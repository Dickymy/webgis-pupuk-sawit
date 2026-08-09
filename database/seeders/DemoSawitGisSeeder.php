<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use Illuminate\Database\Seeder;

/**
 * DemoSawitGisSeeder — Data demo terpisah untuk presentasi skripsi.
 *
 * HANYA dijalankan secara eksplisit:
 *   php artisan db:seed --class=DemoSawitGisSeeder
 *
 * Semua data demo menggunakan prefix "DEMO -" agar mudah diidentifikasi.
 * Tidak dipanggil oleh DatabaseSeeder.
 */
class DemoSawitGisSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Membuat data demo SawitGIS...');

        $anggotaData = $this->seedAnggota();
        $blokData = $this->seedBlokLahan($anggotaData);
        $this->seedKondisiLahan($blokData);

        $this->command?->info('Data demo berhasil dibuat: 5 anggota, 10 blok, beberapa kondisi lahan.');
        $this->command?->info('Jalankan analisis RBS pada masing-masing blok untuk melengkapi data demo.');
    }

    private function seedAnggota(): array
    {
        $anggota = [
            ['nama' => 'DEMO - Pak Hadi Sutrisno', 'alamat' => 'Desa Suka Maju RT 01', 'no_hp' => '081200000001'],
            ['nama' => 'DEMO - Bu Sri Wahyuni', 'alamat' => 'Desa Suka Maju RT 02', 'no_hp' => '081200000002'],
            ['nama' => 'DEMO - Pak Joko Widodo', 'alamat' => 'Desa Harapan Baru RT 03', 'no_hp' => '081200000003'],
            ['nama' => 'DEMO - Pak Ahmad Fauzi', 'alamat' => 'Desa Harapan Baru RT 04', 'no_hp' => '081200000004'],
            ['nama' => 'DEMO - Bu Rina Marlina', 'alamat' => 'Desa Suka Maju RT 05', 'no_hp' => '081200000005'],
        ];

        $created = [];
        foreach ($anggota as $data) {
            $created[] = Anggota::firstOrCreate(
                ['nama' => $data['nama']],
                $data
            );
        }

        return $created;
    }

    private function seedBlokLahan(array $anggotaList): array
    {
        // Default GeoJSON polygon for demo data (area Kalimantan Barat)
        $defaultGeoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[[109.3, -0.05], [109.31, -0.05], [109.31, -0.06], [109.3, -0.06], [109.3, -0.05]]],
        ]);

        $blokData = [
            // Blok 1: Kondisi normal, TM dewasa
            [
                'anggota_idx' => 0,
                'nama_blok' => 'DEMO - Blok A1 Normal',
                'luas_ha' => 4.5,
                'sph' => 136,
                'tahun_tanam' => 2012,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Lempung',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 2: gejala daun bagian bawah menguning
            [
                'anggota_idx' => 0,
                'nama_blok' => 'DEMO - Blok A2 Gejala Daun Menguning',
                'luas_ha' => 3.2,
                'sph' => 130,
                'tahun_tanam' => 2015,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Lempung Berpasir',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 3: gejala bercak pada daun tua
            [
                'anggota_idx' => 1,
                'nama_blok' => 'DEMO - Blok B1 Gejala Bercak Daun Tua',
                'luas_ha' => 5.0,
                'sph' => 140,
                'tahun_tanam' => 2010,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Podsolik Merah Kuning (PMK)',
                'topografi' => 'Bergelombang 15-30°',
            ],
            // Blok 4: gejala tepi daun tua
            [
                'anggota_idx' => 1,
                'nama_blok' => 'DEMO - Blok B2 Gejala Tepi Daun Tua',
                'luas_ha' => 2.8,
                'sph' => 125,
                'tahun_tanam' => 2008,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Berpasir',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 5: Curah hujan rendah (tunda)
            [
                'anggota_idx' => 2,
                'nama_blok' => 'DEMO - Blok C1 Hujan Rendah',
                'luas_ha' => 6.0,
                'sph' => 138,
                'tahun_tanam' => 2014,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Lempung',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 6: Curah hujan tinggi
            [
                'anggota_idx' => 2,
                'nama_blok' => 'DEMO - Blok C2 Hujan Tinggi',
                'luas_ha' => 3.5,
                'sph' => 132,
                'tahun_tanam' => 2016,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Liat',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 7: Drainase buruk
            [
                'anggota_idx' => 3,
                'nama_blok' => 'DEMO - Blok D1 Drainase Buruk',
                'luas_ha' => 4.0,
                'sph' => 128,
                'tahun_tanam' => 2013,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Gambut',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 8: TBM (tanaman muda)
            [
                'anggota_idx' => 3,
                'nama_blok' => 'DEMO - Blok D2 Gejala Daun Muda',
                'luas_ha' => 2.0,
                'sph' => 143,
                'tahun_tanam' => 2024,
                'fase_tanaman' => 'TBM',
                'jenis_tanah' => 'Tanah Lempung',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 9: Belum diobservasi
            [
                'anggota_idx' => 4,
                'nama_blok' => 'DEMO - Blok E1 Belum Observasi',
                'luas_ha' => 3.8,
                'sph' => 135,
                'tahun_tanam' => 2011,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Aluvial',
                'topografi' => 'Datar 0-15°',
            ],
            // Blok 10: Tanpa polygon (belum ada GeoJSON)
            [
                'anggota_idx' => 4,
                'nama_blok' => 'DEMO - Blok E2 Tanpa Polygon',
                'luas_ha' => 2.5,
                'sph' => 130,
                'tahun_tanam' => 2017,
                'fase_tanaman' => 'TM',
                'jenis_tanah' => 'Tanah Lempung',
                'topografi' => 'Datar 0-15°',
            ],
        ];

        $created = [];
        foreach ($blokData as $data) {
            $anggota = $anggotaList[$data['anggota_idx']];
            unset($data['anggota_idx']);

            $created[] = BlokLahan::firstOrCreate(
                ['nama_blok' => $data['nama_blok']],
                array_merge($data, [
                    'anggota_id' => $anggota->id,
                    'koordinat_geojson' => $data['nama_blok'] === 'DEMO - Blok E2 Tanpa Polygon' ? '' : $defaultGeoJson,
                ])
            );
        }

        return $created;
    }

    private function seedKondisiLahan(array $blokList): void
    {
        $kondisiData = [
            // Blok 0: Normal
            [
                'blok_idx' => 0,
                'tanggal_observasi' => now()->subDays(15)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(90)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 180.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Hijau Normal',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Baik',
                'ada_gulma_dominan' => false,
                'ada_serangan_hama' => false,
            ],
            // Blok 1: indikasi visual N
            [
                'blok_idx' => 1,
                'tanggal_observasi' => now()->subDays(10)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(100)->toDateString(),
                'metode_pengukuran_ph' => 'kertas_lakmus',
                'curah_hujan_mm_bulanan' => 150.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Daun Bawah Menguning',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Baik',
                'ada_gulma_dominan' => false,
                'ada_serangan_hama' => false,
            ],
            // Blok 2: indikasi visual K
            [
                'blok_idx' => 2,
                'tanggal_observasi' => now()->subDays(7)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(120)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 200.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Bercak Kuning/Transparan pada Daun Tua',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Baik',
                'ada_gulma_dominan' => true,
                'ada_serangan_hama' => false,
            ],
            // Blok 3: Gejala berat
            [
                'blok_idx' => 3,
                'tanggal_observasi' => now()->subDays(5)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(150)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 170.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Tepi Daun Tua Menguning pada Bagian Terbuka',
                'kondisi_pelepah' => 'Kering Prematur',
                'kondisi_drainase' => 'Cukup',
                'ada_gulma_dominan' => true,
                'ada_serangan_hama' => true,
            ],
            // Blok 4: Curah hujan rendah
            [
                'blok_idx' => 4,
                'tanggal_observasi' => now()->subDays(12)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(80)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 50.0,
                'curah_hujan_kategori' => 'Rendah',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Hijau Normal',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Baik',
                'ada_gulma_dominan' => false,
                'ada_serangan_hama' => false,
            ],
            // Blok 5: Curah hujan tinggi
            [
                'blok_idx' => 5,
                'tanggal_observasi' => now()->subDays(8)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(70)->toDateString(),
                'metode_pengukuran_ph' => 'kertas_lakmus',
                'curah_hujan_mm_bulanan' => 320.0,
                'curah_hujan_kategori' => 'Sangat Tinggi',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Hijau Normal',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Cukup',
                'ada_gulma_dominan' => false,
                'ada_serangan_hama' => false,
            ],
            // Blok 6: Drainase buruk
            [
                'blok_idx' => 6,
                'tanggal_observasi' => now()->subDays(6)->toDateString(),
                'tanggal_pemupukan_terakhir' => now()->subDays(65)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 220.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Hijau Normal',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Buruk — Tergenang',
                'ada_gulma_dominan' => true,
                'ada_serangan_hama' => false,
            ],
            // Blok 7: Tanaman muda TBM
            [
                'blok_idx' => 7,
                'tanggal_observasi' => now()->subDays(20)->toDateString(),
                'metode_pengukuran_ph' => 'ph_meter',
                'curah_hujan_mm_bulanan' => 190.0,
                'curah_hujan_kategori' => 'Normal',
                'periode_curah_hujan' => now()->format('Y-m'),
                'sumber_curah_hujan' => 'open-meteo',
                'warna_daun' => 'Daun Muda Berbentuk Kait atau Memendek',
                'kondisi_pelepah' => 'Normal',
                'kondisi_drainase' => 'Baik',
                'ada_gulma_dominan' => false,
                'ada_serangan_hama' => false,
            ],
            // Blok 8: tidak ada kondisi (belum diobservasi)
            // Blok 9: tanpa polygon — tetap ada kondisi
        ];

        foreach ($kondisiData as $data) {
            $blok = $blokList[$data['blok_idx']];
            unset($data['blok_idx']);

            // Skip if blok not found
            if (! $blok) {
                continue;
            }

            KondisiLahan::firstOrCreate(
                [
                    'blok_lahan_id' => $blok->id,
                    'tanggal_observasi' => $data['tanggal_observasi'],
                ],
                array_merge($data, ['blok_lahan_id' => $blok->id])
            );
        }
    }
}
