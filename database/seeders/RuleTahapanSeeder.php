<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

class RuleTahapanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tahap 1: Diagnosis Visual
        RuleBaseLanjutan::where('jenis_rule', 'DIAGNOSIS_VISUAL')
            ->update(['tahap_eksekusi' => 1]);

        // Berikan fakta baru pada salah satu rule visual (misal kode DIAG-01 atau semacamnya)
        // Jika tidak tahu kodenya, ambil rule pertama yang mengecek daun kuning
        $ruleKuning = RuleBaseLanjutan::where('jenis_rule', 'DIAGNOSIS_VISUAL')
            ->where('kondisi_warna_daun', 'like', '%Kuning%')
            ->first();
        
        if ($ruleKuning) {
            $ruleKuning->update([
                'fakta_yang_dihasilkan' => ['status_nitrogen' => 'Defisiensi']
            ]);
        }

        // 2. Tahap 2: Penentuan Dosis
        RuleBaseLanjutan::where('jenis_rule', 'PENENTU_DOSIS')
            ->update(['tahap_eksekusi' => 2]);

        // 3. Tahap 3: Kondisi Lingkungan / Pembatas Aplikasi
        RuleBaseLanjutan::whereIn('jenis_rule', ['KONDISI_LAHAN', 'PEMBATAS_APLIKASI'])
            ->update(['tahap_eksekusi' => 3]);

        // Buat 1 Rule Tahap 3 Khusus yang membuktikan Chaining dari Tahap 1
        RuleBaseLanjutan::firstOrCreate(
            ['kode_rule' => 'KOREKSI-N-01'],
            [
                'jenis_rule' => 'PEMBATAS_APLIKASI',
                'tahap_eksekusi' => 3,
            'prasyarat_fakta' => ['status_nitrogen' => 'Defisiensi'],
            'indikasi_masalah' => 'Koreksi Defisiensi N Aktif',
            'saran_tindakan' => 'Sistem mendeteksi defisiensi Nitrogen (fakta dari Tahap 1). Lakukan penambahan ekstra Urea di luar dosis standar (Tahap 2) sebanyak 20%.',
            'status_kebutuhan' => 'Segera',
            'tingkat_keparahan' => 'SEDANG',
            'prioritas' => 1,
            'aktif' => true,
            'sumber_judul' => 'Sistem Pakar Dinamis',
            'tingkat_bukti' => 'AHLI',
            'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
            'is_system_rule' => false,
            'status_validasi' => 'TERVERIFIKASI_SUMBER',
        ]);
    }
}
