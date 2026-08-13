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
    case TUNDA_TANAH_KERING = 'TUNDA_TANAH_KERING';
    case TUNDA_INTERVAL = 'TUNDA_INTERVAL';
    case PERLU_PERBAIKAN_DRAINASE = 'PERLU_PERBAIKAN_DRAINASE';
    case TUNDA_KONDISI_LAHAN = 'TUNDA_KONDISI_LAHAN';
    case PERLU_VERIFIKASI_DATA = 'PERLU_VERIFIKASI_DATA';
    case TERLAMBAT_PERLU_DIJADWALKAN = 'TERLAMBAT_PERLU_DIJADWALKAN';

    public function label(): string
    {
        return match ($this) {
            self::LAYAK_DIJADWALKAN => 'Siap Dipupuk',
            self::TUNDA_HUJAN_RENDAH => 'Belum Dipupuk — Hujan Terlalu Rendah',
            self::TUNDA_HUJAN_TINGGI => 'Belum Dipupuk — Hujan Terlalu Tinggi',
            self::TUNDA_TANAH_KERING => 'Belum Dipupuk — Tanah Sangat Kering',
            self::TUNDA_INTERVAL => 'Belum Dipupuk — Jarak Waktu Belum Cukup',
            self::PERLU_PERBAIKAN_DRAINASE => 'Belum Dipupuk — Perbaiki Saluran Air',
            self::TUNDA_KONDISI_LAHAN => 'Belum Dipupuk — Kondisi Lahan Tidak Memenuhi',
            self::PERLU_VERIFIKASI_DATA => 'Data Pemeriksaan Belum Lengkap',
            self::TERLAMBAT_PERLU_DIJADWALKAN => 'Segera Dijadwalkan',
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

        // Kompatibilitas data historis sebelum nama status dinormalisasi.
        if ($value === 'TUNDA_DRAINASE') {
            return 'Belum Dipupuk — Perbaiki Saluran Air';
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
