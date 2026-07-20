<?php

namespace App\Services;

use App\Models\BlokLahan;

/**
 * AnnualFertilizerSnapshotBuilder — Membangun snapshot kebutuhan tahunan & aplikasi saat ini.
 *
 * Rumus:
 *   jumlah pokok = luas × SPH
 *   total minimum = dosis minimum × jumlah pokok
 *   total maksimum = dosis maksimum × jumlah pokok
 *   total estimasi = dosis estimasi × jumlah pokok
 *   karung = ceil(total estimasi ÷ 50)
 *
 * Kebijakan pembulatan karung: selalu bulatkan ke atas (ceil)
 * agar ketersediaan di lapangan cukup. Sisa stok karung yang tidak
 * terpakai masih bisa digunakan pada aplikasi berikutnya.
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 163-164.
 */
class AnnualFertilizerSnapshotBuilder
{
    /**
     * Berat per karung pupuk standar (kg).
     */
    private const KG_PER_KARUNG = 50;

    /**
     * Bangun snapshot kebutuhan tahunan dan aplikasi saat ini.
     *
     * @param  array  $doseReference  Output dari PahanDoseReferenceService::getDoseReferenceForContext()
     * @param  bool  $isApplicable  Apakah aplikasi saat ini layak (dari FertilizationWindowService)
     * @return array{
     *   urea_total_min_tahunan: ?float,
     *   urea_total_max_tahunan: ?float,
     *   urea_total_estimasi_tahunan: ?float,
     *   kcl_total_min_tahunan: ?float,
     *   kcl_total_max_tahunan: ?float,
     *   kcl_total_estimasi_tahunan: ?float,
     *   urea_karung_estimasi_tahunan: ?int,
     *   kcl_karung_estimasi_tahunan: ?int,
     *   urea_aplikasi_saat_ini: float,
     *   kcl_aplikasi_saat_ini: float,
     *   jumlah_pokok: int
     * }
     */
    public function build(BlokLahan $blok, array $doseReference, bool $isApplicable): array
    {
        $jumlahPokok = (int) ($blok->luas_ha * $blok->sph);

        // Jika dosis referensi belum tersedia (fase belum ditentukan, dll)
        if ($doseReference['urea']['estimate'] === null || $doseReference['kcl']['estimate'] === null) {
            return [
                'urea_total_min_tahunan' => null,
                'urea_total_max_tahunan' => null,
                'urea_total_estimasi_tahunan' => null,
                'kcl_total_min_tahunan' => null,
                'kcl_total_max_tahunan' => null,
                'kcl_total_estimasi_tahunan' => null,
                'urea_karung_estimasi_tahunan' => null,
                'kcl_karung_estimasi_tahunan' => null,
                'urea_aplikasi_saat_ini' => 0.0,
                'kcl_aplikasi_saat_ini' => 0.0,
                'jumlah_pokok' => $jumlahPokok,
            ];
        }

        // Kebutuhan tahunan (selalu dihitung, terlepas dari kelayakan aplikasi)
        $ureaMinTahunan = round($doseReference['urea']['min'] * $jumlahPokok, 2);
        $ureaMaxTahunan = round($doseReference['urea']['max'] * $jumlahPokok, 2);
        $ureaEstTahunan = round($doseReference['urea']['estimate'] * $jumlahPokok, 2);

        $kclMinTahunan = round($doseReference['kcl']['min'] * $jumlahPokok, 2);
        $kclMaxTahunan = round($doseReference['kcl']['max'] * $jumlahPokok, 2);
        $kclEstTahunan = round($doseReference['kcl']['estimate'] * $jumlahPokok, 2);

        // Karung: selalu bulatkan ke atas (ceil) agar cukup di lapangan
        $ureaKarung = (int) ceil($ureaEstTahunan / self::KG_PER_KARUNG);
        $kclKarung = (int) ceil($kclEstTahunan / self::KG_PER_KARUNG);

        // Aplikasi saat ini: 0 jika tidak layak, estimasi tahunan jika layak
        $ureaAplikasiSaatIni = $isApplicable ? $ureaEstTahunan : 0.0;
        $kclAplikasiSaatIni = $isApplicable ? $kclEstTahunan : 0.0;

        return [
            'urea_total_min_tahunan' => $ureaMinTahunan,
            'urea_total_max_tahunan' => $ureaMaxTahunan,
            'urea_total_estimasi_tahunan' => $ureaEstTahunan,
            'kcl_total_min_tahunan' => $kclMinTahunan,
            'kcl_total_max_tahunan' => $kclMaxTahunan,
            'kcl_total_estimasi_tahunan' => $kclEstTahunan,
            'urea_karung_estimasi_tahunan' => $ureaKarung,
            'kcl_karung_estimasi_tahunan' => $kclKarung,
            'urea_aplikasi_saat_ini' => $ureaAplikasiSaatIni,
            'kcl_aplikasi_saat_ini' => $kclAplikasiSaatIni,
            'jumlah_pokok' => $jumlahPokok,
        ];
    }
}
