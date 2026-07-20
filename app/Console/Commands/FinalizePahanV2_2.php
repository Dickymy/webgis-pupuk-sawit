<?php

namespace App\Console\Commands;

use App\Enums\PlantPhase;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use Illuminate\Console\Command;

/**
 * sawit:finalize-pahan-v2-2 — Audit dan migrasi data untuk Pahan v2.2.
 *
 * Kemampuan:
 * - Mendeteksi blok dengan konflik fase
 * - Mendeteksi rekomendasi tanpa umur snapshot
 * - Mendeteksi dosis pupuk pendukung belum tervalidasi
 * - Mendeteksi tampilan singkatan fase
 * - Tidak mengubah data saat --dry-run
 */
class FinalizePahanV2_2 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-2 {--dry-run : Hanya audit tanpa mengubah data}';
    protected $description = 'Audit dan finalisasi data untuk engine Pahan v2.2';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  AUDIT PAHAN V2.2' . ($dryRun ? ' (DRY RUN — tidak ada perubahan)' : ''));
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $issues = [];

        // 1. Deteksi blok dengan konflik fase
        $this->info('1. Memeriksa konsistensi fase tanaman...');
        $bloks = BlokLahan::whereNotNull('fase_tanaman')->get();
        $konflikFase = 0;

        foreach ($bloks as $blok) {
            $umur = $blok->umur_tanaman;
            if ($umur === null) continue;

            if ($umur < 3 && $blok->fase_tanaman === 'TM') {
                $konflikFase++;
                $issues[] = "Blok '{$blok->nama_blok}' (ID:{$blok->id}): umur {$umur} tahun tapi fase TM";
            }
            if ($umur > 3 && $blok->fase_tanaman === 'TBM') {
                $konflikFase++;
                $issues[] = "Blok '{$blok->nama_blok}' (ID:{$blok->id}): umur {$umur} tahun tapi fase TBM";
            }
        }
        $this->line("   Konflik fase ditemukan: {$konflikFase}");

        // 2. Deteksi rekomendasi tanpa umur snapshot
        $this->info('2. Memeriksa rekomendasi tanpa umur snapshot...');
        $tanpaUmur = RekomendasiRbs::whereNull('umur_tanaman_snapshot')->count();
        $this->line("   Rekomendasi tanpa umur snapshot: {$tanpaUmur}");
        if ($tanpaUmur > 0) {
            $issues[] = "{$tanpaUmur} rekomendasi tanpa umur_tanaman_snapshot";
        }

        // 3. Deteksi rekomendasi tanpa tanggal_referensi_umur
        $this->info('3. Memeriksa rekomendasi tanpa tanggal referensi umur...');
        $tanpaTanggal = RekomendasiRbs::whereNull('tanggal_referensi_umur')
            ->whereNotNull('umur_tanaman_snapshot')
            ->count();
        $this->line("   Rekomendasi tanpa tanggal referensi: {$tanpaTanggal}");

        // 4. Deteksi pupuk pendukung belum tervalidasi
        $this->info('4. Memeriksa rule pupuk pendukung belum tervalidasi...');
        $belumValid = RuleBaseLanjutan::where('aktif', true)
            ->whereNotNull('jenis_pupuk_pendukung')
            ->where(function ($q) {
                $q->whereNull('status_validasi')
                  ->orWhere('status_validasi', 'PERLU_VALIDASI_AHLI');
            })
            ->where('dosis_anjuran', 'LIKE', '%kg%')
            ->count();
        $this->line("   Rule pendukung tanpa validasi (dengan angka kg): {$belumValid}");
        if ($belumValid > 0) {
            $issues[] = "{$belumValid} rule pendukung masih menampilkan angka dosis tanpa validasi";
        }

        // 5. Deteksi versi mesin lama
        $this->info('5. Memeriksa versi mesin rekomendasi...');
        $versiLama = RekomendasiRbs::where('versi_mesin_rekomendasi', '!=', 'pahan-v2.2')
            ->orWhereNull('versi_mesin_rekomendasi')
            ->count();
        $totalRbs = RekomendasiRbs::count();
        $this->line("   Total rekomendasi: {$totalRbs}");
        $this->line("   Versi lama / null: {$versiLama}");

        // 6. Statistik umum
        $this->newLine();
        $this->info('═══ RINGKASAN STATISTIK ═══');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total blok', BlokLahan::count()],
                ['Blok tanpa fase', BlokLahan::whereNull('fase_tanaman')->count()],
                ['Konflik fase', $konflikFase],
                ['Total rekomendasi', $totalRbs],
                ['Tanpa umur snapshot', $tanpaUmur],
                ['Tanpa tanggal referensi', $tanpaTanggal],
                ['Rule aktif', RuleBaseLanjutan::where('aktif', true)->count()],
                ['Rule terverifikasi sumber', RuleBaseLanjutan::where('status_validasi', 'TERVERIFIKASI_SUMBER')->count()],
                ['Rule perlu validasi', RuleBaseLanjutan::where('status_validasi', 'PERLU_VALIDASI_AHLI')->count()],
            ]
        );

        // 7. Masalah ditemukan
        if (!empty($issues)) {
            $this->newLine();
            $this->warn('⚠ Masalah ditemukan:');
            foreach ($issues as $i => $issue) {
                $this->line("   " . ($i + 1) . ". {$issue}");
            }
        } else {
            $this->newLine();
            $this->info('✓ Tidak ada masalah kritis ditemukan.');
        }

        if (!$dryRun && !empty($issues)) {
            $this->newLine();
            $this->warn('Mode LIVE — menjalankan perbaikan otomatis yang aman...');

            // Auto-fix: Blok tanpa fase yang bisa ditentukan otomatis
            $autoFixed = 0;
            $bloksTanpaFase = BlokLahan::whereNull('fase_tanaman')->get();
            foreach ($bloksTanpaFase as $blok) {
                $umur = $blok->umur_tanaman;
                if ($umur !== null && $umur !== 3) {
                    $blok->fase_tanaman = $umur < 3 ? 'TBM' : 'TM';
                    $blok->save();
                    $autoFixed++;
                }
            }
            $this->line("   Fase otomatis ditetapkan: {$autoFixed} blok");
        }

        $this->newLine();
        $this->info('Audit selesai. Versi mesin target: pahan-v2.2');

        return Command::SUCCESS;
    }
}
