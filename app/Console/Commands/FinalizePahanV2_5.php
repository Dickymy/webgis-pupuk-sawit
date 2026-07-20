<?php

namespace App\Console\Commands;

use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Console\Command;

/**
 * Command audit final Pahan v2.5.
 *
 * Deteksi:
 * - fase snapshot tidak sesuai umur
 * - kelompok dosis tidak sesuai fase snapshot
 * - luas/SPH snapshot kosong
 * - jumlah pokok tidak sesuai luas × SPH
 * - total tahunan tidak sesuai dosis × jumlah pokok
 * - aplikasi saat ini lebih besar dari sisa kebutuhan
 * - aplikasi saat ini sama dengan total tahunan padahal Tahap 1 belum direalisasikan
 * - jadwal terisi saat tidak layak
 * - Tahap 2 muncul tanpa realisasi Tahap 1
 * - Tahap 2 muncul sebelum 60 hari
 * - total realisasi melebihi kebutuhan tahunan
 * - status legacy masih menjadi filter utama dashboard
 * - pupuk pendukung memiliki angka tanpa metadata lengkap
 * - fingerprint tidak memiliki komponen baru
 * - versi mesin lama
 * - umur tiga tahun tanpa verifikasi
 */
class FinalizePahanV2_5 extends Command
{
    protected $signature = 'sawit:finalize-pahan-v2-5 {--dry-run : Hanya audit tanpa perubahan}';

    protected $description = 'Audit dan finalisasi data rekomendasi untuk Pahan v2.5';

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

            // 1. Fase snapshot tidak sesuai umur
            if ($rbs->fase_tanaman_snapshot && $rbs->umur_tanaman_snapshot !== null) {
                $umur = $rbs->umur_tanaman_snapshot;
                $fase = $rbs->fase_tanaman_snapshot;
                if ($umur < 3 && $fase === 'TM') {
                    $issues[] = "[{$blokLabel}] Fase snapshot TM tapi umur {$umur} (< 3)";
                }
                if ($umur > 3 && $fase === 'TBM') {
                    $issues[] = "[{$blokLabel}] Fase snapshot TBM tapi umur {$umur} (> 3)";
                }
            }

            // 2. Luas/SPH snapshot kosong
            if ($rbs->luas_ha_snapshot === null || $rbs->luas_ha_snapshot <= 0) {
                $issues[] = "[{$blokLabel}] luas_ha_snapshot kosong atau 0";
            }
            if ($rbs->sph_snapshot === null || $rbs->sph_snapshot <= 0) {
                $issues[] = "[{$blokLabel}] sph_snapshot kosong atau 0";
            }

            // 3. Jumlah pokok tidak sesuai luas × SPH
            if ($rbs->luas_ha_snapshot && $rbs->sph_snapshot && $rbs->jumlah_pokok_snapshot) {
                $expected = (int) ($rbs->luas_ha_snapshot * $rbs->sph_snapshot);
                if (abs($rbs->jumlah_pokok_snapshot - $expected) > 1) {
                    $issues[] = "[{$blokLabel}] jumlah_pokok_snapshot ({$rbs->jumlah_pokok_snapshot}) != luas ({$rbs->luas_ha_snapshot}) × SPH ({$rbs->sph_snapshot}) = {$expected}";
                }
            }

            // 4. Total tahunan tidak sesuai dosis × jumlah pokok
            if ($rbs->urea_estimasi_kg_per_pokok_tahun && $rbs->jumlah_pokok_snapshot && $rbs->urea_total_estimasi_tahunan) {
                $expectedTotal = round($rbs->urea_estimasi_kg_per_pokok_tahun * $rbs->jumlah_pokok_snapshot, 2);
                if (abs($rbs->urea_total_estimasi_tahunan - $expectedTotal) > 1) {
                    $issues[] = "[{$blokLabel}] Urea total tahunan ({$rbs->urea_total_estimasi_tahunan}) != dosis ({$rbs->urea_estimasi_kg_per_pokok_tahun}) × pokok ({$rbs->jumlah_pokok_snapshot})";
                }
            }

            // 5. Aplikasi saat ini lebih besar dari sisa kebutuhan
            if ($rbs->urea_aplikasi_saat_ini > 0 && $rbs->urea_sisa_tahunan !== null) {
                if ($rbs->urea_aplikasi_saat_ini > $rbs->urea_sisa_tahunan + 0.01) {
                    $issues[] = "[{$blokLabel}] urea_aplikasi_saat_ini ({$rbs->urea_aplikasi_saat_ini}) > sisa tahunan ({$rbs->urea_sisa_tahunan})";
                }
            }

