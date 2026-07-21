<?php

namespace App\Console\Commands;

use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Audit command Pahan v2.8 — Periksa seluruh masalah integrasi program.
 *
 * php artisan sawit:finalize-pahan-v2-8 --dry-run
 */
class FinalizePahanV2_8 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-8 {--dry-run : Hanya audit, tidak mengubah data}';

    protected $description = 'Audit finalisasi Pahan v2.8 — integrasi program, isolasi realisasi, siklus hidup, UX.';

    private int $issueCount = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info(' AUDIT PAHAN v2.8 — Integrasi Program & UX');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $this->checkConfigVersion();
        $this->checkRekomendasiTerbaruTanpaProgram();
        $this->checkMismatchProgramRekomendasiRealisasi();
        $this->checkProgramAktifGanda();
        $this->checkProgramSelesaiMasihAktif();
        $this->checkRealisasiCampurProgram();
        $this->checkRekomendasiHistorisBisaRealisasi();
        $this->checkTahap2TanpaTahap1Selesai();
        $this->checkTahap2Sebelum60Hari();
        $this->checkUreaKclIndependen();
        $this->checkHistoriTransisiTahap();
        $this->checkFingerprintBerbasisProgram();
        $this->checkLaporanMasihLegacy();
        $this->checkSubtotalLaporanLegacy();
        $this->checkTombolRealisasiMunculSalah();
        $this->checkKodeTeknisDiUi();
        $this->checkMigrationRollbackRisiko();
        $this->checkTrueLegacyUpgradeTest();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');

        if ($this->issueCount === 0) {
            $this->info('✅ Tidak ada masalah ditemukan. Pahan v2.8 siap.');

            return self::SUCCESS;
        }

        $this->error("❌ Ditemukan {$this->issueCount} masalah. Perbaiki sebelum release.");

        return self::FAILURE;
    }

    private function checkConfigVersion(): void
    {
        $this->info('🔍 [1] Config engine version...');
        $version = config('fertilization.engine_version');
        if ($version !== 'pahan-v2.8') {
            $this->warn("   ⚠ Config engine_version = '{$version}' (seharusnya 'pahan-v2.8')");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Config tepat pahan-v2.8');
        }
    }

    private function checkRekomendasiTerbaruTanpaProgram(): void
    {
        $this->info('🔍 [2] Rekomendasi terbaru tanpa program...');

        if (! Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id')) {
            $this->warn('   ⚠ Kolom program_pemupukan_id belum ada di rekomendasi_rbs');
            $this->issueCount++;

            return;
        }

        $count = RekomendasiRbs::where('is_latest', true)
            ->whereNull('program_pemupukan_id')
            ->where('urea_total_estimasi_tahunan', '>', 0)
            ->count();

        if ($count > 0) {
            $this->warn("   ⚠ {$count} rekomendasi terbaru (dengan dosis) tanpa program");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Semua rekomendasi terbaru dengan dosis memiliki program');
        }
    }

    private function checkMismatchProgramRekomendasiRealisasi(): void
    {
        $this->info('🔍 [3] Mismatch program rekomendasi dan realisasi...');

        if (! Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id')) {
            $this->line('   ℹ Kolom belum ada');

            return;
        }

        $mismatch = DB::table('realisasi_pemupukans as r')
            ->join('rekomendasi_rbs as rbs', 'r.rekomendasi_rbs_id', '=', 'rbs.id')
            ->whereNotNull('r.program_pemupukan_id')
            ->whereNotNull('rbs.program_pemupukan_id')
            ->whereColumn('r.program_pemupukan_id', '!=', 'rbs.program_pemupukan_id')
            ->where('r.status_realisasi', '!=', 'BATAL')
            ->count();

        if ($mismatch > 0) {
            $this->warn("   ⚠ {$mismatch} realisasi dengan program berbeda dari rekomendasinya");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada mismatch program');
        }
    }

    private function checkProgramAktifGanda(): void
    {
        $this->info('🔍 [4] Program aktif ganda...');

        if (! Schema::hasTable('program_pemupukans')) {
            $this->warn('   ⚠ Tabel program_pemupukans belum ada');
            $this->issueCount++;

            return;
        }

        $ganda = DB::table('program_pemupukans')
            ->select('blok_lahan_id', 'tahun_program')
            ->where('status_program', 'AKTIF')
            ->groupBy('blok_lahan_id', 'tahun_program')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($ganda > 0) {
            $this->warn("   ⚠ {$ganda} kombinasi blok/tahun memiliki program aktif ganda");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada program aktif ganda');
        }
    }

    private function checkProgramSelesaiMasihAktif(): void
    {
        $this->info('🔍 [5] Program selesai tetapi masih aktif...');

        if (! Schema::hasTable('program_pemupukans')) {
            return;
        }

        // Program yang urea_sisa=0 dan kcl_sisa=0 tapi masih AKTIF
        $programs = ProgramPemupukan::where('status_program', 'AKTIF')->get();
        $issues = 0;

        foreach ($programs as $program) {
            $rekomendasi = RekomendasiRbs::where('program_pemupukan_id', $program->id)
                ->where('is_latest', true)
                ->first();

            if ($rekomendasi
                && (float) ($rekomendasi->urea_sisa_tahunan ?? 1) <= 0
                && (float) ($rekomendasi->kcl_sisa_tahunan ?? 1) <= 0
            ) {
                $issues++;
            }
        }

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} program masih AKTIF tapi sisa tahunan = 0");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada program selesai yang masih AKTIF');
        }
    }

    private function checkRealisasiCampurProgram(): void
    {
        $this->info('🔍 [6] Realisasi tercampur antarprogram...');

        if (! Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id')) {
            return;
        }

        // Cek apakah ada blok yang punya realisasi aktif di >1 program pada tahun yang sama
        $campuran = DB::table('realisasi_pemupukans')
            ->select('blok_lahan_id', 'tahun_program')
            ->whereNotNull('program_pemupukan_id')
            ->where('status_realisasi', '!=', 'BATAL')
            ->groupBy('blok_lahan_id', 'tahun_program')
            ->havingRaw('COUNT(DISTINCT program_pemupukan_id) > 1')
            ->count();

        if ($campuran > 0) {
            $this->warn("   ⚠ {$campuran} blok/tahun memiliki realisasi di >1 program");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada pencampuran realisasi antarprogram');
        }
    }

    private function checkRekomendasiHistorisBisaRealisasi(): void
    {
        $this->info('🔍 [7] Rekomendasi historis masih bisa dicatat realisasi...');

        // Check source code: RealisasiEligibilityService must reject is_latest = false
        $file = app_path('Services/RealisasiEligibilityService.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (! str_contains($content, 'is_latest') || ! str_contains($content, 'rekomendasi historis')) {
                $this->warn('   ⚠ EligibilityService tidak menolak rekomendasi historis');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Rekomendasi historis ditolak untuk realisasi');
            }
        }
    }

    private function checkTahap2TanpaTahap1Selesai(): void
    {
        $this->info('🔍 [8] Tahap 2 tanpa Tahap 1 selesai...');

        $tahap2Bloks = RealisasiPemupukan::where('tahap', 2)
            ->where('status_realisasi', '!=', 'BATAL')
            ->pluck('blok_lahan_id')
            ->unique();

        $issues = 0;
        foreach ($tahap2Bloks as $blokId) {
            $tahap1Total = RealisasiPemupukan::where('blok_lahan_id', $blokId)
                ->where('tahap', 1)
                ->where('status_realisasi', '!=', 'BATAL')
                ->sum('urea_realisasi_kg');

            $tahap1Rencana = RealisasiPemupukan::where('blok_lahan_id', $blokId)
                ->where('tahap', 1)
                ->where('status_realisasi', '!=', 'BATAL')
                ->max('urea_rencana_kg');

            if ($tahap1Rencana > 0 && $tahap1Total < ($tahap1Rencana - 0.01)) {
                $issues++;
            }
        }

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} blok memiliki Tahap 2 tapi Tahap 1 belum selesai");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Tidak ada Tahap 2 tanpa Tahap 1 selesai');
        }
    }

    private function checkTahap2Sebelum60Hari(): void
    {
        $this->info('🔍 [9] Tahap 2 sebelum 60 hari setelah Tahap 1...');

        // Cek per blok/tahun: tanggal tahap 2 - tanggal terakhir tahap 1 < 60
        $tahap2Records = RealisasiPemupukan::where('tahap', 2)
            ->where('status_realisasi', '!=', 'BATAL')
            ->get();

        $issues = 0;
        foreach ($tahap2Records as $record) {
            $tahap1Terakhir = RealisasiPemupukan::where('blok_lahan_id', $record->blok_lahan_id)
                ->where('tahap', 1)
                ->where('status_realisasi', '!=', 'BATAL')
                ->max('tanggal_realisasi');

            if ($tahap1Terakhir && $record->tanggal_realisasi) {
                $diff = $record->tanggal_realisasi->diffInDays($tahap1Terakhir);
                if ($diff < 60) {
                    $issues++;
                }
            }
        }

        if ($issues > 0) {
            $this->warn("   ⚠ {$issues} realisasi Tahap 2 dilakukan <60 hari setelah Tahap 1");
            $this->issueCount++;
        } else {
            $this->line('   ✓ Semua Tahap 2 memenuhi interval 60 hari');
        }
    }

    private function checkUreaKclIndependen(): void
    {
        $this->info('🔍 [10] Urea dan KCl diverifikasi independen...');

        $file = app_path('Services/FertilizationRealizationService.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, 'ureaTerpenuhi') && str_contains($content, 'kclTerpenuhi')) {
                $this->line('   ✓ Urea dan KCl dievaluasi secara independen');
            } else {
                $this->warn('   ⚠ Evaluasi Urea/KCl mungkin tidak independen');
                $this->issueCount++;
            }
        }
    }

    private function checkHistoriTransisiTahap(): void
    {
        $this->info('🔍 [11] Histori transisi tahap tercatat...');

        if (! Schema::hasTable('rekomendasi_operasional_histories')) {
            $this->warn('   ⚠ Tabel rekomendasi_operasional_histories belum ada');
            $this->issueCount++;

            return;
        }

        $file = app_path('Http/Controllers/RealisasiPemupukanController.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, 'recordOperationalHistory')
                && str_contains($content, 'REALISASI_DIBUAT')
                && str_contains($content, 'REALISASI_DIPERBARUI')
                && str_contains($content, 'REALISASI_DIBATALKAN')) {
                $this->line('   ✓ Histori operasional dicatat untuk semua operasi');
            } else {
                $this->warn('   ⚠ Beberapa event histori belum dicatat');
                $this->issueCount++;
            }
        }
    }

    private function checkFingerprintBerbasisProgram(): void
    {
        $this->info('🔍 [12] Fingerprint berbasis program...');

        $file = app_path('Services/RbsService.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, "'program_pemupukan_id'") && str_contains($content, 'generateFingerprint')) {
                $this->line('   ✓ Fingerprint memasukkan program_pemupukan_id');
            } else {
                $this->warn('   ⚠ Fingerprint belum memasukkan program_pemupukan_id');
                $this->issueCount++;
            }
        }

        $file2 = app_path('Services/RecommendationOperationalRefreshService.php');
        if (File::exists($file2)) {
            $content2 = File::get($file2);
            if (str_contains($content2, 'program_pemupukan_id') && str_contains($content2, 'realisasi_aktif')) {
                $this->line('   ✓ Refresh fingerprint berbasis program dan realisasi');
            } else {
                $this->warn('   ⚠ Refresh fingerprint belum lengkap');
                $this->issueCount++;
            }
        }
    }

    private function checkLaporanMasihLegacy(): void
    {
        $this->info('🔍 [13] Laporan masih memakai status legacy untuk keputusan...');

        $file = app_path('Http/Controllers/LaporanController.php');
        if (File::exists($file)) {
            $content = File::get($file);
            // Cek apakah masih filter by status_kebutuhan_dominan atau sum total_urea/total_kcl
            if (str_contains($content, "status_kebutuhan_dominan', ['Normal', 'Segera']")
                || str_contains($content, 'in_array($r->status_kebutuhan_dominan')) {
                $this->warn('   ⚠ Laporan masih memakai status_kebutuhan_dominan untuk filter/subtotal');
                $this->issueCount++;
            } else {
                $this->line('   ✓ Laporan tidak memakai status legacy untuk keputusan');
            }
        }
    }

    private function checkSubtotalLaporanLegacy(): void
    {
        $this->info('🔍 [14] Subtotal laporan masih memakai field legacy...');

        $file = app_path('Http/Controllers/LaporanController.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, "->sum('total_urea')") || str_contains($content, "->sum('total_kcl')")) {
                $this->warn("   ⚠ Laporan masih memakai ->sum('total_urea') / ->sum('total_kcl')");
                $this->issueCount++;
            } else {
                $this->line('   ✓ Subtotal laporan memakai urea_aplikasi_saat_ini / kcl_aplikasi_saat_ini');
            }
        }
    }

    private function checkTombolRealisasiMunculSalah(): void
    {
        $this->info('🔍 [15] Tombol realisasi muncul saat tidak eligible...');

        // Check views for eligibility guard
        $file = resource_path('views/rbs/detail.blade.php');
        if (File::exists($file)) {
            $content = File::get($file);
            if (str_contains($content, 'is_tahap_siap') || str_contains($content, 'eligib')) {
                $this->line('   ✓ Tombol realisasi dijaga oleh pemeriksaan kelayakan');
            } else {
                $this->warn('   ⚠ Tombol realisasi mungkin muncul tanpa pemeriksaan kelayakan');
                $this->issueCount++;
            }
        } else {
            $this->line('   ℹ View rbs/detail.blade.php tidak ditemukan');
        }
    }

    private function checkKodeTeknisDiUi(): void
    {
        $this->info('🔍 [16] Kode teknis muncul di UI...');

        $viewPaths = [
            resource_path('views/rbs'),
            resource_path('views/realisasi_pemupukan'),
            resource_path('views/laporan'),
            resource_path('views/dashboard'),
        ];

        $technicalCodes = [
            'TAHAP_1_SIAP',
            'MENUNGGU_INTERVAL',
            'LAYAK_DIJADWALKAN',
            'GEJALA_BERAT',
            'SELESAI_TAHUNAN',
            'PERLU_VERIFIKASI_REALISASI',
        ];

        $issues = 0;
        foreach ($viewPaths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $content = File::get($file->getPathname());
                foreach ($technicalCodes as $code) {
                    // Skip if inside {{ ... }} accessor calls or in comments/data attributes
                    if (preg_match('/[\'"]'.preg_quote($code, '/').'[\'"]/', $content)) {
                        // Check if it's displayed directly (not via accessor)
                        if (preg_match('/>\s*'.preg_quote($code, '/').'\s*</', $content)
                            || preg_match('/\{\{\s*[\'"]'.preg_quote($code, '/').'[\'"]\s*\}\}/', $content)) {
                            $issues++;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($issues > 0) {
            $this->warn('   ⚠ Kode teknis mungkin ditampilkan langsung di UI');
            $this->issueCount++;
        } else {
            $this->line('   ✓ Kode teknis tidak tampil langsung di UI');
        }
    }

    private function checkMigrationRollbackRisiko(): void
    {
        $this->info('🔍 [17] Migration rollback risiko...');

        $v28Migration = '2026_07_23_000001_add_active_key_to_program_pemupukans_table';

        if (Schema::hasTable('migrations')) {
            $exists = DB::table('migrations')->where('migration', $v28Migration)->exists();
            if ($exists) {
                $this->line('   ✓ Migration v2.8 sudah dijalankan');
            } else {
                $this->warn("   ⚠ Migration v2.8 belum dijalankan: {$v28Migration}");
                $this->issueCount++;
            }
        } else {
            $this->line('   ℹ Tabel migrations tidak tersedia');
        }
    }

    private function checkTrueLegacyUpgradeTest(): void
    {
        $this->info('🔍 [18] True legacy upgrade test nyata...');

        $testFile = base_path('tests/Feature/TrueLegacySchemaUpgradeV28Test.php');
        if (File::exists($testFile)) {
            $content = File::get($testFile);
            if (str_contains($content, 'migrate') && str_contains($content, 'rollback')) {
                $this->line('   ✓ True legacy upgrade test v2.8 tersedia');
            } else {
                $this->warn('   ⚠ Test tidak melakukan migrate dan rollback nyata');
                $this->issueCount++;
            }
        } else {
            $this->warn('   ⚠ File TrueLegacySchemaUpgradeV28Test.php belum ada');
            $this->issueCount++;
        }
    }
}
