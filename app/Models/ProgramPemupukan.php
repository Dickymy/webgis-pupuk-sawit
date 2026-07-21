<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProgramPemupukan — Identitas program pemupukan tahunan per blok.
 *
 * Pahan v2.7: Memastikan realisasi tidak tercampur antarprogram.
 * Satu blok hanya boleh memiliki satu program aktif per tahun.
 */
class ProgramPemupukan extends Model
{
    protected $table = 'program_pemupukans';

    protected $fillable = [
        'uuid',
        'blok_lahan_id',
        'tahun_program',
        'rekomendasi_awal_id',
        'status_program',
        'active_key',
    ];

    protected function casts(): array
    {
        return [
            'tahun_program' => 'integer',
        ];
    }

    // ─── Status Constants ────────────────────────────────────

    public const STATUS_AKTIF = 'AKTIF';

    public const STATUS_SELESAI = 'SELESAI';

    public const STATUS_DIBATALKAN = 'DIBATALKAN';

    public const STATUS_ARSIP = 'ARSIP';

    // ─── Relasi ──────────────────────────────────────────────

    public function blokLahan(): BelongsTo
    {
        return $this->belongsTo(BlokLahan::class, 'blok_lahan_id');
    }

    public function rekomendasiAwal(): BelongsTo
    {
        return $this->belongsTo(RekomendasiRbs::class, 'rekomendasi_awal_id');
    }

    public function rekomendasiRbs(): HasMany
    {
        return $this->hasMany(RekomendasiRbs::class, 'program_pemupukan_id');
    }

    public function realisasiPemupukans(): HasMany
    {
        return $this->hasMany(RealisasiPemupukan::class, 'program_pemupukan_id');
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status_program', self::STATUS_AKTIF);
    }

    public function scopeForBlokTahun($query, int $blokLahanId, int $tahun)
    {
        return $query->where('blok_lahan_id', $blokLahanId)
            ->where('tahun_program', $tahun);
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status_program) {
            self::STATUS_AKTIF => 'Aktif',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            self::STATUS_ARSIP => 'Arsip',
            default => $this->status_program ?? '-',
        };
    }

    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status_program) {
            self::STATUS_AKTIF => 'emerald',
            self::STATUS_SELESAI => 'green',
            self::STATUS_DIBATALKAN => 'red',
            self::STATUS_ARSIP => 'slate',
            default => 'slate',
        };
    }

    public function isAktif(): bool
    {
        return $this->status_program === self::STATUS_AKTIF;
    }
}
