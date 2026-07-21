<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Audit command Pahan v2.9 — Stabilisasi demo & pengujian lapangan.
 *
 * php artisan sawit:finalize-pahan-v2-9 --dry-run
 */
class FinalizePahanV2_9 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-9 {--dry-run : Hanya audit, tidak mengubah data}';

    protected $description = 'Audit finalisasi Pahan v2.9 — stabilisasi demo, keamanan, konsistensi.';

    private int $issueCount = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info(' AUDIT PAHAN v2.9 — Stabilisasi Demo & Pengujian Lapangan');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $this->checkConfigVersion();
        $this->checkMultiplierNonaktif();
        $this->checkDosisReferensiTersedia();
        $this->checkInterval60Hari();
        $this->checkCurahHujan100250();
        $this->checkProgramKonsisten();
        $this->checkLaporanTidakLegacy();
        $this->checkUiTidakKodeInternal();
        $this->checkTidakAdaAlurKerja();
        $this->checkTidakAdaVerifikasiManual();
        $this->checkTidakAdaBobotValidasiAhli();
        $this->checkDemoTidakOtomatis();
        $this->checkBackupTidakPublic();
        $this->checkDebugProduction();
        $this->checkMigrationReversible();
        $this->checkHealthCheck();
        $this->checkReliabilityTotal100();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');

        if ($this->issueCount === 0) {
            $this->info('✅ Tidak ada masalah ditemukan. Pahan v2.9 siap.');

            return self::SUCCESS;
        }

        $this->error("❌ Ditemukan {$this->issueCount} masalah. Perbaiki sebelum release.");

        return self::FAILURE;
    }

    private function checkConfigVersion(): void
    {
        $this->info('🔍 [1] Config engine version...');
        $version = config('fertilization.engine_version');
        if ($version !== 'pahan-v2.9') {
            $this->warn("   ⚠ Config engine_version = '{$version}' (seharusnya 'pahan-v2.9')");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Config tepat pahan-v2.9');
        }
    }

    private function checkMultiplierNonaktif(): void
    {
        $this->info('🔍 [2] Multiplier nonaktif...');
        $enabled = config('fertilization.legacy_multipliers.enabled', false);
        if ($enabled) {
            $this->warn('   ⚠ Legacy multipliers masih aktif');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Multiplier nonaktif');
        }
    }

    private function checkDosisReferensiTersedia(): void
    {
        $this->info('🔍 [3] Dosis referensi tersedia...');
        $ref = config('fertilization.dose_reference');
        if (empty($ref) || empty($ref['TBM']) || empty($ref['TM'])) {
            $this->warn('   ⚠ Dosis referensi tidak lengkap');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Dosis referensi TBM dan TM tersedia');
        }
    }

    private function checkInterval60Hari(): void
    {
        $this->info('🔍 [4] Interval 60 hari...');
        $interval = config('fertilization.window.min_interval_days');
        if ($interval !== 60) {
            $this->warn("   ⚠ Interval = {$interval} (seharusnya 60)");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Interval 60 hari');
        }
    }

    private function checkCurahHujan100250(): void
    {
        $this->info('🔍 [5] Curah hujan 100–250...');
        $min = config('fertilization.window.rainfall_min_mm');
        $max = config('fertilization.window.rainfall_max_mm');
        if ($min !== 100 || $max !== 250) {
            $this->warn("   ⚠ Curah hujan {$min}–{$max} (seharusnya 100–250)");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Curah hujan 100–250 mm/bulan');
        }
    }

    private function checkProgramKonsisten(): void
    {
        $this->info('🔍 [6] Program konsisten (via health-check)...');
        $this->call('sawit:health-check', ['--dry-run' => true]);
        // health-check will output its own results
        $this->line('   ✓ Health check selesai (lihat output di atas)');
    }

    private function checkLaporanTidakLegacy(): void
    {
        $this->info('🔍 [7] Laporan tidak memakai status legacy untuk keputusan...');
        $file = app_path('Http/Controllers/LaporanController.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, "->sum('total_urea')") || str_contains($content, "->sum('total_kcl')")) {
                $this->warn("   ⚠ Laporan masih memakai ->sum('total_urea')");
                $this->issueCount++;
            } else {
                $this->line('   ✓ Laporan tidak memakai field legacy');
            }
        }
    }

    private function checkUiTidakKodeInternal(): void
    {
        $this->info('🔍 [8] UI tidak menampilkan kode internal...');
        $technicalCodes = ['TAHAP_1_SIAP', 'MENUNGGU_INTERVAL', 'LAYAK_DIJADWALKAN', 'GEJALA_BERAT', 'SELESAI_TAHUNAN'];
        $issues = 0;

        $viewPaths = [
            resource_path('views/rbs'),
            resource_path('views/realisasi_pemupukan'),
            resource_path('views/laporan'),
            resource_path('views/dashboard'),
        ];

        foreach ($viewPaths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }
            foreach (File::allFiles($path) as $file) {
                $content = File::get($file->getPathname());
                foreach ($technicalCodes as $code) {
                    if (preg_match('/>\s*'.preg_quote($code, '/').'\s*</', $content)
                        || preg_match('/\{\{\s*[\'"]'.preg_quote($code, '/').'[\'"]\s*\}\}/', $content)) {
                        $issues++;
                        break 2;
                    }
                }
            }
        }

        if ($issues > 0) {
            $this->warn('   ⚠ Kode teknis mungkin ditampilkan di UI');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Kode internal tidak tampil di UI');
        }
    }

    private function checkTidakAdaAlurKerja(): void
    {
        $this->info('🔍 [9] Tidak ada Alur Kerja Pemupukan...');
        $dashboardView = resource_path('views/dashboard/index.blade.php');
        if (File::exists($dashboardView)) {
            $content = File::get($dashboardView);
            if (str_contains($content, 'Alur Kerja') || str_contains($content, 'alur-kerja') || str_contains($content, 'stepper')) {
                $this->warn('   ⚠ Dashboard masih mengandung Alur Kerja / stepper');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Tidak ada Alur Kerja Pemupukan');
            }
        }
    }

    private function checkTidakAdaVerifikasiManual(): void
    {
        $this->info('🔍 [10] Tidak ada menu/route verifikasi manual...');
        $routeFile = base_path('routes/web.php');
        if (File::exists($routeFile)) {
            $content = File::get($routeFile);
            if (str_contains($content, 'verifikasi') || str_contains($content, 'verification')) {
                $this->warn('   ⚠ Route verifikasi manual ditemukan');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Tidak ada route verifikasi manual');
            }
        }
    }

    private function checkTidakAdaBobotValidasiAhli(): void
    {
        $this->info('🔍 [11] Tidak ada bobot validasi ahli aktif...');
        $weights = config('fertilization.reliability_weights');
        if (isset($weights['validasi_ahli'])) {
            $this->warn('   ⚠ Bobot validasi_ahli masih ada di config');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Bobot validasi ahli sudah dihapus');
        }
    }

    private function checkDemoTidakOtomatis(): void
    {
        $this->info('🔍 [12] Data demo tidak otomatis masuk production...');
        $seederFile = base_path('database/seeders/DatabaseSeeder.php');
        if (File::exists($seederFile)) {
            $content = File::get($seederFile);
            if (str_contains($content, 'DemoSawitGisSeeder')) {
                $this->warn('   ⚠ DemoSawitGisSeeder dipanggil dari DatabaseSeeder');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Demo seeder tidak otomatis');
            }
        }
    }

    private function checkBackupTidakPublic(): void
    {
        $this->info('🔍 [13] Backup directory tidak public...');
        if (is_dir(public_path('backups'))) {
            $this->warn('   ⚠ Direktori public/backups ada');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Backup tidak di public/');
        }
    }

    private function checkDebugProduction(): void
    {
        $this->info('🔍 [14] Debug production mati...');
        $envProd = base_path('.env.production');
        if (File::exists($envProd)) {
            $content = File::get($envProd);
            if (str_contains($content, 'APP_DEBUG=true')) {
                $this->warn('   ⚠ .env.production memiliki APP_DEBUG=true');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Debug mati di production');
            }
        } else {
            $this->line('   ℹ .env.production tidak ada (cek manual)');
        }
    }

    private function checkMigrationReversible(): void
    {
        $this->info('🔍 [15] Migration reversible...');
        $migrationsPath = database_path('migrations');
        $files = File::glob("{$migrationsPath}/*.php");
        $issues = 0;

        foreach ($files as $file) {
            $content = File::get($file);
            // Check that down() method exists and is not empty
            if (preg_match('/public function down\(\).*?\{(.*?)\}/s', $content, $matches)) {
                $body = trim($matches[1]);
                if (empty($body) || $body === '//') {
                    $issues++;
                }
            }
        }

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} migration tanpa implementasi down()");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Migration reversible');
        }
    }

    private function checkHealthCheck(): void
    {
        $this->info('🔍 [16] Health check command tersedia...');
        $file = app_path('Console/Commands/HealthCheck.php');
        if (File::exists($file)) {
            $this->line('   ✓ Health check command tersedia');
        } else {
            $this->warn('   ⚠ HealthCheck.php belum dibuat');
            $this->issueCount++;
        }
    }

    private function checkReliabilityTotal100(): void
    {
        $this->info('🔍 [17] Skor keandalan berjumlah 100...');
        $weights = config('fertilization.reliability_weights');
        $total = array_sum($weights);
        if ($total !== 100) {
            $this->warn("   ⚠ Total bobot keandalan = {$total} (seharusnya 100)");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Total bobot = 100');
        }
    }
}
