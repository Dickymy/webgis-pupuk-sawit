<?php

namespace App\Services;

use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;

/**
 * ObservationCompletenessService — Menentukan apakah data observasi
 * cukup untuk menjalankan diagnosis Rule-Based System.
 *
 * Pemisahan tanggung jawab:
 * - Perhitungan kebutuhan dasar (dosis tahunan Pahan) hanya butuh:
 *   luas, SPH, tahun tanam, fase.
 * - Diagnosis spesifik RBS membutuhkan data observasi minimum.
 */
class ObservationCompletenessService
{
    /**
     * Parameter penting untuk diagnosis RBS.
     */
    private const PARAMETER_PENTING = [
        'warna_daun' => 'Warna daun',
        'kondisi_drainase' => 'Kondisi drainase',
        'curah_hujan' => 'Data curah hujan',
        'kelembaban_tanah' => 'Kelembaban tanah',
        'musim_saat_ini' => 'Musim saat ini',
        'tanggal_pemupukan_terakhir' => 'Tanggal pemupukan terakhir',
    ];

    /**
     * Evaluasi kelengkapan data observasi.
     *
     * @return array{
     *   can_calculate_base_dose: bool,
     *   can_run_diagnosis: bool,
     *   filled_fields: array,
     *   missing_fields: array,
     *   blocking_missing_fields: array,
     *   filled_count: int,
     *   total_fields: int,
     *   reason: string
     * }
     */
    public function evaluate(KondisiLahan $kondisi): array
    {
        $filledFields = [];
        $missingFields = [];

        // Cek setiap parameter
        if ($kondisi->warna_daun !== null) {
            $filledFields[] = 'warna_daun';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['warna_daun'];
        }

        if ($kondisi->kondisi_drainase !== null) {
            $filledFields[] = 'kondisi_drainase';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['kondisi_drainase'];
        }

        // Curah hujan: numerik atau kategori
        $hasCurahHujan = $kondisi->curah_hujan_mm_bulanan !== null || $kondisi->curah_hujan_kategori !== null;
        if ($hasCurahHujan) {
            $filledFields[] = 'curah_hujan';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['curah_hujan'];
        }

        if ($kondisi->kelembaban_tanah !== null) {
            $filledFields[] = 'kelembaban_tanah';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['kelembaban_tanah'];
        }

        if ($kondisi->musim_saat_ini !== null) {
            $filledFields[] = 'musim_saat_ini';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['musim_saat_ini'];
        }

        $hasFertilizationHistory = $kondisi->tanggal_pemupukan_terakhir !== null
            || ($kondisi->blok_lahan_id !== null && RealisasiPemupukan::query()
                ->where('blok_lahan_id', $kondisi->blok_lahan_id)
                ->aktif()
                ->exists());
        if ($hasFertilizationHistory) {
            $filledFields[] = 'tanggal_pemupukan_terakhir';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['tanggal_pemupukan_terakhir'];
        }

        $filledCount = count($filledFields);
        $totalFields = count(self::PARAMETER_PENTING);

        // Rule diagnosis akademik yang aktif hanya membaca fakta visual warna daun.
        // Curah hujan, kelembapan, drainase, dan riwayat pemupukan dievaluasi
        // secara terpisah oleh FertilizationWindowService untuk menentukan waktu aplikasi.
        $hasWarnaDaun = in_array('warna_daun', $filledFields, true);
        $canRunDiagnosis = $hasWarnaDaun;
        $blockingMissingFields = $hasWarnaDaun ? [] : [self::PARAMETER_PENTING['warna_daun']];
        $supportingMissingFields = array_values(array_diff(
            $missingFields,
            [self::PARAMETER_PENTING['musim_saat_ini']]
        ));

        $reason = $canRunDiagnosis
            ? 'Fakta warna daun tersedia untuk pemeriksaan gejala. Data lingkungan tetap digunakan untuk menilai kesiapan pemupukan.'
            : 'Data warna daun belum diisi; aturan pemeriksaan gejala belum dapat dijalankan.';

        return [
            'can_calculate_base_dose' => true, // Selalu bisa jika blok punya data dasar
            'can_run_diagnosis' => $canRunDiagnosis,
            'filled_fields' => $filledFields,
            // Data pendukung yang belum tersedia tetap ditampilkan sebagai informasi,
            // tetapi tidak menghalangi rule visual.
            'missing_fields' => $supportingMissingFields,
            'blocking_missing_fields' => $blockingMissingFields,
            'filled_count' => $filledCount,
            'total_fields' => $totalFields,
            'reason' => $reason,
        ];
    }
}
