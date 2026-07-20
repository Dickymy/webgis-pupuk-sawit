<?php

namespace App\Services;

/**
 * CurrentApplicationCalculator — Menghitung jumlah pupuk tahap aktif saat ini.
 *
 * Pisahkan tanggung jawab:
 * - AnnualFertilizerSnapshotBuilder → kebutuhan tahunan
 * - CurrentApplicationCalculator → jumlah tahap aktif saat ini
 *
 * Aturan:
 * 1. Belum ada realisasi Tahap 1 dan layak → aplikasi saat ini = 50% kebutuhan tahunan
 * 2. Tidak layak → aplikasi saat ini = 0
 * 3. Tahap 1 sudah direalisasikan, Tahap 2 belum layak → aplikasi saat ini = 0
 * 4. Tahap 1 sudah direalisasikan, interval 60 hari terpenuhi, hujan layak,
 *    Tahap 2 belum selesai → aplikasi saat ini = sisa kebutuhan tahunan
 * 5. Kebutuhan tahunan terpenuhi → aplikasi saat ini = 0, status = SELESAI_TAHUNAN
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 */
class CurrentApplicationCalculator
{
    // Status tahap
    public const TAHAP_1_SIAP = 'TAHAP_1_SIAP';

    public const TAHAP_1_SEBAGIAN = 'TAHAP_1_SEBAGIAN';

    public const MENUNGGU_INTERVAL = 'MENUNGGU_INTERVAL';

    public const MENUNGGU_KELAYAKAN = 'MENUNGGU_KELAYAKAN';

    public const TAHAP_2_SIAP = 'TAHAP_2_SIAP';

    public const SELESAI_TAHUNAN = 'SELESAI_TAHUNAN';

    public const PERLU_VERIFIKASI_REALISASI = 'PERLU_VERIFIKASI_REALISASI';

    private const SPLIT_RATIO = 0.50; // 50% per tahap

