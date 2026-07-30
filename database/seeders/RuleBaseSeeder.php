<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

/**
 * Menyediakan tujuh rule sistem yang memiliki sumber akademik.
 *
 * Rule buatan admin tidak diubah. Rule sistem juga tidak ditimpa saat seeder
 * dijalankan ulang sehingga perubahan melalui aplikasi tetap tersimpan.
 */
class RuleBaseSeeder extends Seeder
{
    public function run(): void
    {
        $rules = $this->rules();
        $systemCodes = array_keys($rules);

        RuleBaseLanjutan::query()
            ->where('is_system_rule', true)
            ->where(function ($query) use ($systemCodes) {
                $query->whereNull('kode_rule')
                    ->orWhereNotIn('kode_rule', $systemCodes);
            })
            ->delete();

        foreach ($rules as $code => $attributes) {
            RuleBaseLanjutan::firstOrCreate(['kode_rule' => $code], $attributes);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rules(): array
    {
        $visualSource = $this->source(
            title: 'Teknik Pemupukan Tanaman Menghasilkan Kelapa Sawit Menggunakan Prinsip Empat Tepat (4T)',
            authors: 'Barus, Hutagalung, Syarovy, dan Fauzi',
            year: 2025,
            pages: '23-38',
            table: '1',
        );

        $rainfallSource = $this->source(
            title: 'Rekomendasi Waktu Pemupukan untuk 22 Zona Perkebunan Kelapa Sawit di Indonesia Berdasarkan Pola Curah Hujan',
            authors: 'Pradiko, Rahutomo, Siregar, dan Darlan',
            year: 2021,
            pages: '67-80',
            table: 'Kriteria curah hujan untuk waktu pemupukan',
        );

        return [
            'VIS-N-01' => $this->rule($visualSource, [
                'kondisi_warna_daun' => 'Daun Bawah Menguning',
                'indikasi_masalah' => 'Gejala daun bagian bawah menguning',
                'jenis_pupuk_utama' => 'Urea',
                'dosis_anjuran' => 'Kebutuhan Urea dihitung dari tabel umur dan fase tanaman dalam Pahan (2013).',
                'metode_aplikasi' => 'Ikuti petunjuk aplikasi pada hasil rekomendasi.',
                'waktu_aplikasi' => 'Mengikuti hasil pemeriksaan kesiapan pemupukan.',
                'saran_tindakan' => 'Gejala ini dapat berkaitan dengan kekurangan nitrogen, yaitu unsur penyubur daun. Periksa riwayat pemupukan dan, bila tersedia, hasil analisis daun atau tanah. Gejala tidak menaikkan dosis secara otomatis.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
                'jenis_rule' => 'DIAGNOSIS_VISUAL',
                'tingkat_keparahan' => 'SEDANG',
                'kategori_kesimpulan' => 'GEJALA_DAUN',
                'keterangan_rule' => 'IF daun bagian bawah menguning THEN tampilkan indikasi awal dan anjurkan pemeriksaan lanjutan.',
                'catatan_validasi' => 'PPKS (2025) menyebut daun menguning mulai dari bagian bawah sebagai gejala kekurangan nitrogen. Dosis Urea tetap berasal dari tabel umur dan fase Pahan.',
            ]),
            'VIS-K-02' => $this->rule($visualSource, [
                'kondisi_warna_daun' => 'Bercak Kuning/Transparan pada Daun Tua',
                'indikasi_masalah' => 'Gejala bercak kuning atau transparan pada daun tua',
                'jenis_pupuk_utama' => 'KCl',
                'dosis_anjuran' => 'Kebutuhan KCl dihitung dari tabel umur dan fase tanaman dalam Pahan (2013).',
                'metode_aplikasi' => 'Ikuti petunjuk aplikasi pada hasil rekomendasi.',
                'waktu_aplikasi' => 'Mengikuti hasil pemeriksaan kesiapan pemupukan.',
                'saran_tindakan' => 'Gejala ini dapat berkaitan dengan kekurangan kalium, yaitu unsur yang membantu kekuatan tanaman dan pembentukan buah. Konfirmasi dengan riwayat pemupukan dan analisis daun bila tersedia. Gejala tidak menaikkan dosis secara otomatis.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
                'jenis_rule' => 'DIAGNOSIS_VISUAL',
                'tingkat_keparahan' => 'SEDANG',
                'kategori_kesimpulan' => 'GEJALA_DAUN',
                'keterangan_rule' => 'IF terdapat bercak kuning atau transparan pada daun tua THEN tampilkan indikasi awal dan anjurkan pemeriksaan lanjutan.',
                'catatan_validasi' => 'PPKS (2025) mencantumkan bercak kuning atau transparan pada daun tua sebagai gejala kekurangan kalium.',
            ]),
            'VIS-MG-01' => $this->rule($visualSource, [
                'kondisi_warna_daun' => 'Tepi Daun Tua Menguning pada Bagian Terbuka',
                'indikasi_masalah' => 'Gejala tepi daun tua menguning pada bagian terbuka',
                'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
                'dosis_anjuran' => 'Tidak ada dosis otomatis dari pengamatan visual.',
                'waktu_aplikasi' => 'Setelah pemeriksaan lanjutan bila tindakan tambahan diperlukan.',
                'saran_tindakan' => 'Gejala ini dapat berkaitan dengan kekurangan magnesium, yaitu unsur pembentuk warna hijau daun. Lakukan pemeriksaan daun atau tanah sebelum menentukan bahan dan dosis tambahan.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
                'jenis_rule' => 'DIAGNOSIS_VISUAL',
                'tingkat_keparahan' => 'RINGAN',
                'kategori_kesimpulan' => 'PERLU_PEMERIKSAAN',
                'keterangan_rule' => 'IF tepi daun tua pada bagian terbuka menguning THEN minta pemeriksaan lanjutan tanpa menentukan pupuk otomatis.',
                'catatan_validasi' => 'PPKS (2025) menjelaskan gejala magnesium pada daun tua dan bagian yang terkena cahaya. Rule tidak menentukan pupuk atau dosis otomatis.',
            ]),
            'VIS-B-01' => $this->rule($visualSource, [
                'kondisi_warna_daun' => 'Daun Muda Berbentuk Kait atau Memendek',
                'indikasi_masalah' => 'Gejala bentuk daun muda tidak normal',
                'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
                'dosis_anjuran' => 'Tidak ada dosis otomatis dari pengamatan visual.',
                'waktu_aplikasi' => 'Setelah pemeriksaan lanjutan bila tindakan tambahan diperlukan.',
                'saran_tindakan' => 'Daun muda berbentuk kait atau memendek dapat berkaitan dengan kekurangan boron, yaitu unsur untuk pertumbuhan pucuk. Jangan menentukan Borax atau dosis lain hanya dari gejala visual; lakukan pemeriksaan lanjutan.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
                'jenis_rule' => 'DIAGNOSIS_VISUAL',
                'tingkat_keparahan' => 'RINGAN',
                'kategori_kesimpulan' => 'PERLU_PEMERIKSAAN',
                'keterangan_rule' => 'IF daun muda berbentuk kait atau memendek THEN minta pemeriksaan lanjutan tanpa menentukan pupuk otomatis.',
                'catatan_validasi' => 'PPKS (2025) mencantumkan hook leaf dan pemendekan anak daun sebagai gejala boron. PPKS (2023) mendukung kehati-hatian karena rentang kekurangan dan toksisitas boron sempit.',
            ]),
            'WAKTU-HUJAN-RENDAH' => $this->rule($rainfallSource, [
                'kondisi_curah_hujan_max_mm' => 59.9,
                'indikasi_masalah' => 'Curah hujan terlalu rendah untuk pemupukan',
                'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
                'dosis_anjuran' => 'Dosis tidak diubah; waktu aplikasi ditunda.',
                'waktu_aplikasi' => 'Tunggu hingga curah hujan dan kelembapan tanah mendukung.',
                'saran_tindakan' => 'Tunda pemupukan karena curah hujan di bawah 60 mm per bulan. Periksa kembali kondisi tanah sebelum menjadwalkan aplikasi.',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 1,
                'jenis_rule' => 'PEMBATAS_APLIKASI',
                'tingkat_keparahan' => 'NORMAL',
                'kategori_kesimpulan' => 'PEMUPUKAN_DITUNDA',
                'keterangan_rule' => 'IF curah hujan di bawah 60 mm/bulan THEN tunda pemupukan.',
                'catatan_validasi' => 'Pradiko dkk. (2021) merekomendasikan penundaan pemupukan pada curah hujan di bawah 60 mm per bulan.',
            ]),
            'WAKTU-HUJAN-OPTIMAL' => $this->rule($visualSource, [
                'kondisi_curah_hujan_min_mm' => 100.0,
                'kondisi_curah_hujan_max_mm' => 250.0,
                'indikasi_masalah' => 'Curah hujan mendukung waktu pemupukan',
                'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
                'dosis_anjuran' => 'Dosis tetap mengikuti tabel umur dan fase tanaman.',
                'waktu_aplikasi' => 'Dapat dijadwalkan jika syarat lapangan dan interval juga terpenuhi.',
                'saran_tindakan' => 'Curah hujan berada pada rentang yang mendukung. Tetap periksa drainase, kelembapan tanah, dan jarak dari pemupukan terakhir.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 5,
                'jenis_rule' => 'PEMBATAS_APLIKASI',
                'tingkat_keparahan' => 'NORMAL',
                'kategori_kesimpulan' => 'WAKTU_MENDUKUNG',
                'keterangan_rule' => 'IF curah hujan 100-250 mm/bulan THEN waktu mendukung, selama pemeriksaan lapangan lain terpenuhi.',
                'catatan_validasi' => 'Barus dkk. (2025) menyebut rentang curah hujan optimal 100-250 mm per bulan untuk pemupukan.',
            ]),
            'WAKTU-HUJAN-TINGGI' => $this->rule($rainfallSource, [
                'kondisi_curah_hujan_min_mm' => 300.1,
                'indikasi_masalah' => 'Curah hujan terlalu tinggi untuk pemupukan',
                'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
                'dosis_anjuran' => 'Dosis tidak diubah; waktu aplikasi ditunda.',
                'waktu_aplikasi' => 'Tunggu hingga curah hujan menurun dan lahan tidak tergenang.',
                'saran_tindakan' => 'Tunda pemupukan karena curah hujan di atas 300 mm per bulan meningkatkan risiko kehilangan pupuk. Periksa kembali kondisi lahan.',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 1,
                'jenis_rule' => 'PEMBATAS_APLIKASI',
                'tingkat_keparahan' => 'NORMAL',
                'kategori_kesimpulan' => 'PEMUPUKAN_DITUNDA',
                'keterangan_rule' => 'IF curah hujan di atas 300 mm/bulan THEN tunda pemupukan.',
                'catatan_validasi' => 'Pradiko dkk. (2021) merekomendasikan penundaan pemupukan pada curah hujan di atas 300 mm per bulan.',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function rule(array $source, array $attributes): array
    {
        return array_merge([
            'kondisi_warna_daun' => null,
            'kondisi_ph_min' => null,
            'kondisi_ph_max' => null,
            'kondisi_kelembaban' => null,
            'kondisi_curah_hujan_kategori' => null,
            'kondisi_curah_hujan_min_mm' => null,
            'kondisi_curah_hujan_max_mm' => null,
            'kondisi_musim' => null,
            'kondisi_drainase' => null,
            'kondisi_defisiensi' => null,
            'kondisi_kategori_umur' => null,
            'kondisi_pelepah' => null,
            'kondisi_tandan' => null,
            'ada_serangan_hama' => null,
            'ada_gulma_dominan' => null,
            'kondisi_intermediate' => null,
            'prasyarat_intermediate' => null,
            'jenis_pupuk_pendukung' => null,
            'metode_aplikasi' => null,
            'aktif' => true,
            'is_system_rule' => true,
            'status_validasi' => 'TERVERIFIKASI_SUMBER',
            'versi_rule' => '4.0',
        ], $source, $attributes);
    }

    /**
     * @return array<string, mixed>
     */
    private function source(
        string $title,
        string $authors,
        int $year,
        string $pages,
        string $table,
    ): array {
        return [
            'sumber_judul' => $title,
            'sumber_penulis' => $authors,
            'sumber_tahun' => $year,
            'sumber_halaman' => $pages,
            'sumber_tabel' => $table,
            'tingkat_bukti' => 'JURNAL',
        ];
    }
}
