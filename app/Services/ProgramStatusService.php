<?php

namespace App\Services;

use App\Models\ProgramPemupukan;
use App\Models\RekomendasiOperasionalHistory;
use App\Models\RekomendasiRbs;

/**
 * ProgramStatusService — Kelola siklus hidup program pemupukan.
 *
 * Pahan v2.8:
 * - Sinkronisasi status program berdasarkan sisa kebutuhan tahunan
 * - Program otomatis SELESAI jika urea dan KCl sisa = 0
 * - Catat histori PROGRAM_SELESAI
 * - Pembukaan kembali hanya melalui aksi eksplisit admin
 */
class ProgramStatusService
{
    /**
     * Sinkronisasi status program berdasarkan currentApp result.
     */
    public function synchronizeStatus(
        ProgramPemupukan $program,
        array $currentApplication
    ): void {
        $sisaUrea = (float) ($currentApplication['urea_sisa_tahunan'] ?? 0);
        $sisaKcl = (float) ($currentApplication['kcl_sisa_tahunan'] ?? 0);

        // Kebutuhan tahunan terpenuhi → SELESAI
        if ($sisaUrea <= 0 && $sisaKcl <= 0 && $program->isAktif()) {
            $program->update([
                'status_program' => ProgramPemupukan::STATUS_SELESAI,
                'active_key' => null,
            ]);

            // Catat histori PROGRAM_SELESAI
            $rekomendasi = $this->getLatestRekomendasi($program);
            if ($rekomendasi) {
                RekomendasiOperasionalHistory::create([
                    'rekomendasi_rbs_id' => $rekomendasi->id,
                    'program_pemupukan_id' => $program->id,
                    'event_type' => RekomendasiOperasionalHistory::PROGRAM_SELESAI,
                    'active_stage' => (int) ($currentApplication['active_stage'] ?? 0),
                    'status_stage' => $currentApplication['status_stage'] ?? null,
                    'urea_aplikasi_saat_ini' => 0,
                    'kcl_aplikasi_saat_ini' => 0,
                    'urea_sisa_tahunan' => 0,
                    'kcl_sisa_tahunan' => 0,
                    'tanggal_minimum_tahap_berikutnya' => null,
                    'alasan_tahap' => 'Program pemupukan tahun ini telah selesai.',
                    'analysis_fingerprint' => $rekomendasi->analysis_fingerprint,
                    'source_realisasi_id' => null,
                    'created_at' => now(),
                ]);
            }

            return;
        }

        // Masih ada sisa → pastikan AKTIF (jika belum SELESAI manual)
        // TIDAK membuka kembali otomatis — hanya admin yang bisa
    }

    /**
     * Ambil rekomendasi terbaru yang terhubung ke program.
     */
    private function getLatestRekomendasi(ProgramPemupukan $program): ?RekomendasiRbs
    {
        return RekomendasiRbs::where('program_pemupukan_id', $program->id)
            ->where('is_latest', true)
            ->first()
            ?? RekomendasiRbs::where('blok_lahan_id', $program->blok_lahan_id)
                ->where('is_latest', true)
                ->first();
    }
}
