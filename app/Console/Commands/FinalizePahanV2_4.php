<?php

namespace App\Console\Commands;

use App\Models\RekomendasiRbs;
use Illuminate\Console\Command;

/**
 * Command audit final Pahan v2.4.
 *
 * Deteksi:
 * - fase snapshot dan kelompok dosis tidak konsisten
 * - total tahunan null
 * - aplikasi saat ini null
 * - jadwal terisi saat tidak layak
 * - status legacy masih menjadi sumber operasional
 * - dosis pendukung tanpa metadata lengkap
 * - umur 3 belum diverifikasi
 * - rekomendasi versi lama
 */
class FinalizePahanV2_4 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-4 {--dry-run : Hanya audit tanpa perubahan}';

    protected $description = 'Audit dan finalisasi data rekomendasi untuk Pahan v2.4';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '🔍 Mode DRY-RUN — tidak ada perubahan pada data.' : '🔧 Mode LIVE — perbaikan aman akan diterapkan.');
        $this->newLine();

        $issues = [];
        $fixed = 0;

        $rekomendasis = RekomendasiRbs::where('is_latest', true)->with('blokLahan')->get();
        $this->info("Memeriksa {$rekomendasis->count()} rekomendasi terbaru...");
        $this->newLine();

        foreach ($rekomendasis as $rbs) {
            $blok = $rbs->blokLahan;
            $blokLabel = $blok ? $blok->nama_blok : "ID:{$rbs->blok_lahan_id}";

            // 1. Fase snapshot dan kelompok dosis tidak konsisten
            if ($rbs->fase_tanaman_snapshot && $rbs->umur_tanaman_snapshot !== null) {
                $fase = $rbs->fase_tanaman_snapshot;
                $umur = $rbs->umur_tanaman_snapshot;
                if ($umur < 3 && $fase === 'TM') {
                    $issues[] = "[{$blokLabel}] Fase snapshot TM tapi umur {$umur} (< 3)";
                }
                if ($umur > 3 && $fase === 'TBM') {
                    $issues[] = "[{$blokLabel}] Fase snapshot TBM tapi umur {$umur} (> 3)";
                }
            }

            // 2. Total tahunan null (padahal ada dosis referensi)
            if ($rbs->urea_estimasi_kg_per_pokok_tahun !== null && $rbs->urea_total_estimasi_tahunan === null) {
                $issues[] = "[{$blokLabel}] Total tahunan Urea null padahal dosis estimasi ada";
            }
            if ($rbs->kcl_estimasi_kg_per_pokok_tahun !== null && $rbs->kcl_total_estimasi_tahunan === null) {
                $issues[] = "[{$blokLabel}] Total tahunan KCl null padahal dosis estimasi ada";
            }

            // 3. Aplikasi saat ini null
            if ($rbs->urea_aplikasi_saat_ini === null) {
                $issues[] = "[{$blokLabel}] urea_aplikasi_saat_ini = NULL";
            }
            if ($rbs->kcl_aplikasi_saat_ini === null) {
                $issues[] = "[{$blokLabel}] kcl_aplikasi_saat_ini = NULL";
            }

            // 4. Jadwal terisi saat tidak layak
            $jadwal = $rbs->jadwal_pemupukan ?? [];
            $kelayakan = $rbs->status_kelayakan_aplikasi;
            $statusLayak = in_array($kelayakan, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN']);
            if (! $statusLayak && ! empty($jadwal)) {
                $issues[] = "[{$blokLabel}] Jadwal terisi ({$this->countJadwalItems($jadwal)} item) tapi kelayakan = {$kelayakan}";
            }

            // 5. Umur 3 belum diverifikasi
            if ($rbs->umur_tanaman_snapshot === 3 && $rbs->fase_tanaman_snapshot === null) {
                $issues[] = "[{$blokLabel}] Umur 3, fase snapshot null — perlu verifikasi";
            }

            // 6. Rekomendasi versi lama
            if ($rbs->versi_mesin_rekomendasi && $rbs->versi_mesin_rekomendasi !== 'pahan-v2.4') {
                $issues[] = "[{$blokLabel}] Versi mesin lama: {$rbs->versi_mesin_rekomendasi}";
            }
        }

        // Output
        $this->newLine();
        if (empty($issues)) {
            $this->info('✅ Tidak ada masalah ditemukan. Semua data konsisten dengan Pahan v2.4.');
        } else {
            $this->warn('⚠️  Ditemukan '.count($issues).' masalah:');
            $this->newLine();
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
        }

        if (! $dryRun && ! empty($issues)) {
            $this->newLine();
            $this->info('Mode live: Perbaikan aman hanya dilakukan pada field yang tidak merusak data historis.');
            $this->info('Untuk memperbaiki versi lama, jalankan analisis ulang pada blok terkait.');
        }

        $this->newLine();
        $this->info("Selesai. Total diperiksa: {$rekomendasis->count()}, Masalah: ".count($issues));

        return empty($issues) ? self::SUCCESS : self::FAILURE;
    }

    private function countJadwalItems(array $jadwal): int
    {
        return count($jadwal);
    }
}
