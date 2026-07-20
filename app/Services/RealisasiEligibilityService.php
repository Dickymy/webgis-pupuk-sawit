<?php

namespace App\Services;

use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;

/**
 * RealisasiEligibilityService — Validasi kelayakan pencatatan realisasi.
 *
 * Pahan v2.7:
 * Form realisasi hanya boleh dibuka jika status layak.
 * Rencana, tahap, dan tahun program dihitung server — bukan dari browser.
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 */
class RealisasiEligibilityService
{
    public function __construct(
        private FertilizationRealizationService $realizationService,
        private CurrentApplicationCalculator $currentAppCalculator,
        private FertilizationWindowService $windowService,
    ) {}

    /**
     * Status yang diizinkan untuk membuka form realisasi.
     */
    private const ALLOWED_STATUSES = [
        CurrentApplicationCalculator::TAHAP_1_SIAP,
        CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
        CurrentApplicationCalculator::TAHAP_2_SIAP,
    ];

    /**
     * Status yang ditolak beserta alasan penolakan.
     */
    private const REJECTION_REASONS = [
        CurrentApplicationCalculator::MENUNGGU_INTERVAL => 'Menunggu interval minimal 60 hari setelah Tahap 1 selesai.',
        CurrentApplicationCalculator::MENUNGGU_KELAYAKAN => 'Kondisi kelayakan aplikasi belum terpenuhi (curah hujan, drainase, atau interval).',
        CurrentApplicationCalculator::SELESAI_TAHUNAN => 'Kebutuhan tahunan telah terpenuhi. Tidak ada sisa pupuk untuk diaplikasikan.',
        CurrentApplicationCalculator::PERLU_VERIFIKASI_REALISASI => 'Data realisasi perlu diverifikasi sebelum pencatatan baru.',
    ];

    /**
     * Evaluasi kelayakan pencatatan realisasi.
     *
     * @return array{
     *   boleh_mencatat: bool,
     *   active_stage: int,
     *   status_stage: string,
     *   urea_rencana_kg: float,
     *   kcl_rencana_kg: float,
     *   tahun_program: int,
     *   program_pemupukan_id: ?int,
     *   reason: string,
     *   realization_summary: array,
     *   window_result: array,
     *   current_app: array,
     * }
     */
    public function evaluate(RekomendasiRbs $rekomendasi): array
    {
        $blok = $rekomendasi->blokLahan;

        // Tentukan program pemupukan aktif
        $tahunProgram = now()->year;
        $program = ProgramPemupukan::where('blok_lahan_id', $blok->id)
            ->where('tahun_program', $tahunProgram)
            ->where('status_program', ProgramPemupukan::STATUS_AKTIF)
            ->first();

        // Ambil ringkasan realisasi
        $realizationSummary = $this->realizationService->getRealizationSummary(
            $blok,
            $rekomendasi->id
        );

        // Evaluasi kelayakan waktu dari kondisi terbaru
        $kondisi = $blok->kondisiTerbaru;
        $windowResult = ['layak' => false, 'alasan' => ['Data kondisi belum tersedia.']];
        if ($kondisi) {
            $windowResult = $this->windowService->evaluate($kondisi);
        }

        // Hitung current application
        $annualSnapshot = [
            'urea_total_estimasi_tahunan' => $rekomendasi->urea_total_estimasi_tahunan,
            'kcl_total_estimasi_tahunan' => $rekomendasi->kcl_total_estimasi_tahunan,
        ];

        $currentApp = $this->currentAppCalculator->calculate([
            'annual_snapshot' => $annualSnapshot,
            'window_result' => $windowResult,
            'realization_summary' => $realizationSummary,
            'analysis_date' => now(),
        ]);

        $statusStage = $currentApp['status_stage'];
        $activeStage = $currentApp['active_stage'];
        $ureaRencana = $currentApp['urea_aplikasi_saat_ini'];
        $kclRencana = $currentApp['kcl_aplikasi_saat_ini'];

        // Cek apakah status diizinkan DAN aplikasi saat ini > 0
        $bolehMencatat = in_array($statusStage, self::ALLOWED_STATUSES)
            && ($ureaRencana > 0 || $kclRencana > 0);

        // Tentukan reason
        $reason = $bolehMencatat
            ? $currentApp['reason']
            : (self::REJECTION_REASONS[$statusStage] ?? $currentApp['reason']);

        return [
            'boleh_mencatat' => $bolehMencatat,
            'active_stage' => $activeStage,
            'status_stage' => $statusStage,
            'urea_rencana_kg' => $ureaRencana,
            'kcl_rencana_kg' => $kclRencana,
            'tahun_program' => $tahunProgram,
            'program_pemupukan_id' => $program?->id,
            'reason' => $reason,
            'realization_summary' => $realizationSummary,
            'window_result' => $windowResult,
            'current_app' => $currentApp,
        ];
    }
}
