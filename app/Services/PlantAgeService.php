<?php

namespace App\Services;

use App\Models\BlokLahan;
use Carbon\Carbon;

/**
 * PlantAgeService — Menghitung umur tanaman pada tanggal referensi tertentu.
 *
 * Umur analisis harus mengikuti tanggal observasi, bukan selalu tanggal saat ini.
 * Dashboard boleh memakai umur saat ini, tapi analisis historis wajib memakai
 * umur saat observasi.
 */
class PlantAgeService
{
    /**
     * Hitung umur tanaman pada tanggal referensi tertentu.
     *
     * @return array{
     *   umur: ?int,
     *   tanggal_referensi: string,
     *   metode_perhitungan: string,
     *   is_estimate: bool
     * }
     */
    public function calculateAgeAt(BlokLahan $blok, Carbon $referenceDate): array
    {
        // Jika tidak ada data tahun tanam sama sekali
        if ($blok->tahun_tanam === null) {
            return [
                'umur'                  => null,
                'tanggal_referensi'     => $referenceDate->toDateString(),
                'metode_perhitungan'    => 'tidak_tersedia',
                'is_estimate'           => true,
            ];
        }

        // Hitung berdasarkan tahun tanam (estimasi — tanggal tanam tidak ada)
        $umur = $referenceDate->year - $blok->tahun_tanam;

        // Pastikan umur tidak negatif
        if ($umur < 0) {
            $umur = 0;
        }

        return [
            'umur'                  => $umur,
            'tanggal_referensi'     => $referenceDate->toDateString(),
            'metode_perhitungan'    => 'tahun_tanam',
            'is_estimate'           => true,
        ];
    }

    /**
     * Hitung umur saat ini (untuk dashboard).
     */
    public function calculateCurrentAge(BlokLahan $blok): ?int
    {
        return $blok->tahun_tanam ? (now()->year - $blok->tahun_tanam) : null;
    }
}
