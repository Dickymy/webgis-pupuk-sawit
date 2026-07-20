<?php

namespace App\Services;

use App\Enums\PlantPhase;
use App\Models\BlokLahan;
use Carbon\Carbon;

/**
 * PlantContextService — Menentukan konteks tanaman pada tanggal tertentu.
 *
 * Service ini menggabungkan PlantAgeService dan PlantPhaseResolver
 * untuk menghasilkan satu konteks lengkap yang benar secara historis.
 *
 * Aturan:
 *   umur < 3  → Tanaman Belum Menghasilkan (otomatis)
 *   umur = 3  → Perlu verifikasi pengguna
 *   umur > 3  → Tanaman Menghasilkan (otomatis)
 *
 * Referensi: Pahan, 2013. Bab 9.
 */
class PlantContextService
{
    public function __construct(
        private PlantAgeService $ageService,
    ) {}

    /**
     * Resolve konteks tanaman pada tanggal referensi tertentu.
     *
     * @return array{
     *   umur: ?int,
     *   fase: ?string,
     *   fase_label: string,
     *   tanggal_referensi: string,
     *   metode_perhitungan_umur: string,
     *   needs_phase_verification: bool,
     *   phase_conflict: bool
     * }
     */
    public function resolve(BlokLahan $blok, Carbon $tanggalReferensi): array
    {
        // Hitung umur pada tanggal referensi
        $ageInfo = $this->ageService->calculateAgeAt($blok, $tanggalReferensi);
        $umur = $ageInfo['umur'];

        if ($umur === null) {
            return [
                'umur' => null,
                'fase' => null,
                'fase_label' => 'Belum Ditentukan',
                'tanggal_referensi' => $tanggalReferensi->toDateString(),
                'metode_perhitungan_umur' => 'tidak_tersedia',
                'needs_phase_verification' => true,
                'phase_conflict' => false,
            ];
        }

        // Tentukan fase berdasarkan umur pada tanggal referensi
        $fase = $this->resolvePhaseFromAge($umur, $blok->fase_tanaman);
        $needsVerification = ($umur === 3 && $blok->fase_tanaman === null);
        $phaseConflict = $this->detectConflict($umur, $blok->fase_tanaman);

        // Jika ada konflik, jangan gunakan fase yang salah
        if ($phaseConflict) {
            $fase = $this->resolvePhaseFromAge($umur, null);
        }

        $faseLabel = PlantPhase::labelFromValue($fase);

        return [
            'umur' => $umur,
            'fase' => $fase,
            'fase_label' => $faseLabel,
            'tanggal_referensi' => $tanggalReferensi->toDateString(),
            'metode_perhitungan_umur' => $ageInfo['metode_perhitungan'],
            'needs_phase_verification' => $needsVerification,
            'phase_conflict' => $phaseConflict,
        ];
    }

    /**
     * Tentukan fase tanaman berdasarkan umur pada tanggal observasi.
     *
     * KUNCI: Fase ditentukan oleh umur SAAT OBSERVASI, bukan fase blok saat ini.
     * Fase blok saat ini hanya digunakan sebagai verifikasi untuk umur = 3.
     */
    private function resolvePhaseFromAge(int $umur, ?string $faseManual): ?string
    {
        if ($umur < 3) {
            return PlantPhase::BELUM_MENGHASILKAN->value; // TBM
        }

        if ($umur > 3) {
            return PlantPhase::MENGHASILKAN->value; // TM
        }

        // Umur = 3: gunakan fase manual jika tersedia dan valid
        if ($faseManual !== null) {
            return $faseManual;
        }

        // Tidak bisa ditentukan tanpa verifikasi
        return null;
    }

    /**
     * Deteksi konflik antara umur historis dan fase manual blok.
     */
    private function detectConflict(int $umur, ?string $faseManual): bool
    {
        if ($faseManual === null) {
            return false;
        }

        if ($umur < 3 && $faseManual === PlantPhase::MENGHASILKAN->value) {
            return true;
        }

        if ($umur > 3 && $faseManual === PlantPhase::BELUM_MENGHASILKAN->value) {
            return true;
        }

        return false;
    }
}
