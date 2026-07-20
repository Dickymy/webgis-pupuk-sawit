<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Support\Facades\DB;

/**
 * RecommendationOperationalRefreshService
 *
 * Dipanggil saat realisasi dibuat, diperbarui, atau dibatalkan.
 * Memperbarui HANYA bagian operasional rekomendasi (tahap aktif, sisa tahunan,
 * jadwal, fingerprint) TANPA menjalankan ulang diagnosis visual.
 *
 * Alur:
 * 1. Ambil rekomendasi dasar (is_latest)
 * 2. Hitung ulang ringkasan realisasi
 * 3. Hitung current application (tahap aktif, sisa, status)
 * 4. Hitung schedule baru
 * 5. Update rekomendasi secara aman (field operasional saja)
 * 6. Perbarui fingerprint
 *
 * Pahan v2.6: Tidak membuat record baru — update in-place pada is_latest.
 * Histori rekomendasi lama TIDAK diubah.
 */
class RecommendationOperationalRefreshService
{
    public function __construct(
        private FertilizationRealizationService $realizationService,
        private CurrentApplicationCalculator $currentAppCalculator,
        private FertilizationScheduleService $scheduleService,
        private FertilizationWindowService $windowService,
        private PlantContextService $contextService,
    ) {}

    /**
     * Refresh operasional setelah realisasi berubah.
     */
    public function refreshAfterRealization(RealisasiPemupukan $realisasi): void
    {
        $blok = $realisasi->blokLahan;
        if (! $blok) {
            return;
        }

        $rekomendasi = $realisasi->rekomendasiRbs;
        if (! $rekomendasi) {
            return;
        }

        // Hanya refresh jika ini rekomendasi terbaru
        if (! $rekomendasi->is_latest) {
            // Ambil rekomendasi terbaru untuk blok ini
            $rekomendasi = RekomendasiRbs::where('blok_lahan_id', $blok->id)
                ->where('is_latest', true)
                ->first();

            if (! $rekomendasi) {
                return;
            }
        }

        $this->refreshRekomendasi($rekomendasi, $blok);
    }

    /**
     * Refresh rekomendasi berdasarkan blok lahan.
     */
    public function refreshForBlok(BlokLahan $blok): void
    {
        $rekomendasi = RekomendasiRbs::where('blok_lahan_id', $blok->id)
            ->where('is_latest', true)
            ->first();

        if (! $rekomendasi) {
            return;
        }

        $this->refreshRekomendasi($rekomendasi, $blok);
    }

