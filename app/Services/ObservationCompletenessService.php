<?php

namespace App\Services;

use App\Models\KondisiLahan;

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
        'ph_tanah' => 'pH tanah',
        'kondisi_drainase' => 'Kondisi drainase',
        'curah_hujan' => 'Data curah hujan',
        'kelembaban_tanah' => 'Kelembaban tanah',
        'musim_saat_ini' => 'Musim saat ini',
        'tanggal_pemupukan_terakhir' => 'Tanggal pemupukan terakhir',
    ];

    /**
     * Minimum field yang harus terisi untuk menjalankan diagnosis.
     */
    private const MIN_FIELDS_REQUIRED = 5;

    /**
     * Evaluasi kelengkapan data observasi.
     *
     * @return array{
     *   can_calculate_base_dose: bool,
     *   can_run_diagnosis: bool,
     *   filled_fields: array,
     *   missing_fields: array,
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

        if ($kondisi->ph_tanah !== null) {
            $filledFields[] = 'ph_tanah';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['ph_tanah'];
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

        if ($kondisi->tanggal_pemupukan_terakhir !== null) {
            $filledFields[] = 'tanggal_pemupukan_terakhir';
        } else {
            $missingFields[] = self::PARAMETER_PENTING['tanggal_pemupukan_terakhir'];
        }

        $filledCount = count($filledFields);
        $totalFields = count(self::PARAMETER_PENTING);

        // Syarat minimum diagnosis:
        // 1. Minimal 5 dari 7 parameter terisi
        // 2. Warna daun WAJIB terisi
        // 3. pH tanah ATAU kondisi drainase WAJIB terisi (salah satu)
        $hasWarnaDaun = in_array('warna_daun', $filledFields);
        $hasPhOrDrainase = in_array('ph_tanah', $filledFields) || in_array('kondisi_drainase', $filledFields);

        $canRunDiagnosis = $filledCount >= self::MIN_FIELDS_REQUIRED
            && $hasWarnaDaun
            && $hasPhOrDrainase;

        // Alasan jika tidak bisa diagnosis
        $reason = '';
        if (! $canRunDiagnosis) {
            $reasons = [];
            if ($filledCount < self::MIN_FIELDS_REQUIRED) {
                $reasons[] = "Hanya {$filledCount} dari {$totalFields} parameter terisi (minimal ".self::MIN_FIELDS_REQUIRED.')';
            }
            if (! $hasWarnaDaun) {
                $reasons[] = 'Data warna daun belum diisi (wajib untuk diagnosis)';
            }
            if (! $hasPhOrDrainase) {
                $reasons[] = 'pH tanah atau kondisi drainase belum diisi (minimal salah satu wajib)';
            }
            $reason = implode('. ', $reasons).'.';
        } else {
            $reason = 'Data observasi cukup untuk menjalankan diagnosis RBS.';
        }

        return [
            'can_calculate_base_dose' => true, // Selalu bisa jika blok punya data dasar
            'can_run_diagnosis' => $canRunDiagnosis,
            'filled_fields' => $filledFields,
            'missing_fields' => $missingFields,
            'filled_count' => $filledCount,
            'total_fields' => $totalFields,
            'reason' => $reason,
        ];
    }
}
