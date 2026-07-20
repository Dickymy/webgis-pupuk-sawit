<?php

namespace App\Enums;

/**
 * SeverityLevel — Tingkat keparahan gejala pada rule diagnosis.
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
}
