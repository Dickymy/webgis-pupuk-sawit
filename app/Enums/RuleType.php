<?php

namespace App\Enums;

/**
 * RuleType — Jenis rule dalam Rule-Based System.
 */
enum RuleType: string
{
    case DIAGNOSIS_VISUAL = 'DIAGNOSIS_VISUAL';
    case PEMBATAS_APLIKASI = 'PEMBATAS_APLIKASI';
    case SARAN_PENDUKUNG = 'SARAN_PENDUKUNG';
    case PERINGATAN_DATA = 'PERINGATAN_DATA';
    case NORMAL = 'NORMAL';

    public function label(): string
    {
        return match ($this) {
            self::DIAGNOSIS_VISUAL => 'Diagnosis Visual',
            self::PEMBATAS_APLIKASI => 'Pembatas Aplikasi',
            self::SARAN_PENDUKUNG => 'Saran Pendukung',
            self::PERINGATAN_DATA => 'Peringatan Data',
            self::NORMAL => 'Normal',
        };
    }
}
