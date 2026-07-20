<?php

namespace App\Models;

use App\Enums\PlantPhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BlokLahan extends Model
{
    protected $fillable = [
        'anggota_id',
        'nama_blok',
        'luas_ha',
        'sph',
        'koordinat_geojson',
        'tahun_tanam',
        'jenis_tanah',
        'topografi',
        'fase_tanaman',
    ];

    protected function casts(): array
    {
        return [
            'luas_ha' => 'double',
            'sph' => 'integer',
            'tahun_tanam' => 'integer',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function kondisiLahans(): HasMany
    {
        return $this->hasMany(KondisiLahan::class, 'blok_lahan_id');
    }

    public function kondisiTerbaru(): HasOne
    {
        return $this->hasOne(KondisiLahan::class, 'blok_lahan_id')->latestOfMany('tanggal_observasi');
    }

    public function rekomendasiRbs(): HasMany
    {
        return $this->hasMany(RekomendasiRbs::class, 'blok_lahan_id');
    }

    public function realisasiPemupukans(): HasMany
    {
        return $this->hasMany(RealisasiPemupukan::class, 'blok_lahan_id');
    }

    public function programPemupukans(): HasMany
    {
        return $this->hasMany(ProgramPemupukan::class, 'blok_lahan_id');
    }

    public function rekomendasiRbsTerbaru(): HasOne
    {
        return $this->hasOne(RekomendasiRbs::class, 'blok_lahan_id')
            ->where('is_latest', true)
            ->latestOfMany('tanggal_analisis');
    }

    // ─── Accessor ────────────────────────────────────────────

    /**
     * Nama pemilik dari relasi anggota.
     */
    public function getNamaPemilikAttribute(): string
    {
        return $this->anggota?->nama ?? '-';
    }

    /**
     * Hitung umur tanaman secara dinamis.
     */
    public function getUmurTanamanAttribute(): ?int
    {
        return $this->tahun_tanam ? (now()->year - $this->tahun_tanam) : null;
    }

    /**
     * Kategorisasi umur tanaman.
     */
    public function getKategoriUmurAttribute(): ?string
    {
        $umur = $this->umur_tanaman;
        if ($umur === null) {
            return null;
        }

        if ($umur < 3) {
            return 'Belum Menghasilkan';
        }
        if ($umur <= 8) {
            return 'Remaja';
        }
        if ($umur <= 14) {
            return 'Menghasilkan Muda';
        }
        if ($umur <= 25) {
            return 'Menghasilkan Tua';
        }

        return 'Tua Renta';
    }

    /**
     * Label fase tanaman (nama lengkap, bukan singkatan TBM/TM).
     */
    public function getFaseLabelAttribute(): string
    {
        return PlantPhase::labelFromValue($this->fase_tanaman);
    }
}
