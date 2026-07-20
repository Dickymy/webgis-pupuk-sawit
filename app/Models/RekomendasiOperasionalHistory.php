<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RekomendasiOperasionalHistory — Histori perubahan operasional rekomendasi.
 *
 * Pahan v2.7: Setiap perubahan realisasi mencatat snapshot operasional.
 * Histori tidak pernah dihapus.
 */
class RekomendasiOperasionalHistory extends Model
{
    protected $table = 'rekomendasi_operasional_histories';

    public $timestamps = false;

    protected $fillable = [
        'rekomendasi_rbs_id',
        'program_pemupukan_id',
        'event_type',
        'active_stage',
        'status_stage',
        'urea_aplikasi_saat_ini',
        'kcl_aplikasi_saat_ini',
        'urea_sisa_tahunan',
        'kcl_sisa_tahunan',
        'tanggal_minimum_tahap_berikutnya',
        'alasan_tahap',
        'analysis_fingerprint',
        'source_realisasi_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'active_stage' => 'integer',
            'urea_aplikasi_saat_ini' => 'decimal:2',
            'kcl_aplikasi_saat_ini' => 'decimal:2',
            'urea_sisa_tahunan' => 'decimal:2',
            'kcl_sisa_tahunan' => 'decimal:2',
            'tanggal_minimum_tahap_berikutnya' => 'date',
            'created_at' => 'datetime',
        ];
    }

    // ─── Event Type Constants ────────────────────────────────

    public const ANALISIS_AWAL = 'ANALISIS_AWAL';

    public const REALISASI_DIBUAT = 'REALISASI_DIBUAT';

    public const REALISASI_DIPERBARUI = 'REALISASI_DIPERBARUI';

    public const REALISASI_DIBATALKAN = 'REALISASI_DIBATALKAN';

    public const TAHAP_1_SEBAGIAN = 'TAHAP_1_SEBAGIAN';

    public const TAHAP_1_SELESAI = 'TAHAP_1_SELESAI';

    public const TAHAP_2_SIAP = 'TAHAP_2_SIAP';

    public const PROGRAM_SELESAI = 'PROGRAM_SELESAI';

    // ─── Relasi ──────────────────────────────────────────────

    public function rekomendasiRbs(): BelongsTo
    {
        return $this->belongsTo(RekomendasiRbs::class, 'rekomendasi_rbs_id');
    }

    public function programPemupukan(): BelongsTo
    {
        return $this->belongsTo(ProgramPemupukan::class, 'program_pemupukan_id');
    }

    public function sourceRealisasi(): BelongsTo
    {
        return $this->belongsTo(RealisasiPemupukan::class, 'source_realisasi_id');
    }

    // ─── Accessors ───────────────────────────────────────────

    public function getLabelEventAttribute(): string
    {
        return match ($this->event_type) {
            self::ANALISIS_AWAL => 'Analisis Awal',
            self::REALISASI_DIBUAT => 'Realisasi Dibuat',
            self::REALISASI_DIPERBARUI => 'Realisasi Diperbarui',
            self::REALISASI_DIBATALKAN => 'Realisasi Dibatalkan',
            self::TAHAP_1_SEBAGIAN => 'Tahap 1 Sebagian',
            self::TAHAP_1_SELESAI => 'Tahap 1 Selesai',
            self::TAHAP_2_SIAP => 'Tahap 2 Siap',
            self::PROGRAM_SELESAI => 'Program Selesai',
            default => $this->event_type ?? '-',
        };
    }
}
