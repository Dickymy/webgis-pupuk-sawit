<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

class RuleCurahHujanGulmaSeeder extends Seeder
{
    /**
     * Seed rule baru untuk curah hujan dan gulma dominan (Fitur 4).
     */
    public function run(): void
    {
        $rules = [
            // Rule: Curah Hujan Sangat Tinggi
            [
                'kondisi_curah_hujan_kategori' => 'Sangat Tinggi',
                'indikasi_masalah' => 'Curah hujan sangat tinggi berisiko menyebabkan pencucian hara',
                'jenis_pupuk_utama' => 'Tunda Pemupukan',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Tunda hingga curah hujan lebih sesuai',
                'metode_aplikasi' => 'Tidak dianjurkan pemupukan saat hujan sangat tinggi',
                'waktu_aplikasi' => 'Setelah curah hujan kembali normal',
                'saran_tindakan' => 'Tunda pemupukan dan pastikan lahan tidak tergenang sebelum aplikasi pupuk',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 1,
            ],

            // Rule: Curah Hujan Sangat Rendah
            [
                'kondisi_curah_hujan_kategori' => 'Sangat Rendah',
                'indikasi_masalah' => 'Curah hujan sangat rendah menyebabkan pupuk kurang efektif diserap tanaman',
                'jenis_pupuk_utama' => 'Tunda Pemupukan',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Tunda sampai kelembaban tanah membaik',
                'metode_aplikasi' => 'Tidak dianjurkan pemupukan saat tanah terlalu kering',
                'waktu_aplikasi' => 'Setelah ada hujan cukup atau kelembaban tanah normal',
                'saran_tindakan' => 'Tunda pemupukan dan lakukan observasi ulang saat kelembaban membaik',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 2,
            ],

            // Rule: Gulma Dominan
            [
                'ada_gulma_dominan' => true,
                'indikasi_masalah' => 'Gulma dominan berpotensi bersaing menyerap hara dengan tanaman sawit',
                'jenis_pupuk_utama' => 'Pengendalian Gulma',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Tidak berupa dosis pupuk',
                'metode_aplikasi' => 'Pengendalian gulma manual/kimia sesuai kondisi lapangan',
                'waktu_aplikasi' => 'Sebelum pemupukan utama',
                'saran_tindakan' => 'Lakukan pengendalian gulma terlebih dahulu agar pemupukan lebih efektif',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],
        ];

        foreach ($rules as $rule) {
            RuleBaseLanjutan::create($rule);
        }

        $this->command->info('Rule Curah Hujan & Gulma berhasil di-seed: '.count($rules).' rules.');
    }
}
