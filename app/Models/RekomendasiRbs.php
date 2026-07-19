<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiRbs extends Model
{
    protected $table = 'rekomendasi_rbs';

    protected $fillable = [
        'blok_lahan_id',
        'kondisi_lahan_id',
        'admin_id',
        'tanggal_analisis',
        'is_latest',
        'nomor_analisis',
        'rules_terpicu',
        'masalah_teridentifikasi',
        'rekomendasi_pupuk',
        'saran_tindakan_utama',
        'status_kebutuhan_dominan',
        'jumlah_rule_terpicu',
        'dosis_urea',
        'dosis_kcl',
        'total_urea',
        'total_kcl',
        'catatan_dosis',
        'jadwal_pemupukan',
        'validitas_rekomendasi',
        'catatan_validitas',
        'confidence_score',
        'confidence_label',
        'catatan_confidence',
        'data_cukup',
        'data_kurang',
        'notifikasi_data',
        // Pahan-v2 fields
        'fase_tanaman_snapshot',
        'umur_tanaman_snapshot',
        'urea_min_kg_per_pokok_tahun',
        'urea_max_kg_per_pokok_tahun',
        'urea_estimasi_kg_per_pokok_tahun',
        'kcl_min_kg_per_pokok_tahun',
        'kcl_max_kg_per_pokok_tahun',
        'kcl_estimasi_kg_per_pokok_tahun',
        'strategi_estimasi_dosis',
        'jumlah_pokok_snapshot',
        'dasar_perhitungan_json',
        'peringatan_json',
        'kelengkapan_data_score',
        'kategori_keandalan',
        'rincian_skor_json',
        'status_kondisi_tanaman',
        'status_kelayakan_aplikasi',
        'alasan_kelayakan',
        'versi_mesin_rekomendasi',
        'analysis_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_analisis'        => 'date',
            'rules_terpicu'           => 'array',
            'masalah_teridentifikasi' => 'array',
            'rekomendasi_pupuk'       => 'array',
            'jadwal_pemupukan'        => 'array',
            'data_kurang'             => 'array',
            'dasar_perhitungan_json'  => 'array',
            'peringatan_json'         => 'array',
            'rincian_skor_json'       => 'array',
            'is_latest'               => 'boolean',
            'data_cukup'              => 'boolean',
            'dosis_urea'              => 'double',
            'dosis_kcl'               => 'double',
            'total_urea'              => 'double',
            'total_kcl'               => 'double',
            'confidence_score'        => 'integer',
            'kelengkapan_data_score'  => 'integer',
        ];
    }

    public function blokLahan(): BelongsTo
    {
        return $this->belongsTo(BlokLahan::class, 'blok_lahan_id');
    }

    public function kondisiLahan(): BelongsTo
    {
        return $this->belongsTo(KondisiLahan::class, 'kondisi_lahan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // Accessor: badge warna status
    public function getWarnaBadgeAttribute(): string
    {
        return match($this->status_kebutuhan_dominan) {
            'Darurat' => 'red',
            'Segera'  => 'orange',
            'Normal'  => 'green',
            'Tunda'   => 'gray',
            default   => 'blue',
        };
    }

    /**
     * Accessor: label status yang ditampilkan ke user.
     * Data di DB tetap Darurat/Segera/Normal/Tunda, tapi tampilan lebih mudah dipahami.
     */
    public function getLabelStatusAttribute(): string
    {
        return self::labelStatus($this->status_kebutuhan_dominan);
    }

    /**
     * Static helper: konversi status DB ke label tampilan.
     */
    public static function labelStatus(?string $status): string
    {
        return match($status) {
            'Darurat' => 'Defisiensi Berat',
            'Segera'  => 'Perlu Pupuk',
            'Normal'  => 'Sehat',
            'Tunda'   => 'Tunda Pupuk',
            default   => 'Belum Dicek',
        };
    }

    /**
     * Hitung kebutuhan karung Urea (1 karung = 50 kg)
     */
    public function getKarungUreaAttribute(): int
    {
        return $this->total_urea ? (int) ceil($this->total_urea / 50) : 0;
    }

    /**
     * Hitung kebutuhan karung KCl (1 karung = 50 kg)
     */
    public function getKarungKclAttribute(): int
    {
        return $this->total_kcl ? (int) ceil($this->total_kcl / 50) : 0;
    }

    // Accessor: badge warna confidence
    public function getWarnaConfidenceAttribute(): string
    {
        return match($this->confidence_label) {
            'Tinggi' => 'green',
            'Sedang' => 'blue',
            default  => 'amber',
        };
    }

    // Accessor: badge warna validitas
    public function getWarnaValiditasAttribute(): string
    {
        return match($this->validitas_rekomendasi) {
            'Terverifikasi' => 'green',
            'Cukup Kuat'    => 'blue',
            default         => 'amber',
        };
    }

    // Scope: hanya rekomendasi terbaru
    public function scopeLatest_only($query)
    {
        return $query->where('is_latest', true);
    }
}
