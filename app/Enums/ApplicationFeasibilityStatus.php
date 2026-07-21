<?php

namespace App\Enums;

/**
 * ApplicationFeasibilityStatus — Status kelayakan aplikasi pemupukan.
 *
 * Hanya berasal dari FertilizationWindowService.
 * Terpisah sepenuhnya dari status kondisi tanaman (PlantConditionStatus).
 */
enum ApplicationFeasibilityStatus: string
{
    case LAYAK_DIJADWALKAN = 'LAYAK_DIJADWALKAN';
    case TUNDA_HUJAN_RENDAH = 'TUNDA_HUJAN_RENDAH';
    case TUNDA_HUJAN_TINGGI = 'TUNDA_HUJAN_TINGGI';
    case TUNDA_INTERVAL = 'TUNDA_INTERVAL';
    case TUNDA_DRAINASE = 'TUNDA_DRAINASE';
    case PERLU_VERIFIKASI_DATA = 'PERLU_VERIFIKASI_DATA';
    case TERLAMBAT_PERLU_DIJADWALKAN = 'TERLAMBAT_PERLU_DIJADWALKAN';

    public function label(): string
    {
        return match ($this) {
            self::LAYAK_DIJADWALKAN => 'Layak Dijadwalkan',
            self::TUNDA_HUJAN_RENDAH => 'Ditunda karena Curah Hujan Rendah',
            self::TUNDA_HUJAN_TINGGI => 'Ditunda karena Curah Hujan Tinggi',
            self::TUNDA_INTERVAL => 'Ditunda karena Interval Pemupukan Terlalu Dekat',
            self::TUNDA_DRAINASE => 'Ditunda karena Drainase Bermasalah',
            self::PERLU_VERIFIKASI_DATA => 'Data Belum Lengkap',
            self::TERLAMBAT_PERLU_DIJADWALKAN => 'Terlambat dan Perlu Dijadwalkan',
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

        $status = self::tryFrom($value);

        return $status?->label() ?? $value;
    }

    /**
     * Apakah status ini berarti pemupukan boleh diaplikasikan.
     */
    public function isApplicable(): bool
    {
        return match ($this) {
            self::LAYAK_DIJADWALKAN, self::TERLAMBAT_PERLU_DIJADWALKAN => true,
            default => false,
        };
    }
}
