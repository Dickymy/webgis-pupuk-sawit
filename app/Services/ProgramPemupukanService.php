<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ProgramPemupukanService — Resolve dan kelola program pemupukan tahunan.
 *
 * Pahan v2.8:
 * - Satu blok hanya boleh memiliki satu program aktif per tahun (dijamin via active_key UNIQUE)
 * - Digunakan oleh RbsService dan RealisasiPemupukanController
 * - Menggunakan lockForUpdate() untuk mencegah race condition
 */
class ProgramPemupukanService
{
    /**
     * Resolve program aktif untuk blok dan tahun tertentu.
     * Jika belum ada, buat program baru dalam transaksi dengan lock.
     */
    public function resolveActiveProgram(
        BlokLahan $blok,
        int $tahunProgram,
        ?RekomendasiRbs $rekomendasi = null
    ): ProgramPemupukan {
        return DB::transaction(function () use ($blok, $tahunProgram, $rekomendasi) {
            // Lock row yang relevan untuk mencegah program aktif ganda
            $existing = ProgramPemupukan::where('blok_lahan_id', $blok->id)
                ->where('tahun_program', $tahunProgram)
                ->where('status_program', ProgramPemupukan::STATUS_AKTIF)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // Buat program baru dengan active_key
            $activeKey = $blok->id.'-'.$tahunProgram;

            return ProgramPemupukan::create([
                'uuid' => Str::uuid()->toString(),
                'blok_lahan_id' => $blok->id,
                'tahun_program' => $tahunProgram,
                'rekomendasi_awal_id' => $rekomendasi?->id,
                'status_program' => ProgramPemupukan::STATUS_AKTIF,
                'active_key' => $activeKey,
            ]);
        });
    }

    /**
     * Resolve program untuk analisis: ambil program aktif tanpa membuat baru jika tidak perlu.
     * Membuat baru hanya jika blok belum punya program aktif untuk tahun tersebut.
     */
    public function resolveForAnalysis(
        BlokLahan $blok,
        int $tahunProgram,
        RekomendasiRbs $rekomendasi
    ): ProgramPemupukan {
        return $this->resolveActiveProgram($blok, $tahunProgram, $rekomendasi);
    }

    /**
     * Ambil program aktif tanpa membuat baru.
     */
    public function getActiveProgram(BlokLahan $blok, int $tahunProgram): ?ProgramPemupukan
    {
        return ProgramPemupukan::where('blok_lahan_id', $blok->id)
            ->where('tahun_program', $tahunProgram)
            ->where('status_program', ProgramPemupukan::STATUS_AKTIF)
            ->first();
    }
}
