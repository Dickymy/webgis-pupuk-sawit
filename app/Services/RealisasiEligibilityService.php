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
 * Waktu aplikasi mengacu pada PPKS (2021; 2025); interval tahap adalah adaptasi operasional sistem.
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
        CurrentApplicationCalculator::MENUNGGU_INTERVAL => 'Tahap berikutnya belum dapat dicatat karena jarak waktu minimum pada jadwal belum terpenuhi.',
        CurrentApplicationCalculator::MENUNGGU_KELAYAKAN => 'Kondisi lapangan belum mendukung pemupukan (curah hujan, saluran air, atau jarak waktu).',
        CurrentApplicationCalculator::SELESAI_TAHUNAN => 'Kebutuhan tahunan telah terpenuhi. Tidak ada sisa pupuk untuk diaplikasikan.',
        CurrentApplicationCalculator::PERLU_VERIFIKASI_REALISASI => 'Data realisasi perlu diverifikasi sebelum pencatatan baru.',
    ];

    /**
     * Evaluasi kelayakan pencatatan realisasi.
     *
     * Pahan v2.8:
     * - Tolak rekomendasi historis (is_latest = false)
     * - Tolak jika program bukan AKTIF
     * - Tolak jika rekomendasi tidak terhubung ke program aktif
     * - Gunakan program.tahun_program bukan now()->year secara buta
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

        // Pahan v2.8: Tolak rekomendasi historis
        if (! $rekomendasi->is_latest) {
            return $this->reject(
                'Realisasi tidak dapat dicatat dari rekomendasi historis. Gunakan rekomendasi terbaru pada program aktif.'
            );
        }

        $dataBelumCukup = in_array($rekomendasi->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
            || $rekomendasi->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA';

        if ($dataBelumCukup) {
            $result = $this->reject(
                'Data observasi belum cukup untuk realisasi. Lengkapi observasi lalu hitung ulang rekomendasi.'
            );
            $result['status_stage'] = CurrentApplicationCalculator::MENUNGGU_KELAYAKAN;
            $result['active_stage'] = $rekomendasi->active_stage ?? 1;

            return $result;
        }

        // Pahan v2.8: Tentukan program pemupukan aktif
        // Gunakan program dari rekomendasi jika sudah terhubung
        $program = null;
        if ($rekomendasi->program_pemupukan_id) {
            $program = ProgramPemupukan::find($rekomendasi->program_pemupukan_id);
            if ($program && ! $program->isAktif()) {
                return $this->reject(
                    'Program pemupukan terkait rekomendasi ini sudah berstatus '
                    .$program->label_status.'. Gunakan program aktif.'
                );
            }
        }

        // Jika rekomendasi belum punya program, cari program aktif untuk blok
        if (! $program) {
            $tahunProgram = now()->year;
            $program = ProgramPemupukan::where('blok_lahan_id', $blok->id)
                ->where('tahun_program', $tahunProgram)
                ->where('status_program', ProgramPemupukan::STATUS_AKTIF)
                ->first();
        }

        $tahunProgram = $program ? $program->tahun_program : now()->year;

        // Ambil ringkasan realisasi berbasis program (Pahan v2.8)
        $realizationSummary = $program
            ? $this->realizationService->getRealizationSummaryForProgram($program)
            : $this->realizationService->getRealizationSummary($blok, $rekomendasi->id);

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

    /**
     * Buat response penolakan standar.
     */
    private function reject(string $reason): array
    {
        return [
            'boleh_mencatat' => false,
            'active_stage' => 0,
            'status_stage' => '',
            'urea_rencana_kg' => 0,
            'kcl_rencana_kg' => 0,
            'tahun_program' => now()->year,
            'program_pemupukan_id' => null,
            'reason' => $reason,
            'realization_summary' => [],
            'window_result' => ['layak' => false, 'alasan' => [$reason]],
            'current_app' => [],
        ];
    }
}