    /**
     * Hitung aplikasi saat ini berdasarkan konteks.
     *
     * @param  array  $input  {
     *                        annual_snapshot: array dari AnnualFertilizerSnapshotBuilder,
     *                        window_result: array dari FertilizationWindowService::evaluate(),
     *                        realization_summary: array dari FertilizationRealizationService::getRealizationSummary(),
     *                        analysis_date: Carbon|string
     *                        }
     * @return array{
     *   active_stage: int,
     *   status_stage: string,
     *   urea_aplikasi_saat_ini: float,
     *   kcl_aplikasi_saat_ini: float,
     *   urea_sisa_tahunan: float,
     *   kcl_sisa_tahunan: float,
     *   tanggal_minimum_tahap_berikutnya: ?string,
     *   reason: string
     * }
     */
    public function calculate(array $input): array
    {
        $annualSnapshot = $input['annual_snapshot'] ?? [];
        $windowResult = $input['window_result'] ?? [];
        $realizationSummary = $input['realization_summary'] ?? [];

        $totalUreaTahunan = (float) ($annualSnapshot['urea_total_estimasi_tahunan'] ?? 0);
        $totalKclTahunan = (float) ($annualSnapshot['kcl_total_estimasi_tahunan'] ?? 0);
        $isApplicable = (bool) ($windowResult['layak'] ?? false);

        // Data realisasi
        $tahap1Selesai = (bool) ($realizationSummary['tahap_1_selesai'] ?? false);
        $totalUreaRealisasi = (float) ($realizationSummary['total_urea_realisasi'] ?? 0);
        $totalKclRealisasi = (float) ($realizationSummary['total_kcl_realisasi'] ?? 0);
        $intervalTerpenuhi = (bool) ($realizationSummary['interval_terpenuhi'] ?? false);
        $tanggalMinTahap2 = $realizationSummary['tanggal_minimum_tahap_2'] ?? null;

        // Hitung sisa tahunan
        $sisaUrea = max(0, round($totalUreaTahunan - $totalUreaRealisasi, 2));
        $sisaKcl = max(0, round($totalKclTahunan - $totalKclRealisasi, 2));

        // Jika kebutuhan tahunan belum ada (data tidak lengkap)
        if ($totalUreaTahunan <= 0 && $totalKclTahunan <= 0) {
            return $this->buildResult(0, self::PERLU_VERIFIKASI_REALISASI, 0, 0, 0, 0, null,
                'Kebutuhan tahunan belum ditentukan.');
        }

        // KASUS 5: Kebutuhan tahunan sudah terpenuhi
        if ($sisaUrea <= 0 && $sisaKcl <= 0) {
            return $this->buildResult(0, self::SELESAI_TAHUNAN, 0, 0, 0, 0, null,
                'Kebutuhan tahunan telah terpenuhi berdasarkan realisasi yang tercatat.');
        }

        // KASUS 2: Tidak layak
        if (! $isApplicable) {
            $stage = $tahap1Selesai ? 2 : 1;
            $statusStage = $tahap1Selesai ? self::MENUNGGU_KELAYAKAN : self::MENUNGGU_KELAYAKAN;

            return $this->buildResult($stage, $statusStage, 0, 0, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
                'Pemupukan ditunda karena kondisi kelayakan belum terpenuhi.');
        }

        // KASUS 1: Belum ada realisasi Tahap 1, layak → 50% kebutuhan tahunan
        if (! $tahap1Selesai) {
            $ureaAplikasi = round($totalUreaTahunan * self::SPLIT_RATIO, 2);
            $kclAplikasi = round($totalKclTahunan * self::SPLIT_RATIO, 2);

            return $this->buildResult(1, self::TAHAP_1_SIAP, $ureaAplikasi, $kclAplikasi, $sisaUrea, $sisaKcl, null,
                'Tahap 1 siap diaplikasikan (50% kebutuhan tahunan).');
        }

        // KASUS 3: Tahap 1 sudah direalisasikan, interval belum 60 hari
        if (! $intervalTerpenuhi) {
            return $this->buildResult(2, self::MENUNGGU_INTERVAL, 0, 0, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
                'Menunggu interval minimal 60 hari setelah realisasi Tahap 1.');
        }

        // KASUS 4: Tahap 1 sudah direalisasikan, interval terpenuhi, layak → sisa aktual
        return $this->buildResult(2, self::TAHAP_2_SIAP, $sisaUrea, $sisaKcl, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
            'Tahap 2 siap diaplikasikan (sisa kebutuhan tahunan setelah realisasi Tahap 1).');
    }

    /**
     * Build standardized result array.
     */
    private function buildResult(
        int $activeStage,
        string $statusStage,
        float $ureaAplikasi,
        float $kclAplikasi,
        float $sisaUrea,
        float $sisaKcl,
        ?string $tanggalMinTahapBerikutnya,
        string $reason
    ): array {
        return [
            'active_stage' => $activeStage,
            'status_stage' => $statusStage,
            'urea_aplikasi_saat_ini' => $ureaAplikasi,
            'kcl_aplikasi_saat_ini' => $kclAplikasi,
            'urea_sisa_tahunan' => $sisaUrea,
            'kcl_sisa_tahunan' => $sisaKcl,
            'tanggal_minimum_tahap_berikutnya' => $tanggalMinTahapBerikutnya,
            'reason' => $reason,
        ];
    }

    /**
     * Label lengkap status tahap untuk ditampilkan ke pengguna.
     */
    public static function labelStatusStage(string $status): string
    {
        return match ($status) {
            self::TAHAP_1_SIAP => 'Tahap 1 Siap Diaplikasikan',
            self::TAHAP_1_SEBAGIAN => 'Tahap 1 Direalisasikan Sebagian',
            self::MENUNGGU_INTERVAL => 'Menunggu Interval Minimal 60 Hari',
            self::MENUNGGU_KELAYAKAN => 'Menunggu Kelayakan Aplikasi',
            self::TAHAP_2_SIAP => 'Tahap 2 Siap Diaplikasikan',
            self::SELESAI_TAHUNAN => 'Kebutuhan Tahunan Telah Terpenuhi',
            self::PERLU_VERIFIKASI_REALISASI => 'Perlu Verifikasi Data Realisasi',
            default => $status,
        };
    }
}
