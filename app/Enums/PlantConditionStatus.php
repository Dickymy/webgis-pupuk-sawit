<?php

namespace App\Enums;

/**
 * PlantConditionStatus — Status kondisi tanaman berdasarkan diagnosis visual.
 *
 * Hanya berasal dari rule jenis_rule = DIAGNOSIS_VISUAL dan tingkat_keparahan.
 * Rule PEMBATAS_APLIKASI, SARAN_PENDUKUNG, PERINGATAN_DATA
 * TIDAK boleh mengubah status kondisi tanaman.
 */
enum PlantConditionStatus: string
{
    case NORMAL_VISUAL = 'NORMAL_VISUAL';
    case TERINDIKASI_DEFISIENSI_RINGAN = 'TERINDIKASI_DEFISIENSI_RINGAN';
    case TERINDIKASI_DEFISIENSI = 'TERINDIKASI_DEFISIENSI';
    case GEJALA_BERAT = 'GEJALA_BERAT';
    case PERLU_VERIFIKASI = 'PERLU_VERIFIKASI';
    case BELUM_DIOBSERVASI = 'BELUM_DIOBSERVASI';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL_VISUAL => 'Tidak Ditemukan Gejala pada Daun',
            self::TERINDIKASI_DEFISIENSI_RINGAN => 'Ditemukan Gejala pada Daun',
            self::TERINDIKASI_DEFISIENSI => 'Ditemukan Gejala pada Daun',
            self::GEJALA_BERAT => 'Ditemukan Gejala pada Daun',
            self::PERLU_VERIFIKASI => 'Data Pemeriksaan Belum Lengkap',
            self::BELUM_DIOBSERVASI => 'Belum Diperiksa',
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
     * Tentukan status dari tingkat keparahan rule DIAGNOSIS_VISUAL.
     */
    public static function fromSeverity(?string $tingkatKeparahan): self
    {
        return match ($tingkatKeparahan) {
            'NORMAL' => self::NORMAL_VISUAL,
            'RINGAN' => self::TERINDIKASI_DEFISIENSI_RINGAN,
            'SEDANG' => self::TERINDIKASI_DEFISIENSI,
            'BERAT' => self::GEJALA_BERAT,
            'PERLU_VERIFIKASI' => self::PERLU_VERIFIKASI,
            default => self::PERLU_VERIFIKASI,
        };
    }
}
