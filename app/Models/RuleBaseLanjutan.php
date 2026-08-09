<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleBaseLanjutan extends Model
{
    protected $table = 'rule_bases_lanjutan';

    protected $fillable = [
        'kode_rule',
        'jenis_rule',
        // Kondisi IF — hanya parameter yang masih aktif di form
        'kondisi_warna_daun',
        'kondisi_topografi',
        'kondisi_curah_hujan_min_mm',
        'kondisi_curah_hujan_max_mm',
        'kondisi_kategori_umur',
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
            'kondisi_curah_hujan_min_mm' => 'decimal:1',
            'kondisi_curah_hujan_max_mm' => 'decimal:1',
            'prioritas' => 'integer',
        ];
    }

    // Scope: hanya rule aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
