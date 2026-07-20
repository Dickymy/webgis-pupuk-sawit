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
     * PERBAIKAN: Validasi konsistensi fase manual dengan umur.
     * - Umur < 3: hanya TBM valid
     * - Umur = 3: TBM atau TM, perlu verifikasi
     * - Umur > 3: hanya TM valid
     *
     * @return array{fase: ?string, verified: bool, needs_verification: bool, message: ?string, phase_conflict: bool}
     */
    public function resolve(BlokLahan $blok): array
    {
        $umur = $blok->umur_tanaman;

        // Jika fase sudah diisi manual, VALIDASI terhadap umur
        if ($blok->fase_tanaman !== null) {
            // Validasi konsistensi
            if ($umur !== null) {
                $conflict = $this->detectPhaseConflict($blok->fase_tanaman, $umur);
                if ($conflict !== null) {
                    return [
                        'fase' => null, // Tidak gunakan fase yang konflik
                        'verified' => false,
                        'needs_verification' => true,
                        'message' => $conflict,
                        'phase_conflict' => true,
                    ];
                }
            }

            return [
                'fase' => $blok->fase_tanaman,
                'verified' => true,
                'needs_verification' => false,
                'message' => null,
                'phase_conflict' => false,
            ];
        }

        // Auto-suggest berdasarkan umur
        if ($umur === null) {
            return [
                'fase' => null,
                'verified' => false,
                'needs_verification' => true,
                'message' => 'Tahun tanam belum diisi, fase tanaman tidak dapat ditentukan.',
                'phase_conflict' => false,
            ];
        }

        if ($umur < 3) {
            return [
                'fase' => 'TBM',
                'verified' => false,
                'needs_verification' => false,
                'message' => "Umur {$umur} tahun — otomatis dikategorikan TBM.",
                'phase_conflict' => false,
            ];
        }

        if ($umur === 3) {
            return [
                'fase' => null,
                'verified' => false,
                'needs_verification' => true,
                'message' => 'Umur tepat 3 tahun — bisa TBM atau TM. Perlu verifikasi pengguna.',
                'phase_conflict' => false,
            ];
        }

        // Umur > 3 tahun
        return [
            'fase' => 'TM',
            'verified' => false,
            'needs_verification' => false,
            'message' => "Umur {$umur} tahun — otomatis dikategorikan TM.",
            'phase_conflict' => false,
        ];
    }

    /**
     * Deteksi konflik antara fase manual dan umur.
     * Mengembalikan pesan error jika ada konflik, null jika OK.
     */
    public function detectPhaseConflict(string $fase, int $umur): ?string
    {
        if ($umur < 3 && $fase === 'TM') {
            return "Konflik: Umur {$umur} tahun tidak dapat dikategorikan sebagai TM (Tanaman Menghasilkan). Umur < 3 tahun hanya valid untuk TBM.";
        }

        if ($umur > 3 && $fase === 'TBM') {
            return "Konflik: Umur {$umur} tahun tidak dapat dikategorikan sebagai TBM (Tanaman Belum Menghasilkan). Umur > 3 tahun hanya valid untuk TM.";
        }

        // Umur = 3: keduanya valid
        return null;
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
