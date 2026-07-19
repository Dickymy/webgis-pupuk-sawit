<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleBaseLanjutan extends Model
{
    protected $table = 'rule_bases_lanjutan';

    protected $fillable = [
        'kode_rule',
        'kondisi_warna_daun',
        'kondisi_ph_min',
        'kondisi_ph_max',
        'kondisi_kelembaban',
        'kondisi_curah_hujan_kategori',
        'kondisi_musim',
        'kondisi_drainase',
        'kondisi_defisiensi',
        'kondisi_kategori_umur',
        'kondisi_pelepah',
        'kondisi_tandan',
        'ada_serangan_hama',
        'ada_gulma_dominan',
        'kondisi_intermediate',
        'prasyarat_intermediate',
        'indikasi_masalah',
        'jenis_pupuk_utama',
        'jenis_pupuk_pendukung',
        'dosis_anjuran',
        'metode_aplikasi',
        'waktu_aplikasi',
        'saran_tindakan',
        'status_kebutuhan',
        'prioritas',
        'aktif',
        'keterangan_rule',
        // Provenance fields (Pahan-v2)
        'sumber_judul',
        'sumber_penulis',
        'sumber_tahun',
        'sumber_halaman',
        'sumber_tabel',
        'tingkat_bukti',
        'versi_rule',
        'is_system_rule',
        'status_validasi',
        'divalidasi_oleh',
        'tanggal_validasi',
        'catatan_validasi',
    ];

    protected function casts(): array
    {
        return [
            'aktif'                    => 'boolean',
            'ada_serangan_hama'        => 'boolean',
            'ada_gulma_dominan'        => 'boolean',
            'kondisi_ph_min'           => 'decimal:2',
            'kondisi_ph_max'           => 'decimal:2',
            'prioritas'                => 'integer',
            'kondisi_intermediate'     => 'array',
            'prasyarat_intermediate'   => 'array',
        ];
    }

    // Scope: hanya rule aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
