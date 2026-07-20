<?php

namespace App\Enums;

/**
 * PlantPhase — Enum fase tanaman kelapa sawit.
 *
 * Kode internal (TBM, TM) disimpan di database.
 * Label lengkap ditampilkan ke pengguna.
 *
 * Referensi: Pahan, 2013. Bab 9.
 */
enum PlantPhase: string
{
    case BELUM_MENGHASILKAN = 'TBM';
    case MENGHASILKAN = 'TM';

    /**
     * Label lengkap untuk ditampilkan ke pengguna.
     * Jangan tampilkan singkatan TBM/TM pada antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::BELUM_MENGHASILKAN => 'Tanaman Belum Menghasilkan',
            self::MENGHASILKAN => 'Tanaman Menghasilkan',
        };
    }

    /**
     * Deskripsi singkat untuk formulir.
     */
    public function description(): string
    {
        return match ($this) {
            self::BELUM_MENGHASILKAN => 'Tanaman yang belum memasuki fase produksi.',
            self::MENGHASILKAN => 'Tanaman yang telah memasuki fase produksi tandan.',
        };
    }

    /**
     * Konversi kode internal ke label tampilan.
     */
    public static function labelFromValue(?string $value): string
    {
        if ($value === null) {
            return 'Belum Ditentukan';
        }

        $phase = self::tryFrom($value);
        return $phase?->label() ?? $value;
    }

    /**
     * Semua opsi untuk dropdown/select.
     */
    public static function options(): array
    {
        return [
            self::BELUM_MENGHASILKAN->value => self::BELUM_MENGHASILKAN->label(),
            self::MENGHASILKAN->value => self::MENGHASILKAN->label(),
        ];
    }
}
