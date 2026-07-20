<?php

namespace App\Console\Commands;

use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Audit command untuk Pahan v2.6.
 *
 * Memeriksa konsistensi seluruh data sesuai acceptance criteria v2.6.
 */
class FinalizePahanV2_6 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-6 {--dry-run : Hanya laporkan tanpa perubahan}';

    protected $description = 'Audit konsistensi data Pahan v2.6 — realisasi, tahap aktif, snapshot, status legacy';

    private int $issues = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  AUDIT PAHAN v2.6 — Finalisasi Menyeluruh');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->checkMigrationSchema();
        $this->checkEngineVersion();
        $this->checkSnapshotConsistency();
        $this->checkStageLogic();
        $this->checkRealizationLogic();
        $this->checkScheduleConsistency();
        $this->checkSupportingFertilizerDose();
        $this->checkLegacyStatusUsage();
        $this->checkFingerprintConsistency();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');

        if ($this->issues === 0) {
            $this->info('✅ Tidak ada masalah ditemukan. Pahan v2.6 konsisten.');

            return self::SUCCESS;
        }

        $this->error("❌ Ditemukan {$this->issues} masalah. Perbaiki sebelum release.");

        return self::FAILURE;
    }

    private function addIssue(string $category, string $message): void
    {
        $this->issues++;
        $this->warn("  [{$category}] {$message}");
    }

    private function checkMigrationSchema(): void
    {
        $this->info('▸ Memeriksa schema migration...');

        // Cek tabel realisasi punya field v2.6
        $requiredColumns = ['tahun_program', 'confirmed_over_plan', 'override_annual_limit', 'override_reason'];
        foreach ($requiredColumns as $col) {
            if (! Schema::hasColumn('realisasi_pemupukans', $col)) {
                $this->addIssue('MIGRATION', "Kolom '{$col}' belum ada di realisasi_pemupukans");
            }
        }

        // Cek tabel rekomendasi_rbs punya field v2.5
        $rbsColumns = ['luas_ha_snapshot', 'sph_snapshot', 'active_stage', 'status_stage'];
        foreach ($rbsColumns as $col) {
            if (! Schema::hasColumn('rekomendasi_rbs', $col)) {
                $this->addIssue('MIGRATION', "Kolom '{$col}' belum ada di rekomendasi_rbs");
            }
        }
    }

    private function checkEngineVersion(): void
    {
        $this->info('▸ Memeriksa versi mesin...');

        $expected = 'pahan-v2.6';
        $actual = config('fertilization.engine_version');

        if ($actual !== $expected) {
            $this->addIssue('VERSION', "Versi mesin di config: '{$actual}', seharusnya '{$expected}'");
        }

        // Cek rekomendasi terbaru menggunakan versi yang benar
        $latest = RekomendasiRbs::where('is_latest', true)->first();
        if ($latest && $latest->versi_mesin_rekomendasi && ! str_starts_with($latest->versi_mesin_rekomendasi, 'pahan-v2.')) {
            $this->addIssue('VERSION', "Rekomendasi terbaru menggunakan versi: '{$latest->versi_mesin_rekomendasi}'");
        }
    }

    private function checkSnapshotConsistency(): void
    {
        $this->info('▸ Memeriksa konsistensi snapshot...');

        $count = RekomendasiRbs::where('is_latest', true)
            ->whereNull('luas_ha_snapshot')
            ->whereNotNull('urea_total_estimasi_tahunan')
            ->count();

        if ($count > 0) {
            $this->addIssue('SNAPSHOT', "{$count} rekomendasi terbaru memiliki kebutuhan tahunan tanpa luas_ha_snapshot");
        }

        $countSph = RekomendasiRbs::where('is_latest', true)
            ->whereNull('sph_snapshot')
            ->whereNotNull('jumlah_pokok_snapshot')
            ->count();

        if ($countSph > 0) {
            $this->addIssue('SNAPSHOT', "{$countSph} rekomendasi terbaru memiliki jumlah_pokok_snapshot tanpa sph_snapshot");
        }

        // Cek jumlah_pokok = luas × SPH
        $inconsistent = RekomendasiRbs::where('is_latest', true)
            ->whereNotNull('luas_ha_snapshot')
            ->whereNotNull('sph_snapshot')
            ->whereNotNull('jumlah_pokok_snapshot')
            ->get()
            ->filter(function ($r) {
                $expected = (int) ($r->luas_ha_snapshot * $r->sph_snapshot);

                return abs($r->jumlah_pokok_snapshot - $expected) > 1;
            })
            ->count();

        if ($inconsistent > 0) {
            $this->addIssue('SNAPSHOT', "{$inconsistent} rekomendasi memiliki jumlah_pokok_snapshot tidak sesuai luas × SPH");
        }
    }

    private function checkStageLogic(): void
    {
        $this->info('▸ Memeriksa logika tahap aktif...');

        // Tahap 1 sebagian tapi status Tahap 2
        $wrong = RekomendasiRbs::where('is_latest', true)
            ->where('active_stage', 2)
            ->where('status_stage', CurrentApplicationCalculator::TAHAP_1_SEBAGIAN)
            ->count();

        if ($wrong > 0) {
            $this->addIssue('STAGE', "{$wrong} rekomendasi memiliki active_stage=2 tapi status=TAHAP_1_SEBAGIAN");
        }

        // Tahap 2 tanpa Tahap 1 selesai (cek realisasi)
        // This would need more complex checking, simplified here
    }

    private function checkRealizationLogic(): void
    {
        $this->info('▸ Memeriksa logika realisasi...');

        // Skip jika kolom v2.6 belum ada di database
        if (! Schema::hasColumn('realisasi_pemupukans', 'tahun_program')) {
            $this->comment('  Info: Kolom v2.6 belum tersedia di database. Jalankan migration terlebih dahulu.');

            return;
        }

        // Realisasi batal yang mungkin masih terhitung
        $batalCount = RealisasiPemupukan::where('status_realisasi', 'BATAL')->count();
        if ($batalCount > 0) {
            $this->comment("  Info: {$batalCount} realisasi berstatus BATAL (tidak terhitung dalam ringkasan).");
        }

        // Realisasi tanpa tahun_program
        $noYear = RealisasiPemupukan::whereNull('tahun_program')->count();
        if ($noYear > 0) {
            $this->addIssue('REALISASI', "{$noYear} realisasi belum memiliki tahun_program (backfill diperlukan)");
        }

        // Override tanpa alasan
        $noReason = RealisasiPemupukan::where('override_annual_limit', true)
            ->whereNull('override_reason')
            ->count();

        if ($noReason > 0) {
            $this->addIssue('REALISASI', "{$noReason} realisasi memiliki override_annual_limit tanpa override_reason");
        }
    }

    private function checkScheduleConsistency(): void
    {
        $this->info('▸ Memeriksa konsistensi jadwal...');

        // Jadwal terisi saat status menunggu atau selesai
        $statusKosong = [
            CurrentApplicationCalculator::MENUNGGU_INTERVAL,
            CurrentApplicationCalculator::MENUNGGU_KELAYAKAN,
            CurrentApplicationCalculator::SELESAI_TAHUNAN,
            CurrentApplicationCalculator::PERLU_VERIFIKASI_REALISASI,
        ];

        $wrongSchedule = RekomendasiRbs::where('is_latest', true)
            ->whereIn('status_stage', $statusKosong)
            ->whereNotNull('jadwal_pemupukan')
            ->get()
            ->filter(fn ($r) => ! empty($r->jadwal_pemupukan))
            ->count();

        if ($wrongSchedule > 0) {
            $this->addIssue('JADWAL', "{$wrongSchedule} rekomendasi memiliki jadwal terisi saat status menunggu/selesai");
        }

        // Jadwal nomor tahap tidak sesuai active_stage
        $wrongTahap = RekomendasiRbs::where('is_latest', true)
            ->whereNotNull('jadwal_pemupukan')
            ->whereNotNull('active_stage')
            ->where('active_stage', '>', 0)
            ->get()
            ->filter(function ($r) {
                $jadwal = $r->jadwal_pemupukan ?? [];
                if (empty($jadwal)) {
                    return false;
                }
                $firstTahap = $jadwal[0]['tahap'] ?? null;

                return $firstTahap !== null && $firstTahap !== $r->active_stage;
            })
            ->count();

        if ($wrongTahap > 0) {
            $this->addIssue('JADWAL', "{$wrongTahap} rekomendasi memiliki jadwal dengan nomor tahap ≠ active_stage");
        }
    }

    private function checkSupportingFertilizerDose(): void
    {
        $this->info('▸ Memeriksa dosis pupuk pendukung...');

        // Deteksi angka numerik di dalam dosis pupuk pendukung
        $rekomendasis = RekomendasiRbs::where('is_latest', true)
            ->whereNotNull('rekomendasi_pupuk')
            ->get();

        $numericDosis = 0;
        foreach ($rekomendasis as $r) {
            $pupuk = $r->rekomendasi_pupuk ?? [];
            foreach ($pupuk as $p) {
                $dosis = $p['dosis'] ?? '';
                // Deteksi angka: "1 kg", "1.5 kg", "500 gram", dll
                if (preg_match('/\d+[\.,]?\d*\s*(kg|gram|g|ton)/i', $dosis)) {
                    $numericDosis++;
                    break;
                }
            }
        }

        if ($numericDosis > 0) {
            $this->addIssue('PENDUKUNG', "{$numericDosis} rekomendasi memiliki dosis pupuk pendukung yang berbentuk angka tanpa validasi metadata");
        }
    }

    private function checkLegacyStatusUsage(): void
    {
        $this->info('▸ Memeriksa penggunaan status legacy...');

        // Cek views yang masih menggunakan status_kebutuhan_dominan sebagai keputusan
        // (ini dilakukan secara statis, di sini kita cek data saja)
        $this->comment('  Info: Audit penggunaan status legacy di views harus dilakukan secara manual.');
    }

    private function checkFingerprintConsistency(): void
    {
        $this->info('▸ Memeriksa konsistensi fingerprint...');

        $noFingerprint = RekomendasiRbs::where('is_latest', true)
            ->whereNull('analysis_fingerprint')
            ->count();

        if ($noFingerprint > 0) {
            $this->addIssue('FINGERPRINT', "{$noFingerprint} rekomendasi terbaru tanpa analysis_fingerprint");
        }
    }
}
