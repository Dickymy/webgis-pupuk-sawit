<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuleBaseDosisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus rule dosis lama jika ada untuk mencegah duplikasi
        DB::table('rule_bases_lanjutan')->where('jenis_rule', 'PENENTU_DOSIS')->delete();

        $rules = [];
        
        // Aturan Dosis untuk Tahun 1-25
        for ($i = 1; $i <= 25; $i++) {
            
            // Rumus Dosis Sederhana berdasar Pahan
            if ($i <= 3) {
                $fase = 'TBM';
                $urea = 0.5 + ($i * 0.25);
                $kcl = 0.5 + ($i * 0.25);
            } elseif ($i <= 8) {
                $fase = 'TM';
                $urea = 2.0;
                $kcl = 2.0;
            } elseif ($i <= 15) {
                $fase = 'TM';
                $urea = 2.5;
                $kcl = 2.5;
            } else {
                $fase = 'TM';
                $urea = 2.25;
                $kcl = 2.25;
            }

            $rules[] = [
                'kode_rule' => 'DOSIS-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'jenis_rule' => 'PENENTU_DOSIS',
                'kondisi_umur_tahun' => $i,
                'kondisi_kategori_umur' => $fase,
                'rekomendasi_dosis_urea' => $urea,
                'rekomendasi_dosis_kcl' => $kcl,
                'saran_tindakan' => "Rekomendasi Dosis Berdasarkan Umur $i Tahun ($fase)",
                'indikasi_masalah' => "Kebutuhan Nutrisi Rutin",
                'jenis_pupuk_utama' => 'Urea & KCl',
                'status_kebutuhan' => 'Normal',
                'aktif' => true,
                'is_system_rule' => true,
                'sumber_judul' => 'Panduan Pemupukan',
                'prioritas' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('rule_bases_lanjutan')->insert($rules);
    }
}
