<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\RealisasiPemupukan;
use Carbon\Carbon;

/**
 * FertilizationRealizationService — Mengelola data realisasi pemupukan.
 *
 * Tanggung jawab:
 * 1. Mengambil realisasi untuk rekomendasi atau blok terkait
 * 2. Menentukan tahap realisasi
 * 3. Menjumlahkan realisasi Urea dan KCl
 * 4. Menyimpan tanggal realisasi
 * 5. Menentukan realisasi Tahap 1 sudah selesai atau belum
 * 6. Menghitung sisa kebutuhan tahunan
 * 7. Menentukan tanggal minimum Tahap 2
 * 8. Menangani realisasi kurang, tepat, atau lebih dari rencana
 * 9. Mencegah total melebihi kebutuhan tahunan tanpa konfirmasi
 * 10. Menjaga histori realisasi
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 */
class FertilizationRealizationService
{
    private const MIN_INTERVAL_DAYS = 60;

    /**
     * Ambil ringkasan realisasi untuk blok lahan tertentu pada tahun berjalan.
     *
     * @return array{
     *   tahap_1_selesai: bool,
     *   tahap_1_tanggal: ?string,
     *   total_urea_realisasi: float,
     *   total_kcl_realisasi: float,
     *   realisasi_tahap_1: array,
     *   realisasi_tahap_2: array,
     *   tanggal_minimum_tahap_2: ?string,
     *   interval_hari_sejak_tahap_1: ?int,
     *   interval_terpenuhi: bool
     * }
     */
    public function getRealizationSummary(BlokLahan $blok, ?int $rekomendasiRbsId = null, ?Carbon $analysisDate = null): array
    {
        $analysisDate = $analysisDate ?? now();
        $tahunBerjalan = $analysisDate->year;

        // Query realisasi untuk blok ini pada tahun berjalan
        $query = RealisasiPemupukan::where('blok_lahan_id', $blok->id)
            ->whereYear('tanggal_realisasi', $tahunBerjalan)
            ->where('status_realisasi', '!=', RealisasiPemupukan::STATUS_BATAL)
            ->orderBy('tahap')
            ->orderBy('tanggal_realisasi');

        if ($rekomendasiRbsId) {
            $query->where('rekomendasi_rbs_id', $rekomendasiRbsId);
        }

        $realisasis = $query->get();

        // Pisahkan per tahap
        $tahap1 = $realisasis->where('tahap', 1);
        $tahap2 = $realisasis->where('tahap', 2);

        // Hitung total realisasi
        $totalUrea = $realisasis->sum('urea_realisasi_kg');
        $totalKcl = $realisasis->sum('kcl_realisasi_kg');

        // Tentukan status tahap 1
        $tahap1Selesai = $tahap1->isNotEmpty();
        $tahap1Tanggal = $tahap1->max('tanggal_realisasi');

        // Hitung interval dan tanggal minimum tahap 2
        $tanggalMinTahap2 = null;
        $intervalHari = null;
        $intervalTerpenuhi = false;

        if ($tahap1Tanggal) {
            $tglTahap1 = Carbon::parse($tahap1Tanggal);
            $tanggalMinTahap2 = $tglTahap1->copy()->addDays(self::MIN_INTERVAL_DAYS)->toDateString();
            $intervalHari = (int) $tglTahap1->diffInDays($analysisDate);
            $intervalTerpenuhi = $intervalHari >= self::MIN_INTERVAL_DAYS;
        }

        return [
            'tahap_1_selesai' => $tahap1Selesai,
            'tahap_1_tanggal' => $tahap1Tanggal?->toDateString() ?? ($tahap1Tanggal ? (string) $tahap1Tanggal : null),
            'total_urea_realisasi' => round((float) $totalUrea, 2),
            'total_kcl_realisasi' => round((float) $totalKcl, 2),
            'urea_realisasi_tahap_1' => round((float) $tahap1->sum('urea_realisasi_kg'), 2),
            'kcl_realisasi_tahap_1' => round((float) $tahap1->sum('kcl_realisasi_kg'), 2),
            'urea_realisasi_tahap_2' => round((float) $tahap2->sum('urea_realisasi_kg'), 2),
            'kcl_realisasi_tahap_2' => round((float) $tahap2->sum('kcl_realisasi_kg'), 2),
            'realisasi_tahap_1' => $tahap1->toArray(),
            'realisasi_tahap_2' => $tahap2->toArray(),
            'tanggal_minimum_tahap_2' => $tanggalMinTahap2,
            'interval_hari_sejak_tahap_1' => $intervalHari,
            'interval_terpenuhi' => $intervalTerpenuhi,
        ];
    }

    /**
     * Hitung sisa kebutuhan tahunan setelah realisasi.
     *
     * @param  float  $totalEstimasiTahunanUrea  Kebutuhan tahunan Urea
     * @param  float  $totalEstimasiTahunanKcl  Kebutuhan tahunan KCl
     * @param  array  $realizationSummary  Output dari getRealizationSummary()
     */
    public function calculateRemaining(
        float $totalEstimasiTahunanUrea,
        float $totalEstimasiTahunanKcl,
        array $realizationSummary
    ): array {
        $sisaUrea = max(0, $totalEstimasiTahunanUrea - $realizationSummary['total_urea_realisasi']);
        $sisaKcl = max(0, $totalEstimasiTahunanKcl - $realizationSummary['total_kcl_realisasi']);

        $kebutuhanTerpenuhi = ($sisaUrea <= 0 && $sisaKcl <= 0);

        return [
            'urea_sisa_tahunan' => round($sisaUrea, 2),
            'kcl_sisa_tahunan' => round($sisaKcl, 2),
            'kebutuhan_tahunan_terpenuhi' => $kebutuhanTerpenuhi,
            'persentase_urea_terpenuhi' => $totalEstimasiTahunanUrea > 0
                ? round(($realizationSummary['total_urea_realisasi'] / $totalEstimasiTahunanUrea) * 100, 1)
                : 0,
            'persentase_kcl_terpenuhi' => $totalEstimasiTahunanKcl > 0
                ? round(($realizationSummary['total_kcl_realisasi'] / $totalEstimasiTahunanKcl) * 100, 1)
                : 0,
        ];
    }

    /**
     * Cek apakah total realisasi melebihi kebutuhan tahunan.
     */
    public function exceedsAnnualRequirement(
        float $totalEstimasiTahunanUrea,
        float $totalEstimasiTahunanKcl,
        array $realizationSummary
    ): bool {
        return $realizationSummary['total_urea_realisasi'] > $totalEstimasiTahunanUrea
            || $realizationSummary['total_kcl_realisasi'] > $totalEstimasiTahunanKcl;
    }
}
