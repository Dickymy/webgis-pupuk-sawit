<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KondisiLahan extends Model
{
    protected $fillable = [
        'blok_lahan_id',
        'tanggal_observasi',
        'tanggal_pemupukan_terakhir',
        'ph_tanah',
        'metode_pengukuran_ph',
        'kelembaban_tanah',
        'curah_hujan_kategori',
        'curah_hujan_mm_bulanan',
        'periode_curah_hujan',
        'sumber_curah_hujan',
        'musim_saat_ini',
        'warna_daun',
        'kondisi_pelepah',
        'gejala_defisiensi',
        'kondisi_tandan',
        'kondisi_drainase',
        'ada_gulma_dominan',
        'ada_serangan_hama',
        'catatan_observasi',
        'status_verifikasi_gejala',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_observasi' => 'date',
            'tanggal_pemupukan_terakhir' => 'date',
            'gejala_defisiensi' => 'array',
            'ada_gulma_dominan' => 'boolean',
            'ada_serangan_hama' => 'boolean',
            'ph_tanah' => 'decimal:2',
            'curah_hujan_mm_bulanan' => 'decimal:1',
        ];
    }

    // Relasi
    public function blokLahan(): BelongsTo
    {
        return $this->belongsTo(BlokLahan::class);
    }

    public function rekomendasiRbs(): HasMany
    {
        return $this->hasMany(RekomendasiRbs::class);
    }

    // Accessor: label pH
    public function getLabelPhAttribute(): string
    {
        if (is_null($this->ph_tanah)) {
            return '-';
        }

        return match (true) {
            $this->ph_tanah < 4.0 => 'Sangat Masam',
            $this->ph_tanah < 5.5 => 'Masam',
            $this->ph_tanah < 6.5 => 'Agak Masam (Optimal)',
            $this->ph_tanah < 7.5 => 'Netral',
            default => 'Basa',
        };
    }
}
