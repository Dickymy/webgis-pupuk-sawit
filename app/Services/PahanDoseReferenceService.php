<?php

namespace App\Services;

use App\Models\BlokLahan;

/**
 * PahanDoseReferenceService — Mengembalikan rentang dosis Urea & MOP/KCl
 * berdasarkan fase dan umur tanaman sesuai Pahan (2013), Tabel 9.13 & 9.14.
 *
 * Service ini TIDAK menggunakan multiplier tanah/topografi/waktu.
 * Dosis ditentukan semata-mata dari tabel referensi + strategi estimasi.
 */
class PahanDoseReferenceService
{
    private PlantPhaseResolver $phaseResolver;

    public function __construct(PlantPhaseResolver $phaseResolver)
    {
        $this->phaseResolver = $phaseResolver;
    }

    /**
     * Dapatkan referensi dosis lengkap berdasarkan blok lahan.
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

        // Tentukan kelompok umur
        $ageGroup = $this->resolveAgeGroup($fase, $umur);

        if ($ageGroup === null) {
            $warnings[] = "Kombinasi fase {$fase} dan umur {$umur} tahun tidak ditemukan dalam tabel referensi.";
            return $this->emptyResult($strategy, $phaseInfo['needs_verification'], $warnings);
        }

        // Ambil dari config
        $doseTable = config('fertilization.dose_reference');
        $entry = $doseTable[$fase][$ageGroup] ?? null;

        if ($entry === null) {
            $warnings[] = "Entry dosis untuk {$fase}/{$ageGroup} tidak ditemukan di konfigurasi.";
            return $this->emptyResult($strategy, $phaseInfo['needs_verification'], $warnings);
        }

        $ureaEstimate = $this->calculateEstimate($entry['urea_min'], $entry['urea_max'], $strategy);
        $kclEstimate = $this->calculateEstimate($entry['kcl_min'], $entry['kcl_max'], $strategy);

        return [
            'phase'     => $fase,
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
            'needs_phase_verification' => $phaseInfo['needs_verification'],
            'warnings'  => $warnings,
        ];
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
