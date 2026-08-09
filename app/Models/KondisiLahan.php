<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KondisiLahan extends Model
{
    use HasFactory;

    protected $fillable = [
        'blok_lahan_id',
        'tanggal_observasi',
        'tanggal_pemupukan_terakhir',
        'kelembaban_tanah',
        'curah_hujan_kategori',
        'curah_hujan_mm_bulanan',
        'periode_curah_hujan',
        'sumber_curah_hujan',
        'musim_saat_ini',
        'warna_daun',
        'kondisi_drainase',
        'ada_gulma_dominan',
        'ada_serangan_hama',
        'catatan_observasi',
        'foto_observasi_path',
        'status_verifikasi_gejala',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_observasi' => 'date',
            'tanggal_pemupukan_terakhir' => 'date',
            'ada_gulma_dominan' => 'boolean',
            'ada_serangan_hama' => 'boolean',
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

}
