<?php

namespace App\Services;

/**
 * CurrentApplicationCalculator — Menghitung jumlah pupuk tahap aktif saat ini.
 *
 * Pahan v2.6 Perubahan:
 * - TAHAP_1_SEBAGIAN: jika realisasi Tahap 1 sudah sebagian, aplikasi saat ini = sisa rencana Tahap 1
 * - Urea dan KCl dievaluasi secara independen
 * - Tidak pindah ke Tahap 2 jika salah satu pupuk belum terpenuhi
 *
 * Aturan:
 * 1. Belum ada realisasi Tahap 1 dan layak → aplikasi saat ini = 50% kebutuhan tahunan (TAHAP_1_SIAP)
 * 2. Tidak layak → aplikasi saat ini = 0 (MENUNGGU_KELAYAKAN)
 * 3. Tahap 1 sebagian → aplikasi saat ini = sisa rencana Tahap 1 (TAHAP_1_SEBAGIAN)
 * 4. Tahap 1 selesai, interval operasional belum terpenuhi → 0 (MENUNGGU_INTERVAL)
 * 5. Tahap 1 selesai, interval terpenuhi, layak → sisa tahunan (TAHAP_2_SIAP)
 * 6. Kebutuhan tahunan terpenuhi → 0 (SELESAI_TAHUNAN)
 *
 * Frekuensi 2-3 aplikasi/tahun mengacu PPKS (2021); pembagian dua tahap adalah adaptasi desain penelitian.
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

    private const SPLIT_RATIO = 0.50; // Adaptasi operasional: dua tahap sama besar, tidak mengubah dosis tahunan.

    /**
     * Hitung aplikasi saat ini berdasarkan konteks.
     *
     * @param  array  $input  {
     *                        annual_snapshot: array dari AnnualFertilizerSnapshotBuilder,
     *                        window_result: array dari FertilizationWindowService::evaluate(),
     *                        realization_summary: array dari FertilizationRealizationService::getRealizationSummary(),
     *                        analysis_date: Carbon|string
     *                        }
     */
    public function calculate(array $input): array
    {
        $annualSnapshot = $input['annual_snapshot'] ?? [];
        $windowResult = $input['window_result'] ?? [];
        $realizationSummary = $input['realization_summary'] ?? [];

        $totalUreaTahunan = (float) ($annualSnapshot['urea_total_estimasi_tahunan'] ?? 0);
        $totalKclTahunan = (float) ($annualSnapshot['kcl_total_estimasi_tahunan'] ?? 0);
        $isApplicable = (bool) ($windowResult['layak'] ?? false);

        // Data realisasi (Pahan v2.6: gunakan status detail)
        $tahap1Selesai = (bool) ($realizationSummary['tahap_1_selesai'] ?? false);
        $tahap1Sebagian = (bool) ($realizationSummary['tahap_1_sebagian'] ?? false);
        $tahap1Ada = (bool) ($realizationSummary['tahap_1_ada'] ?? false);
        $totalUreaRealisasi = (float) ($realizationSummary['total_urea_realisasi'] ?? 0);
        $totalKclRealisasi = (float) ($realizationSummary['total_kcl_realisasi'] ?? 0);
        $intervalTerpenuhi = (bool) ($realizationSummary['interval_terpenuhi'] ?? false);
        $tanggalMinTahap2 = $realizationSummary['tanggal_minimum_tahap_2'] ?? null;

        // Rencana dan realisasi Tahap 1
        $ureaRencanaTahap1 = (float) ($realizationSummary['urea_rencana_tahap_1'] ?? 0);
        $kclRencanaTahap1 = (float) ($realizationSummary['kcl_rencana_tahap_1'] ?? 0);
        $ureaRealisasiTahap1 = (float) ($realizationSummary['urea_realisasi_tahap_1'] ?? 0);
        $kclRealisasiTahap1 = (float) ($realizationSummary['kcl_realisasi_tahap_1'] ?? 0);

        // Hitung sisa tahunan
        $sisaUrea = max(0, round($totalUreaTahunan - $totalUreaRealisasi, 2));
        $sisaKcl = max(0, round($totalKclTahunan - $totalKclRealisasi, 2));

        // Jika kebutuhan tahunan belum ada (data tidak lengkap)
        if ($totalUreaTahunan <= 0 && $totalKclTahunan <= 0) {
            return $this->buildResult(0, self::PERLU_VERIFIKASI_REALISASI, 0, 0, 0, 0, null,
                'Kebutuhan tahunan belum ditentukan.');
        }

        // KASUS 6: Kebutuhan tahunan sudah terpenuhi
        if ($sisaUrea <= 0 && $sisaKcl <= 0) {
            return $this->buildResult(0, self::SELESAI_TAHUNAN, 0, 0, 0, 0, null,
                'Kebutuhan tahunan telah terpenuhi berdasarkan realisasi yang tercatat.');
        }

        // KASUS 2: Tidak layak
        if (! $isApplicable) {
            $stage = $tahap1Selesai ? 2 : 1;
            $statusStage = self::MENUNGGU_KELAYAKAN;

            return $this->buildResult($stage, $statusStage, 0, 0, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
                'Blok belum siap dipupuk karena kondisi lapangan belum memenuhi syarat.');
        }

        // KASUS 3: Tahap 1 ada tapi SEBAGIAN (belum selesai) → sisa rencana Tahap 1
        if ($tahap1Sebagian) {
            // Hitung sisa rencana Tahap 1 (belum terpenuhi)
            $rencanaTahap1Urea = $ureaRencanaTahap1 > 0 ? $ureaRencanaTahap1 : round($totalUreaTahunan * self::SPLIT_RATIO, 2);
            $rencanaTahap1Kcl = $kclRencanaTahap1 > 0 ? $kclRencanaTahap1 : round($totalKclTahunan * self::SPLIT_RATIO, 2);

            $sisaUreaTahap1 = max(0, round($rencanaTahap1Urea - $ureaRealisasiTahap1, 2));
            $sisaKclTahap1 = max(0, round($rencanaTahap1Kcl - $kclRealisasiTahap1, 2));

            return $this->buildResult(1, self::TAHAP_1_SEBAGIAN, $sisaUreaTahap1, $sisaKclTahap1, $sisaUrea, $sisaKcl, null,
                'Tahap 1 baru dilakukan sebagian. Sisa rencana Tahap 1 masih perlu dipupuk.');
        }

        // KASUS 1: Belum ada realisasi Tahap 1, layak → 50% kebutuhan tahunan
        if (! $tahap1Ada && ! $tahap1Selesai) {
            $ureaAplikasi = round($totalUreaTahunan * self::SPLIT_RATIO, 2);
            $kclAplikasi = round($totalKclTahunan * self::SPLIT_RATIO, 2);

            return $this->buildResult(1, self::TAHAP_1_SIAP, $ureaAplikasi, $kclAplikasi, $sisaUrea, $sisaKcl, null,
                'Tahap 1 siap dipupuk (50% kebutuhan tahunan).');
        }

        // KASUS 4: Tahap 1 sudah direalisasikan selesai, interval operasional belum terpenuhi
        if ($tahap1Selesai && ! $intervalTerpenuhi) {
            return $this->buildResult(2, self::MENUNGGU_INTERVAL, 0, 0, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
                'Menunggu jarak waktu minimum '.config('fertilization.window.min_interval_days', 120).' hari setelah realisasi Tahap 1.');
        }

        // KASUS 5: Tahap 1 selesai, interval terpenuhi, layak → sisa aktual
        return $this->buildResult(2, self::TAHAP_2_SIAP, $sisaUrea, $sisaKcl, $sisaUrea, $sisaKcl, $tanggalMinTahap2,
            'Tahap 2 siap dipupuk (sisa kebutuhan tahunan setelah realisasi Tahap 1).');
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
    public static function labelStatusStage(?string $status): string
    {
        return match ($status) {
            self::TAHAP_1_SIAP => 'Tahap 1 Siap Dipupuk',
            self::TAHAP_1_SEBAGIAN => 'Tahap 1 Sudah Dicatat Sebagian',
            self::MENUNGGU_INTERVAL => 'Menunggu Jarak Waktu '.config('fertilization.window.min_interval_days', 120).' Hari',
            self::MENUNGGU_KELAYAKAN => 'Menunggu Kondisi Lapangan Mendukung',
            self::TAHAP_2_SIAP => 'Tahap 2 Siap Dipupuk',
            self::SELESAI_TAHUNAN => 'Program Pemupukan Tahun Ini Selesai',
            self::PERLU_VERIFIKASI_REALISASI => 'Periksa Catatan Pelaksanaan',
            default => $status ?? '-',
        };
    }

    /**
     * Warna badge status tahap.
     */
    public static function warnaStatusStage(?string $status): string
    {
        return match ($status) {
            self::TAHAP_1_SIAP => 'emerald',
            self::TAHAP_1_SEBAGIAN => 'amber',
            self::MENUNGGU_INTERVAL => 'blue',
            self::MENUNGGU_KELAYAKAN => 'amber',
            self::TAHAP_2_SIAP => 'emerald',
            self::SELESAI_TAHUNAN => 'green',
            self::PERLU_VERIFIKASI_REALISASI => 'red',
            default => 'slate',
        };
    }
}
