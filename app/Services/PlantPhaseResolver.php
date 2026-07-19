<?php

namespace App\Services;

use App\Models\BlokLahan;

/**
 * PlantPhaseResolver — Menentukan fase tanaman TBM/TM.
 *
 * Referensi: Pahan, 2013. Bab 9.
 * - TBM: Tanaman Belum Menghasilkan (biasanya 0-3 tahun)
 * - TM: Tanaman Menghasilkan (biasanya mulai umur 3+ tahun)
 *
 * Catatan: Umur 3 tahun bisa TBM atau TM tergantung kondisi lapangan,
 * sehingga fase harus bisa dipilih manual.
 */
class PlantPhaseResolver
{
    /**
     * Resolve fase tanaman dari BlokLahan.
     * Prioritas: field fase_tanaman > auto-suggest dari umur.
     *
     * @return array{fase: ?string, verified: bool, needs_verification: bool, message: ?string}
     */
    public function resolve(BlokLahan $blok): array
    {
        // Jika fase sudah diisi manual, gunakan itu
        if ($blok->fase_tanaman !== null) {
            return [
                'fase'               => $blok->fase_tanaman,
                'verified'           => true,
                'needs_verification' => false,
                'message'            => null,
            ];
        }

        // Auto-suggest berdasarkan umur
        $umur = $blok->umur_tanaman;

        if ($umur === null) {
            return [
                'fase'               => null,
                'verified'           => false,
                'needs_verification' => true,
                'message'            => 'Tahun tanam belum diisi, fase tanaman tidak dapat ditentukan.',
            ];
        }

        if ($umur < 3) {
            return [
                'fase'               => 'TBM',
                'verified'           => false,
                'needs_verification' => false,
                'message'            => "Umur {$umur} tahun — otomatis dikategorikan TBM.",
            ];
        }

        if ($umur === 3) {
            return [
                'fase'               => null,
                'verified'           => false,
                'needs_verification' => true,
                'message'            => 'Umur tepat 3 tahun — bisa TBM atau TM. Perlu verifikasi pengguna.',
            ];
        }

        // Umur > 3 tahun
        return [
            'fase'               => 'TM',
            'verified'           => false,
            'needs_verification' => false,
            'message'            => "Umur {$umur} tahun — otomatis dikategorikan TM.",
        ];
    }

    /**
     * Dapatkan fase efektif (untuk dipakai perhitungan).
     * Jika fase belum ditentukan, gunakan auto-suggest terbaik.
     */
    public function getEffectivePhase(BlokLahan $blok): ?string
    {
        $resolved = $this->resolve($blok);

        return $resolved['fase'];
    }
}
