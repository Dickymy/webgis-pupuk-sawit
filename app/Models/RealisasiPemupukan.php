<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RealisasiPemupukan — Realisasi pelaksanaan pemupukan per tahap.
 *
 * Menyimpan data aktual pelaksanaan (realisasi) pemupukan
 * yang merujuk pada rekomendasi RBS tertentu.
 */
class RealisasiPemupukan extends Model
{
    protected $table = 'realisasi_pemupukans';

    protected $fillable = [
        'rekomendasi_rbs_id',
        'blok_lahan_id',
        'admin_id',
        'tahap',
        'tanggal_realisasi',
        'urea_rencana_kg',
        'kcl_rencana_kg',
        'urea_realisasi_kg',
        'kcl_realisasi_kg',
        'status_realisasi',
        'catatan_pelaksana',
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
}
