<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RealisasiPemupukan — Realisasi pelaksanaan pemupukan per tahap.
 *
 * Menyimpan data aktual pelaksanaan (realisasi) pemupukan
 * yang merujuk pada rekomendasi RBS tertentu.
 *
 * Pahan v2.6: Ditambahkan field tahun_program, confirmed_over_plan,
 * override_annual_limit, override_reason untuk validasi ketat.
 */
class RealisasiPemupukan extends Model
{
    protected $table = 'realisasi_pemupukans';

    protected $fillable = [
        'rekomendasi_rbs_id',
        'blok_lahan_id',
        'admin_id',
        'tahun_program',
        'tahap',
        'tanggal_realisasi',
        'urea_rencana_kg',
        'kcl_rencana_kg',
        'urea_realisasi_kg',
        'kcl_realisasi_kg',
        'status_realisasi',
        'catatan_pelaksana',
        'confirmed_over_plan',
        'override_annual_limit',
        'override_reason',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_realisasi' => 'date',
            'urea_rencana_kg' => 'decimal:2',
            'kcl_rencana_kg' => 'decimal:2',
            'urea_realisasi_kg' => 'decimal:2',
            'kcl_realisasi_kg' => 'decimal:2',
            'tahap' => 'integer',
            'tahun_program' => 'integer',
            'confirmed_over_plan' => 'boolean',
            'override_annual_limit' => 'boolean',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────

    public function rekomendasiRbs(): BelongsTo
    {
        return $this->belongsTo(RekomendasiRbs::class, 'rekomendasi_rbs_id');
    }

    public function blokLahan(): BelongsTo
    {
        return $this->belongsTo(BlokLahan::class, 'blok_lahan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // ─── Constants ───────────────────────────────────────────

    public const STATUS_SELESAI = 'SELESAI';

    public const STATUS_SEBAGIAN = 'SEBAGIAN';

    public const STATUS_BATAL = 'BATAL';

    // ─── Accessors ───────────────────────────────────────────

    /**
     * Label status realisasi untuk tampilan.
     */
    public function getLabelStatusAttribute(): string
    {
        return match ($this->status_realisasi) {
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_SEBAGIAN => 'Sebagian',
            self::STATUS_BATAL => 'Dibatalkan',
            default => $this->status_realisasi ?? '-',
        };
    }

    /**
     * Warna badge status realisasi.
     */
    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status_realisasi) {
            self::STATUS_SELESAI => 'green',
            self::STATUS_SEBAGIAN => 'amber',
            self::STATUS_BATAL => 'red',
            default => 'slate',
        };
    }

    /**
     * Cek apakah realisasi ini aktif (bukan batal).
     */
    public function isAktif(): bool
    {
        return $this->status_realisasi !== self::STATUS_BATAL;
    }

    // ─── Scopes ──────────────────────────────────────────────

    /**
     * Scope: hanya realisasi aktif (non-batal).
     */
    public function scopeAktif($query)
    {
        return $query->where('status_realisasi', '!=', self::STATUS_BATAL);
    }

    /**
     * Scope: realisasi per tahap.
     */
    public function scopeTahap($query, int $tahap)
    {
        return $query->where('tahap', $tahap);
    }

    /**
     * Scope: realisasi pada tahun program tertentu.
     */
    public function scopeTahunProgram($query, int $tahun)
    {
        return $query->where('tahun_program', $tahun);
    }
}
