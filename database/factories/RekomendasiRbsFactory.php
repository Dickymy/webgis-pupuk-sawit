<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Database\Eloquent\Factories\Factory;

class RekomendasiRbsFactory extends Factory
{
    protected $model = RekomendasiRbs::class;

    public function definition(): array
    {
        return [
            'blok_lahan_id' => BlokLahan::factory(),
            'kondisi_lahan_id' => KondisiLahan::factory(),
            'admin_id' => Admin::factory(),
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Tidak ada masalah'],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Lanjutkan pemupukan standar.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 1.0,
            'dosis_kcl' => 1.5,
            'total_urea' => 50.0,
            'total_kcl' => 40.0,
            'urea_total_estimasi_tahunan' => 100.0,
            'kcl_total_estimasi_tahunan' => 80.0,
            'urea_aplikasi_saat_ini' => 50.0,
            'kcl_aplikasi_saat_ini' => 40.0,
            'status_kondisi_tanaman' => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'versi_mesin_rekomendasi' => 'pahan-v2.8',
        ];
    }
}