    /**
     * Refresh internal — update field operasional pada rekomendasi.
     */
    private function refreshRekomendasi(RekomendasiRbs $rekomendasi, BlokLahan $blok): void
    {
        DB::transaction(function () use ($rekomendasi, $blok) {
            // 1. Hitung ulang ringkasan realisasi
            $realizationSummary = $this->realizationService->getRealizationSummary($blok, $rekomendasi->id);

            // 2. Build annual snapshot data dari rekomendasi yang ada
            $annualSnapshot = [
                'urea_total_estimasi_tahunan' => $rekomendasi->urea_total_estimasi_tahunan,
                'kcl_total_estimasi_tahunan' => $rekomendasi->kcl_total_estimasi_tahunan,
                'jumlah_pokok' => $rekomendasi->jumlah_pokok_snapshot,
            ];

            // 3. Evaluasi kelayakan dari kondisi terbaru
            $kondisi = $blok->kondisiTerbaru;
            $windowResult = ['layak' => false, 'alasan' => ['Data kondisi belum tersedia']];
            if ($kondisi) {
                $windowResult = $this->windowService->evaluate($kondisi);
            }

            // 4. Hitung current application
            $currentApp = $this->currentAppCalculator->calculate([
                'annual_snapshot' => $annualSnapshot,
                'window_result' => $windowResult,
                'realization_summary' => $realizationSummary,
                'analysis_date' => now(),
            ]);

            // 5. Hitung jadwal baru (jika kondisi tersedia)
            $jadwal = [];
            if ($kondisi) {
                $tanggalObservasi = $kondisi->tanggal_observasi ?? now();
                $plantContext = $this->contextService->resolve($blok, $tanggalObservasi);

                $jadwal = $this->scheduleService->generate(
                    [
                        'dosis_urea' => $rekomendasi->urea_estimasi_kg_per_pokok_tahun ?? 0,
                        'dosis_kcl' => $rekomendasi->kcl_estimasi_kg_per_pokok_tahun ?? 0,
                        'total_urea' => $currentApp['urea_aplikasi_saat_ini'],
                        'total_kcl' => $currentApp['kcl_aplikasi_saat_ini'],
                        'active_stage' => $currentApp['active_stage'],
                        'status_stage' => $currentApp['status_stage'],
                        'jumlah_pokok_snapshot' => $rekomendasi->jumlah_pokok_snapshot,
                    ],
                    $kondisi,
                    $blok,
                    $windowResult,
                    $plantContext
                );
            }

            // 6. Update field operasional (TIDAK mengubah diagnosis, rules, dosis referensi)
            $rekomendasi->update([
                'active_stage' => $currentApp['active_stage'],
                'status_stage' => $currentApp['status_stage'],
                'urea_aplikasi_saat_ini' => $currentApp['urea_aplikasi_saat_ini'],
                'kcl_aplikasi_saat_ini' => $currentApp['kcl_aplikasi_saat_ini'],
                'urea_sisa_tahunan' => $currentApp['urea_sisa_tahunan'],
                'kcl_sisa_tahunan' => $currentApp['kcl_sisa_tahunan'],
                'tanggal_minimum_tahap_berikutnya' => $currentApp['tanggal_minimum_tahap_berikutnya'],
                'alasan_tahap' => $currentApp['reason'],
                'total_urea' => $currentApp['urea_aplikasi_saat_ini'],
                'total_kcl' => $currentApp['kcl_aplikasi_saat_ini'],
                'jadwal_pemupukan' => $jadwal,
                // Fingerprint berubah karena realisasi berubah
                'analysis_fingerprint' => $this->generateRefreshedFingerprint($rekomendasi, $currentApp),
            ]);
        });
    }

    /**
     * Generate fingerprint baru setelah refresh operasional.
     */
    private function generateRefreshedFingerprint(RekomendasiRbs $rekomendasi, array $currentApp): string
    {
        $fingerprintData = [
            'kondisi_lahan_id' => $rekomendasi->kondisi_lahan_id,
            'versi_mesin' => $rekomendasi->versi_mesin_rekomendasi,
            'fase' => $rekomendasi->fase_tanaman_snapshot,
            'umur' => $rekomendasi->umur_tanaman_snapshot,
            'strategi_estimasi' => $rekomendasi->strategi_estimasi_dosis,
            'urea_estimasi' => $rekomendasi->urea_estimasi_kg_per_pokok_tahun,
            'kcl_estimasi' => $rekomendasi->kcl_estimasi_kg_per_pokok_tahun,
            'status_kondisi' => $rekomendasi->status_kondisi_tanaman,
            'status_kelayakan' => $rekomendasi->status_kelayakan_aplikasi,
            'luas_ha_snapshot' => $rekomendasi->luas_ha_snapshot,
            'sph_snapshot' => $rekomendasi->sph_snapshot,
            'jumlah_pokok_snapshot' => $rekomendasi->jumlah_pokok_snapshot,
            'urea_total_estimasi_tahunan' => $rekomendasi->urea_total_estimasi_tahunan,
            'kcl_total_estimasi_tahunan' => $rekomendasi->kcl_total_estimasi_tahunan,
            'urea_aplikasi_saat_ini' => $currentApp['urea_aplikasi_saat_ini'],
            'kcl_aplikasi_saat_ini' => $currentApp['kcl_aplikasi_saat_ini'],
            'urea_sisa_tahunan' => $currentApp['urea_sisa_tahunan'],
            'kcl_sisa_tahunan' => $currentApp['kcl_sisa_tahunan'],
            'active_stage' => $currentApp['active_stage'],
            'status_stage' => $currentApp['status_stage'],
        ];

        ksort($fingerprintData);

        return hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE));
    }
}