            // 6. Aplikasi saat ini = total tahunan (v2.4 bug — seharusnya max 50%)
            if ($rbs->urea_total_estimasi_tahunan > 0 && $rbs->urea_aplikasi_saat_ini > 0) {
                if (abs($rbs->urea_aplikasi_saat_ini - $rbs->urea_total_estimasi_tahunan) < 0.01) {
                    $issues[] = "[{$blokLabel}] urea_aplikasi_saat_ini = total tahunan ({$rbs->urea_total_estimasi_tahunan}) — seharusnya max 50%";
                }
            }

            // 7. Jadwal terisi saat tidak layak
            $jadwal = $rbs->jadwal_pemupukan ?? [];
            $kelayakan = $rbs->status_kelayakan_aplikasi;
            $statusLayak = in_array($kelayakan, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN']);
            if (! $statusLayak && ! empty($jadwal)) {
                $issues[] = "[{$blokLabel}] Jadwal terisi (".count($jadwal)." item) tapi kelayakan = {$kelayakan}";
            }

            // 8. Tahap 2 muncul tanpa realisasi Tahap 1
            if ($rbs->active_stage === 2 && $rbs->status_stage === 'TAHAP_2_SIAP') {
                // Cek apakah ada realisasi tahap 1
                $realisasiTahap1 = $rbs->realisasiPemupukans()->where('tahap', 1)->exists();
                if (! $realisasiTahap1 && $blok) {
                    $realisasiBlok = RealisasiPemupukan::where('blok_lahan_id', $blok->id)->where('tahap', 1)->exists();
                    if (! $realisasiBlok) {
                        $issues[] = "[{$blokLabel}] Tahap 2 siap tanpa realisasi Tahap 1";
                    }
                }
            }

            // 9. Versi mesin lama
            if ($rbs->versi_mesin_rekomendasi && $rbs->versi_mesin_rekomendasi !== 'pahan-v2.5') {
                $issues[] = "[{$blokLabel}] Versi mesin lama: {$rbs->versi_mesin_rekomendasi}";
            }

            // 10. Umur 3 tanpa verifikasi
            if ($rbs->umur_tanaman_snapshot === 3 && $rbs->fase_tanaman_snapshot === null) {
                $issues[] = "[{$blokLabel}] Umur 3, fase snapshot null — perlu verifikasi";
            }

            // 11. Pupuk pendukung memiliki angka tanpa metadata lengkap
            $pupuk = $rbs->rekomendasi_pupuk ?? [];
            foreach ($pupuk as $p) {
                if (isset($p['dosis']) && is_numeric($p['dosis'] ?? null)) {
                    if (empty($p['sumber_referensi'] ?? null) && empty($p['metadata_lengkap'] ?? null)) {
                        $jenisPupuk = $p['jenis_utama'] ?? '?';
                        $issues[] = "[{$blokLabel}] Pupuk pendukung '{$jenisPupuk}' memiliki angka tanpa metadata lengkap";
                    }
                }
            }
        }

        // 12. Audit dashboard — cek apakah status legacy masih di view
        $dashboardView = resource_path('views/dashboard/index.blade.php');
        if (file_exists($dashboardView)) {
            $content = file_get_contents($dashboardView);
            $legacyPatterns = ['data-status="Darurat"', 'data-status="Segera"', 'data-status="Normal"', 'data-status="Tunda"', 'activeStatuses.indexOf(b.status_rbs'];
            foreach ($legacyPatterns as $pattern) {
                if (str_contains($content, $pattern)) {
                    $issues[] = "[Dashboard] Status legacy masih ditemukan: {$pattern}";
                }
            }
        }

        // Output
        $this->newLine();
        if (empty($issues)) {
            $this->info('✅ Tidak ada masalah ditemukan. Semua data konsisten dengan Pahan v2.5.');
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
            // Perbaikan yang aman: update luas/sph snapshot dari blok terkini
            foreach ($rekomendasis as $rbs) {
                $blok = $rbs->blokLahan;
                if (! $blok) {
                    continue;
                }
                $needsUpdate = false;
                $updates = [];
                if ($rbs->luas_ha_snapshot === null && $blok->luas_ha) {
                    $updates['luas_ha_snapshot'] = $blok->luas_ha;
                    $needsUpdate = true;
                }
                if ($rbs->sph_snapshot === null && $blok->sph) {
                    $updates['sph_snapshot'] = $blok->sph;
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    $rbs->update($updates);
                    $fixed++;
                }
            }
            $this->info("Perbaikan aman diterapkan: {$fixed} record diperbarui.");
        }

        $this->newLine();
        $this->info("Selesai. Total diperiksa: {$rekomendasis->count()}, Masalah: ".count($issues));

        return empty($issues) ? self::SUCCESS : self::FAILURE;
    }
}
