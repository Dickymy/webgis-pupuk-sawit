<?php

namespace App\Enums;

/**
 * SeverityLevel — Tingkat keparahan gejala dari rule diagnosis.
 */
enum SeverityLevel: string
{
    case NORMAL = 'NORMAL';
    case RINGAN = 'RINGAN';
    case SEDANG = 'SEDANG';
    case BERAT = 'BERAT';
    case PERLU_VERIFIKASI = 'PERLU_VERIFIKASI';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::RINGAN => 'Ringan',
            self::SEDANG => 'Sedang',
            self::BERAT => 'Berat',
            self::PERLU_VERIFIKASI => 'Perlu Verifikasi',
        };
    }

    /**
     * Bobot numerik untuk perbandingan (semakin tinggi = semakin parah).
     */
    public function weight(): int
    {
        return match ($this) {
            self::NORMAL => 0,
            self::RINGAN => 1,
            self::SEDANG => 2,
            self::BERAT => 3,
            self::PERLU_VERIFIKASI => 1,
        };
    }
}
