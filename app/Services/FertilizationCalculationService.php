<?php

namespace App\Services;

use App\Models\BlokLahan;

/**
 * FertilizationCalculationService — Menghitung total kebutuhan pupuk per blok.
 *
 * Rumus:
 *   jumlah_pokok = luas_ha × SPH
 *   total_min    = dosis_min × jumlah_pokok
 *   total_max    = dosis_max × jumlah_pokok
 *   total_est    = dosis_estimasi × jumlah_pokok
 *   karung_est   = total_est / 50
 */
class FertilizationCalculationService
{
    /**
     * Hitung total kebutuhan pupuk berdasarkan referensi dosis.
     *
     * @param array $doseReference Output dari PahanDoseReferenceService::getDoseReference()
     */
    public function calculate(BlokLahan $blok, array $doseReference): array
    {
        $jumlahPokok = $blok->luas_ha * $blok->sph;

        $urea = $doseReference['urea'];
        $kcl = $doseReference['kcl'];

        if ($urea['estimate'] === null || $kcl['estimate'] === null) {
            return [
                'jumlah_pokok'  => (int) $jumlahPokok,
                'urea' => [
                    'min_total'   => null,
                    'max_total'   => null,
                    'est_total'   => null,
                    'karung_est'  => null,
                    'karung_bulat' => null,
                    'per_tahap'   => null,
                ],
                'kcl' => [
                    'min_total'   => null,
                    'max_total'   => null,
                    'est_total'   => null,
                    'karung_est'  => null,
                    'karung_bulat' => null,
                    'per_tahap'   => null,
                ],
            ];
        }

        // Total kebutuhan (kg/tahun)
        $ureaMinTotal = $urea['min'] * $jumlahPokok;
        $ureaMaxTotal = $urea['max'] * $jumlahPokok;
        $ureaEstTotal = $urea['estimate'] * $jumlahPokok;

        $kclMinTotal = $kcl['min'] * $jumlahPokok;
        $kclMaxTotal = $kcl['max'] * $jumlahPokok;
        $kclEstTotal = $kcl['estimate'] * $jumlahPokok;

        // Karung (50 kg)
        $ureaKarungEst  = $ureaEstTotal / 50;
        $ureaKarungBulat = (int) ceil($ureaEstTotal / 50);

        $kclKarungEst  = $kclEstTotal / 50;
        $kclKarungBulat = (int) ceil($kclEstTotal / 50);

        // Per tahap (2 aplikasi/tahun)
        $ureaPerTahap = $urea['estimate'] / 2;
        $kclPerTahap  = $kcl['estimate'] / 2;

        return [
            'jumlah_pokok' => (int) $jumlahPokok,
            'urea' => [
                'dosis_per_pokok' => $urea['estimate'],
                'min_total'       => round($ureaMinTotal, 2),
                'max_total'       => round($ureaMaxTotal, 2),
                'est_total'       => round($ureaEstTotal, 2),
                'karung_est'      => round($ureaKarungEst, 2),
                'karung_bulat'    => $ureaKarungBulat,
                'per_tahap'       => round($ureaPerTahap, 3),
            ],
            'kcl' => [
                'dosis_per_pokok' => $kcl['estimate'],
                'min_total'       => round($kclMinTotal, 2),
                'max_total'       => round($kclMaxTotal, 2),
                'est_total'       => round($kclEstTotal, 2),
                'karung_est'      => round($kclKarungEst, 2),
                'karung_bulat'    => $kclKarungBulat,
                'per_tahap'       => round($kclPerTahap, 3),
            ],
        ];
    }
}
