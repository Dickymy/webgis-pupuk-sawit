<?php

namespace App\Services;

use App\Models\BlokLahan;

/**
 * PahanDoseReferenceService — Mengembalikan rentang dosis Urea & MOP/KCl
 * berdasarkan fase dan umur tanaman sesuai Pahan (2013), Tabel 9.13 & 9.14.
 *
 * PERBAIKAN v2.2:
 * - Menerima umur dan fase eksplisit (dari tanggal observasi)
 * - Tidak lagi mengandalkan $blok->umur_tanaman untuk analisis
 * - Backward compatible: getDoseReference() tanpa parameter tetap bekerja
 */
class PahanDoseReferenceService
{
    private PlantPhaseResolver $phaseResolver;

    public function __construct(PlantPhaseResolver $phaseResolver)
    {
        $this->phaseResolver = $phaseResolver;
    }

    /**
     * Dapatkan referensi dosis berdasarkan umur dan fase eksplisit.
     * Gunakan method ini untuk analisis dengan tanggal observasi tertentu.
     *
     * @param BlokLahan $blok
     * @param int $umurSaatObservasi Umur tanaman pada tanggal observasi
     * @param string $faseSaatObservasi Fase tanaman (TBM/TM) pada saat observasi
     * @return array
     */
    public function getDoseReferenceForContext(BlokLahan $blok, int $umurSaatObservasi, string $faseSaatObservasi): array
    {
        $strategy = config('fertilization.reference_dose_strategy', 'midpoint');
        $warnings = [];

        // Tentukan kelompok umur
        $ageGroup = $this->resolveAgeGroup($faseSaatObservasi, $umurSaatObservasi);

        if ($ageGroup === null) {
            $warnings[] = "Kombinasi fase {$faseSaatObservasi} dan umur {$umurSaatObservasi} tahun tidak ditemukan dalam tabel referensi.";
            return $this->emptyResult($strategy, false, $warnings);
        }

        // Ambil dari config
        $doseTable = config('fertilization.dose_reference');
        $entry = $doseTable[$faseSaatObservasi][$ageGroup] ?? null;

        if ($entry === null) {
            $warnings[] = "Entry dosis untuk {$faseSaatObservasi}/{$ageGroup} tidak ditemukan di konfigurasi.";
            return $this->emptyResult($strategy, false, $warnings);
        }

        $ureaEstimate = $this->calculateEstimate($entry['urea_min'], $entry['urea_max'], $strategy);
        $kclEstimate = $this->calculateEstimate($entry['kcl_min'], $entry['kcl_max'], $strategy);

        return [
            'phase'     => $faseSaatObservasi,
            'age_group' => $ageGroup,
            'age_label' => $entry['label'],
            'urea' => [
                'min'      => $entry['urea_min'],
                'max'      => $entry['urea_max'],
                'estimate' => $ureaEstimate,
            ],
            'kcl' => [
                'min'      => $entry['kcl_min'],
                'max'      => $entry['kcl_max'],
                'estimate' => $kclEstimate,
            ],
            'unit'      => 'kg/pokok/tahun',
            'strategy'  => $strategy,
            'reference' => config('fertilization.reference_source'),
            'needs_phase_verification' => false,
            'warnings'  => $warnings,
        ];
    }

    /**
     * Dapatkan referensi dosis lengkap berdasarkan blok lahan (kompatibilitas).
     * Menggunakan umur saat ini (dari accessor model).
     * Gunakan getDoseReferenceForContext() untuk analisis historis.
     *
     * @return array{
     *   phase: ?string,
     *   age_group: ?string,
     *   urea: array{min: float, max: float, estimate: float},
     *   kcl: array{min: float, max: float, estimate: float},
     *   unit: string,
     *   strategy: string,
     *   reference: array,
     *   needs_phase_verification: bool,
     *   warnings: array
     * }
     */
    public function getDoseReference(BlokLahan $blok): array
    {
        $phaseInfo = $this->phaseResolver->resolve($blok);
        $fase = $phaseInfo['fase'];
        $umur = $blok->umur_tanaman;
        $strategy = config('fertilization.reference_dose_strategy', 'midpoint');
        $warnings = [];

        if ($phaseInfo['needs_verification']) {
            $warnings[] = $phaseInfo['message'];
        }

        if ($fase === null || $umur === null) {
            return $this->emptyResult($strategy, $phaseInfo['needs_verification'], $warnings);
        }

        // Delegasi ke method konteks
        $result = $this->getDoseReferenceForContext($blok, $umur, $fase);

        // Merge warnings dan overwrite needs_phase_verification
        $result['warnings'] = array_merge($warnings, $result['warnings']);
        $result['needs_phase_verification'] = $phaseInfo['needs_verification'];

        return $result;
    }

    /**
     * Tentukan kelompok umur dalam tabel referensi.
     */
    private function resolveAgeGroup(string $fase, int $umur): ?string
    {
        if ($fase === 'TBM') {
            if ($umur <= 1) return '1';
            if ($umur === 2) return '2';
            if ($umur >= 3) return '3';
            return null;
        }

        // TM
        if ($umur >= 3 && $umur <= 5) return '3-5';
        if ($umur >= 6 && $umur <= 15) return '6-15';
        if ($umur >= 16) return '16+';

        return null;
    }

    /**
     * Hitung estimasi berdasarkan strategi.
     */
    private function calculateEstimate(float $min, float $max, string $strategy): float
    {
        return match ($strategy) {
            'minimum'  => $min,
            'maximum'  => $max,
            'midpoint' => round(($min + $max) / 2, 2),
            default    => round(($min + $max) / 2, 2),
        };
    }

    /**
     * Result kosong ketika data tidak mencukupi.
     */
    private function emptyResult(string $strategy, bool $needsVerification, array $warnings): array
    {
        return [
            'phase'     => null,
            'age_group' => null,
            'age_label' => null,
            'urea'      => ['min' => null, 'max' => null, 'estimate' => null],
            'kcl'       => ['min' => null, 'max' => null, 'estimate' => null],
            'unit'      => 'kg/pokok/tahun',
            'strategy'  => $strategy,
            'reference' => config('fertilization.reference_source'),
            'needs_phase_verification' => $needsVerification,
            'warnings'  => $warnings,
        ];
    }
}
