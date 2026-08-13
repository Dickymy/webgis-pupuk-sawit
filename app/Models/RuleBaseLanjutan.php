<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleBaseLanjutan extends Model
{
    protected $table = 'rule_bases_lanjutan';

    protected $fillable = [
        'kode_rule',
        'jenis_rule',
        'tahap_eksekusi',
        // Kondisi IF — hanya parameter yang masih aktif di form
        'kondisi_warna_daun',
        'kondisi_topografi',
        'kondisi_curah_hujan_min_mm',
        'kondisi_curah_hujan_max_mm',
        'kondisi_kategori_umur',
        'kondisi_kelembaban',
        'kondisi_drainase',
        'ada_gulma_dominan',
        'ada_serangan_hama',
        'kondisi_umur_tahun',
        'rekomendasi_dosis_urea',
        'rekomendasi_dosis_kcl',
        'fakta_yang_dihasilkan',
        'prasyarat_fakta',
        // Output THEN
        'indikasi_masalah',
        'jenis_pupuk_utama',
        'saran_tindakan',
        'status_kebutuhan',
        'tingkat_keparahan',
        'prioritas',
        'aktif',
        // Provenance
        'sumber_judul',
        'sumber_penulis',
        'sumber_tahun',
        'sumber_halaman',
        'sumber_tabel',
        'tingkat_bukti',
        'is_system_rule',
        'status_validasi',
        'catatan_validasi',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'is_system_rule' => 'boolean',
            'tahap_eksekusi' => 'integer',
            'fakta_yang_dihasilkan' => 'array',
            'prasyarat_fakta' => 'array',
            'kondisi_curah_hujan_min_mm' => 'decimal:1',
            'kondisi_curah_hujan_max_mm' => 'decimal:1',
            'kondisi_umur_tahun' => 'integer',
            'rekomendasi_dosis_urea' => 'decimal:2',
            'rekomendasi_dosis_kcl' => 'decimal:2',
            'prioritas' => 'integer',
            'ada_gulma_dominan' => 'boolean',
            'ada_serangan_hama' => 'boolean',
        ];
    }

    // Scope: hanya rule aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
