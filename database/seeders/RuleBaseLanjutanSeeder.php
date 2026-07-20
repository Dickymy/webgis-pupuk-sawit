<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

class RuleBaseLanjutanSeeder extends Seeder
{
    /**
     * Seed tabel rule_bases_lanjutan dengan aturan Rule-Based System
     * berdasarkan gejala visual, pH tanah, kondisi iklim, dan umur tanaman.
     */
    public function run(): void
    {
        // Hanya seed jika tabel masih kosong — aman untuk re-deploy di Railway
        if (RuleBaseLanjutan::count() > 0) {
            $this->command->info('RuleBaseLanjutan sudah ada datanya, skip seeding.');

            return;
        }

        $rules = [

            // ─── GRUP 1: DEFISIENSI NITROGEN (N) ───────────────────────────
            [
                'kondisi_warna_daun' => 'Kuning Merata',
                'kondisi_defisiensi' => 'N',
                'indikasi_masalah' => 'Defisiensi Nitrogen — Klorosis Umum',
                'jenis_pupuk_utama' => 'Urea (46% N)',
                'jenis_pupuk_pendukung' => 'ZA (21% N)',
                'dosis_anjuran' => '1.5–2.0 kg Urea/pokok, 2–3 kali/tahun',
                'metode_aplikasi' => 'Ditabur melingkar 1–2 m dari pangkal batang, tutup tanah',
                'waktu_aplikasi' => 'Awal musim hujan (Maret–April) dan pertengahan tahun',
                'saran_tindakan' => 'Segera berikan pupuk nitrogen. Periksa apakah ada leaching akibat drainase buruk. Aplikasikan saat tanah lembab.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
            ],
            [
                'kondisi_warna_daun' => 'Hijau Pucat',
                'kondisi_defisiensi' => 'N',
                'indikasi_masalah' => 'Defisiensi Nitrogen Ringan — Pertumbuhan Lambat',
                'jenis_pupuk_utama' => 'Urea (46% N)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '1.0–1.5 kg Urea/pokok, 2 kali/tahun',
                'metode_aplikasi' => 'Ditabur melingkar, campurkan dengan pupuk organik jika tersedia',
                'waktu_aplikasi' => 'Awal dan pertengahan musim hujan',
                'saran_tindakan' => 'Tingkatkan dosis nitrogen secara bertahap. Monitor warna daun setiap 1 bulan.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 4,
            ],

            // ─── GRUP 2: DEFISIENSI KALIUM (K) ────────────────────────────
            [
                'kondisi_warna_daun' => 'Oranye/Kemerahan',
                'kondisi_defisiensi' => 'K',
                'indikasi_masalah' => 'Defisiensi Kalium — Orange Frond (OF)',
                'jenis_pupuk_utama' => 'KCl (60% K2O)',
                'jenis_pupuk_pendukung' => 'SOP (Sulfate of Potash)',
                'dosis_anjuran' => '2.0–2.5 kg KCl/pokok, 2 kali/tahun',
                'metode_aplikasi' => 'Sebar di piringan, hindari kontak langsung dengan akar',
                'waktu_aplikasi' => 'Sebelum musim hujan, September–Oktober',
                'saran_tindakan' => 'Orange Frond adalah gejala defisiensi K berat. Lakukan aplikasi darurat KCl. Periksa pH tanah — jika < 4.5, lakukan pengapuran dulu.',
                'status_kebutuhan' => 'Darurat',
                'prioritas' => 1,
            ],
            [
                'kondisi_warna_daun' => 'Kuning Tepi',
                'kondisi_defisiensi' => 'K',
                'indikasi_masalah' => 'Defisiensi Kalium Sedang — Marginal Chlorosis',
                'jenis_pupuk_utama' => 'KCl (60% K2O)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '1.5–2.0 kg KCl/pokok, 2 kali/tahun',
                'metode_aplikasi' => 'Sebar merata di piringan pohon radius 2 m',
                'waktu_aplikasi' => 'Awal musim hujan',
                'saran_tindakan' => 'Segera aplikasikan KCl. Pastikan drainase lahan baik agar tidak terjadi leaching.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
            ],

            // ─── GRUP 3: DEFISIENSI MAGNESIUM (Mg) ────────────────────────
            [
                'kondisi_warna_daun' => 'Kuning Antar Tulang',
                'kondisi_defisiensi' => 'Mg',
                'indikasi_masalah' => 'Defisiensi Magnesium — Interveinal Chlorosis',
                'jenis_pupuk_utama' => 'Kieserit (27% MgO)',
                'jenis_pupuk_pendukung' => 'Dolomit',
                'dosis_anjuran' => '1.0–1.5 kg Kieserit/pokok, 1–2 kali/tahun',
                'metode_aplikasi' => 'Sebar di piringan, dapat dikombinasikan dengan Dolomit sebagai pengapuran',
                'waktu_aplikasi' => 'Awal musim hujan',
                'saran_tindakan' => 'Defisiensi Mg sering terjadi di tanah masam dan berpasir. Periksa pH tanah — pengapuran dengan Dolomit lebih efektif jika pH < 5.0.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 4: DEFISIENSI BORON (B) ─────────────────────────────
            [
                'kondisi_defisiensi' => 'B',
                'kondisi_pelepah' => 'Pertumbuhan Terhambat',
                'indikasi_masalah' => 'Defisiensi Boron — Pucuk Abnormal / Blind Pocket',
                'jenis_pupuk_utama' => 'Borax (Na2B4O7·10H2O)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '50–100 g Borax/pokok, 1 kali/tahun',
                'metode_aplikasi' => 'Larutkan dalam air dan siramkan ke piringan atau tabur kering',
                'waktu_aplikasi' => 'Awal musim hujan untuk memaksimalkan penyerapan',
                'saran_tindakan' => 'Defisiensi B ditandai daun kecil, bengkok, dan pucuk tidak berkembang. Jangan overdosis — boron toksik jika berlebihan. Dosis harus presisi.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
            ],

            // ─── GRUP 5: pH TANAH MASAM ───────────────────────────────────
            [
                'kondisi_ph_min' => 3.0,
                'kondisi_ph_max' => 4.5,
                'indikasi_masalah' => 'pH Sangat Masam — Penghambatan Penyerapan Unsur Hara',
                'jenis_pupuk_utama' => 'Dolomit (Kapur Pertanian)',
                'jenis_pupuk_pendukung' => 'Kieserit',
                'dosis_anjuran' => '500–1000 kg Dolomit/Ha, 1–2 kali/tahun tergantung pH',
                'metode_aplikasi' => 'Sebar merata di antara baris tanaman dan di piringan, masukkan ke lapisan atas tanah 10 cm',
                'waktu_aplikasi' => '2–3 bulan sebelum aplikasi pupuk kimia utama',
                'saran_tindakan' => 'pH < 4.5 sangat menghambat penyerapan P, Mg, Ca, dan Mo. Lakukan pengapuran dulu. Ulangi uji pH setelah 3 bulan. Hindari aplikasi Urea dan KCl sebelum pH naik ke 5.0+.',
                'status_kebutuhan' => 'Darurat',
                'prioritas' => 1,
            ],
            [
                'kondisi_ph_min' => 4.5,
                'kondisi_ph_max' => 5.5,
                'indikasi_masalah' => 'pH Masam — Efisiensi Pupuk Rendah',
                'jenis_pupuk_utama' => 'Dolomit (Kapur Pertanian)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '250–500 kg Dolomit/Ha per aplikasi',
                'metode_aplikasi' => 'Sebar di piringan dan gawangan',
                'waktu_aplikasi' => 'Awal musim hujan',
                'saran_tindakan' => 'Lakukan pengapuran ringan untuk menaikkan pH ke 5.5–6.5 (optimal sawit). Pupuk NPK tetap bisa diberikan bersamaan dengan dosis standar.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 4,
            ],

            // ─── GRUP 6: KONDISI DRAINASE BURUK ──────────────────────────
            [
                'kondisi_drainase' => 'Buruk — Tergenang',
                'kondisi_kelembaban' => null,
                'indikasi_masalah' => 'Waterlogging — Akar Kekurangan Oksigen dan Leaching Hara',
                'jenis_pupuk_utama' => 'MOP / KCl (melalui jalur daun — foliar)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Tunda pupuk tanah; gunakan 0.5% larutan KNO3 semprot daun',
                'metode_aplikasi' => 'Pupuk foliar spray pada pagi hari, konsentrasi rendah',
                'waktu_aplikasi' => 'Setelah kondisi genangan mereda (3–5 hari)',
                'saran_tindakan' => 'TUNDA SEMUA APLIKASI PUPUK TANAH. Kondisi tergenang menyebabkan denitrifikasi dan leaching masif. Perbaiki drainase parit. Setelah drainase baik, lakukan pemupukan kembali dengan dosis penuh.',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 1,
            ],

            // ─── GRUP 7: MUSIM KEMARAU PANJANG ───────────────────────────
            [
                'kondisi_musim' => null,
                'kondisi_kelembaban' => 'Sangat Kering',
                'indikasi_masalah' => 'Cekaman Kekeringan — Efisiensi Pupuk Sangat Rendah',
                'jenis_pupuk_utama' => 'Pupuk Organik / Kompos',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '20–30 kg kompos/pokok sebagai mulsa piringan',
                'metode_aplikasi' => 'Aplikasi sebagai mulsa tebal 5–10 cm di piringan untuk menjaga kelembaban',
                'waktu_aplikasi' => 'Awal kemarau sebelum tanah mengering parah',
                'saran_tindakan' => 'TUNDA pupuk kimia (Urea, KCl) saat kemarau sangat kering — pupuk tidak terlarut dan bisa membakar akar. Fokus pada mulsa organik dan konservasi air. Jadwalkan pupuk kimia saat hujan mulai turun.',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 2,
            ],
            [
                'kondisi_musim' => 'Musim Kemarau',
                'kondisi_kelembaban' => 'Kering',
                'indikasi_masalah' => 'Kemarau — Perlu Penyesuaian Dosis Pupuk',
                'jenis_pupuk_utama' => 'Urea + KCl (dosis dikurangi 30%)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '70% dari dosis normal; pecah menjadi dosis lebih kecil',
                'metode_aplikasi' => 'Aplikasi di pagi hari saat ada embun atau setelah hujan ringan',
                'waktu_aplikasi' => 'Segera setelah hujan pertama turun minimal 20mm',
                'saran_tindakan' => 'Kurangi dosis dan jadwalkan setelah hujan pertama. Jangan biarkan pupuk granul di tanah kering > 2 minggu.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 5,
            ],

            // ─── GRUP 8: TANAMAN MUDA (< 3 tahun) ────────────────────────
            [
                'kondisi_kategori_umur' => 'Belum Menghasilkan',
                'kondisi_warna_daun' => 'Kuning Merata',
                'indikasi_masalah' => 'Bibit/TBM Defisiensi Nitrogen — Pertumbuhan Vegetatif Terhambat',
                'jenis_pupuk_utama' => 'NPK Majemuk 15-15-6-4 (formula TBM)',
                'jenis_pupuk_pendukung' => 'Urea',
                'dosis_anjuran' => 'Bulan 1–12: 100–350 g NPK/pokok/aplikasi; Bulan 13–24: 350–700 g/pokok',
                'metode_aplikasi' => 'Ditabur melingkar 30–60 cm dari batang, sesuai perkembangan pelepah',
                'waktu_aplikasi' => 'Setiap 3–4 bulan sekali selama fase TBM',
                'saran_tindakan' => 'Tanaman TBM sangat sensitif terhadap defisiensi N. Gunakan NPK formula khusus TBM. Jangan gunakan Urea tunggal dosis tinggi pada TBM karena risiko terbakar.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
            ],

            // ─── GRUP 9: TANAMAN TUA (> 25 tahun) ────────────────────────
            [
                'kondisi_kategori_umur' => 'Tua Renta',
                'indikasi_masalah' => 'Tanaman Tua Renta — Efisiensi Pemupukan Sangat Rendah',
                'jenis_pupuk_utama' => 'Evaluasi Ekonomis Sebelum Pemupukan',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Kurangi dosis 40–50% dari standar atau pertimbangkan replanting',
                'metode_aplikasi' => 'Aplikasi minimal untuk mempertahankan produksi sisa',
                'waktu_aplikasi' => 'Sekali per tahun cukup',
                'saran_tindakan' => 'Tanaman > 25 tahun memiliki efisiensi penyerapan hara rendah dan biaya produksi tinggi. Lakukan analisis ekonomis: apakah biaya pupuk masih menguntungkan vs pertimbangan replanting.',
                'status_kebutuhan' => 'Tunda',
                'prioritas' => 8,
            ],

            // ─── GRUP 10: KONDISI NORMAL ──────────────────────────────────
            [
                'kondisi_warna_daun' => 'Hijau Normal',
                'kondisi_ph_min' => 5.5,
                'kondisi_ph_max' => 6.5,
                'kondisi_drainase' => 'Baik',
                'indikasi_masalah' => 'Kondisi Optimal — Pemupukan Standar',
                'jenis_pupuk_utama' => 'Urea + KCl (program rutin)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Sesuai program pemupukan standar kebun (SPH dan umur)',
                'metode_aplikasi' => 'Sebar melingkar di piringan, kombinasi Urea dan KCl terpisah aplikasi',
                'waktu_aplikasi' => '2 kali setahun: Maret–April dan September–Oktober',
                'saran_tindakan' => 'Kondisi lahan baik. Lanjutkan program pemupukan standar. Monitor rutin setiap 3 bulan.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 9,
            ],

            // ─── GRUP 11: DEFISIENSI FOSFOR (P) ──────────────────────────
            [
                'kondisi_warna_daun' => 'Coklat Ujung',
                'kondisi_defisiensi' => 'P',
                'indikasi_masalah' => 'Defisiensi Fosfor — Nekrosis Ujung Daun',
                'jenis_pupuk_utama' => 'TSP / SP-36 (36% P2O5)',
                'jenis_pupuk_pendukung' => 'Rock Phosphate (untuk tanah masam)',
                'dosis_anjuran' => '0.75–1.0 kg TSP/pokok, 1–2 kali/tahun',
                'metode_aplikasi' => 'Benamkan 5–10 cm di tanah piringan untuk mengurangi fiksasi',
                'waktu_aplikasi' => 'Awal musim hujan',
                'saran_tindakan' => 'Fosfor mudah terfiksasi di tanah masam. Jika pH < 5.0, gunakan Rock Phosphate yang lebih efektif. Tingkatkan bahan organik tanah untuk meningkatkan ketersediaan P.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 12: SERANGAN HAMA + PEMUPUKAN ──────────────────────
            [
                'kondisi_warna_daun' => 'Bercak Nekrotik',
                'indikasi_masalah' => 'Kombinasi Serangan Hama/Penyakit + Defisiensi Mikro',
                'jenis_pupuk_utama' => 'Pupuk Daun Mikro (Zn, Fe, Mn campuran)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Sesuai label produk, biasanya 2–3 cc/liter air, spray 2–3 kali',
                'metode_aplikasi' => 'Semprot daun (foliar) pada pagi hari, kombinasi dengan fungisida/insektisida',
                'waktu_aplikasi' => 'Segera setelah deteksi gejala',
                'saran_tindakan' => 'PENTING: Atasi masalah hama/penyakit dulu sebelum optimasi pemupukan. Konsultasikan dengan PPL setempat untuk identifikasi hama spesifik. Pupuk foliar mikro membantu pemulihan lebih cepat.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
                'keterangan_rule' => 'Rule ini menangkap bercak nekrotik yang bisa disebabkan hama atau defisiensi mikro',
            ],

            // ─── GRUP 13: DEFISIENSI BESI (Fe) ───────────────────────────
            [
                'kondisi_warna_daun' => 'Kuning Antar Tulang',
                'kondisi_defisiensi' => 'Fe',
                'kondisi_ph_min' => 6.5,
                'indikasi_masalah' => 'Defisiensi Besi (Fe) — pH Terlalu Tinggi',
                'jenis_pupuk_utama' => 'FeSO4 (Ferrous Sulfate)',
                'jenis_pupuk_pendukung' => 'Pupuk Daun Fe-chelate',
                'dosis_anjuran' => '50–75 g FeSO4/pokok dicampur air, atau 1–2 g/liter larutan Fe-chelate spray',
                'metode_aplikasi' => 'Siram ke piringan atau spray daun. Pada pH tinggi, foliar lebih efektif.',
                'waktu_aplikasi' => 'Saat gejala terlihat, ulangi setiap 3 minggu hingga gejala hilang',
                'saran_tindakan' => 'Defisiensi Fe sering terjadi saat pH terlalu tinggi (> 6.5). Hindari pengapuran berlebihan. Aplikasi Fe-chelate lewat daun lebih efektif daripada pupuk tanah pada kondisi pH tinggi.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 14: KONDISI PELEPAH ABNORMAL ───────────────────────
            [
                'kondisi_pelepah' => 'Kering Prematur',
                'indikasi_masalah' => 'Pelepah Kering Prematur — Stres Air atau Defisiensi K',
                'jenis_pupuk_utama' => 'KCl (60% K2O)',
                'jenis_pupuk_pendukung' => 'Pupuk Organik',
                'dosis_anjuran' => '1.5–2.0 kg KCl/pokok + 10–15 kg kompos/pokok',
                'metode_aplikasi' => 'KCl sebar di piringan, kompos sebagai mulsa',
                'waktu_aplikasi' => 'Awal musim hujan atau segera setelah irigasi tersedia',
                'saran_tindakan' => 'Pelepah kering prematur bisa menandakan defisiensi K atau stres kekeringan. Pastikan pasokan air cukup. Aplikasi KCl dan mulsa organik untuk memperbaiki kondisi.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 15: KONDISI TANDAN ABNORMAL ────────────────────────
            [
                'kondisi_tandan' => 'Rontok Prematur',
                'indikasi_masalah' => 'Rontok Tandan Prematur — Defisiensi B atau K',
                'jenis_pupuk_utama' => 'Borax + KCl',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => '75 g Borax + 1.5 kg KCl per pokok',
                'metode_aplikasi' => 'Tabur Borax di piringan, KCl sebar merata radius 2 m',
                'waktu_aplikasi' => 'Segera saat gejala terdeteksi',
                'saran_tindakan' => 'Rontok tandan prematur sangat berkaitan dengan defisiensi Boron dan Kalium. Segera aplikasikan kombinasi Borax + KCl. Monitor hasil setelah 1 siklus tandan (3–4 bulan).',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 2,
            ],
            [
                'kondisi_tandan' => 'Busuk Pangkal',
                'indikasi_masalah' => 'Busuk Pangkal Tandan — Infeksi Ganoderma atau Kelebihan Air',
                'jenis_pupuk_utama' => 'Pupuk Silika + Kalium',
                'jenis_pupuk_pendukung' => 'Pupuk Organik',
                'dosis_anjuran' => 'Perbaiki drainase dulu; 1.5 kg KCl + aplikasi silika sesuai anjuran',
                'metode_aplikasi' => 'Kurangi kelembaban piringan, perbaiki drainase, baru pupuk',
                'waktu_aplikasi' => 'Setelah kondisi drainase diperbaiki',
                'saran_tindakan' => 'Busuk pangkal tandan kemungkinan besar disebabkan Ganoderma atau kondisi tergenang. PRIORITAS: perbaiki drainase dan konsultasi pengendalian Ganoderma. Pemupukan dilakukan setelah kondisi membaik.',
                'status_kebutuhan' => 'Darurat',
                'prioritas' => 1,
            ],

            // ─── GRUP 16: MUSIM HUJAN — OPTIMASI PEMUPUKAN ───────────────
            [
                'kondisi_musim' => 'Musim Hujan',
                'kondisi_kelembaban' => 'Normal',
                'indikasi_masalah' => 'Kondisi Optimal untuk Pemupukan — Musim Hujan Normal',
                'jenis_pupuk_utama' => 'Urea + KCl (dosis penuh)',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'Dosis penuh sesuai program standard, split aplikasi 2–3 kali',
                'metode_aplikasi' => 'Tabur di piringan saat tanah lembab (1–2 hari setelah hujan)',
                'waktu_aplikasi' => 'Saat musim hujan aktif, hindari sebelum hujan lebat (> 50mm/hari)',
                'saran_tindakan' => 'Kondisi optimal untuk pemupukan. Aplikasikan dosis penuh. Hindari aplikasi saat hujan sangat lebat untuk mencegah leaching.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 6,
            ],

            // ─── GRUP 17: DEFISIENSI SENG (Zn) ───────────────────────────
            [
                'kondisi_defisiensi' => 'Zn',
                'kondisi_warna_daun' => 'Kuning Merata',
                'indikasi_masalah' => 'Defisiensi Seng (Zn) — Klorosis Daun Muda',
                'jenis_pupuk_utama' => 'ZnSO4 (Zinc Sulfate)',
                'jenis_pupuk_pendukung' => 'Pupuk Daun Zn-chelate',
                'dosis_anjuran' => '50–100 g ZnSO4/pokok atau 2–3 g/liter larutan Zn spray daun',
                'metode_aplikasi' => 'Tabur di piringan atau spray daun (lebih efektif untuk gejala akut)',
                'waktu_aplikasi' => 'Saat gejala terlihat; ulangi setiap 4–6 minggu',
                'saran_tindakan' => 'Defisiensi Zn sering menyerang daun muda. Foliar spray Zn-chelate memberikan respons lebih cepat. Periksa pH tanah — fiksasi Zn meningkat pada pH > 6.5.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 18: SERANGAN HAMA TANPA GEJALA DAUN SPESIFIK ───────
            [
                'ada_serangan_hama' => true,
                'indikasi_masalah' => 'Serangan Hama Aktif — Pemupukan Dukung Pemulihan',
                'jenis_pupuk_utama' => 'NPK Lengkap + Pupuk Daun Mikro',
                'jenis_pupuk_pendukung' => null,
                'dosis_anjuran' => 'NPK standar + foliar mikro 2–3 cc/liter, aplikasi 2× sebulan selama pemulihan',
                'metode_aplikasi' => 'Kombinasi pupuk tanah + spray daun saat tanaman sedang dipulihkan',
                'waktu_aplikasi' => 'Bersamaan atau segera setelah pengendalian hama',
                'saran_tindakan' => 'Tangani hama dengan insektisida/fungisida yang tepat terlebih dahulu. Pupuk pendukung pemulihan diberikan bersamaan untuk mempercepat regenerasi jaringan daun.',
                'status_kebutuhan' => 'Segera',
                'prioritas' => 3,
            ],

            // ─── GRUP 19: TANAMAN REMAJA PRODUKTIF ───────────────────────
            [
                'kondisi_kategori_umur' => 'Remaja',
                'kondisi_warna_daun' => 'Hijau Normal',
                'indikasi_masalah' => 'Tanaman Remaja — Program Pemupukan Intensif',
                'jenis_pupuk_utama' => 'NPK + Urea + KCl (program TM muda)',
                'jenis_pupuk_pendukung' => 'Kieserit',
                'dosis_anjuran' => 'Urea 1.0–1.5 kg + KCl 1.0 kg + Kieserit 0.5 kg per pokok per tahun',
                'metode_aplikasi' => 'Split aplikasi 2–3 kali/tahun di piringan',
                'waktu_aplikasi' => 'Februari–Maret dan Agustus–September',
                'saran_tindakan' => 'Fase remaja adalah periode kritis untuk membangun struktur tanaman. Pastikan program pemupukan lengkap dan konsisten.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 6,
            ],

            // ─── GRUP 20: TANAMAN MENGHASILKAN TUA ───────────────────────
            [
                'kondisi_kategori_umur' => 'Menghasilkan Tua',
                'kondisi_warna_daun' => 'Hijau Pucat',
                'indikasi_masalah' => 'Tanaman Menghasilkan Tua — Penurunan Efisiensi Hara',
                'jenis_pupuk_utama' => 'Urea + KCl (dosis optimal)',
                'jenis_pupuk_pendukung' => 'Kieserit + Borax',
                'dosis_anjuran' => 'Urea 2.5–3.0 kg + KCl 2.0–2.5 kg + Kieserit 1.0 kg per pokok per tahun',
                'metode_aplikasi' => 'Split 2 kali aplikasi, tabur merata di piringan radius 2–3 m',
                'waktu_aplikasi' => 'Maret–April dan September–Oktober',
                'saran_tindakan' => 'Tanaman fase TM tua membutuhkan dosis tinggi karena kapasitas penyerapan mulai menurun. Pertahankan program pemupukan penuh dan tambahkan Kieserit serta Borax untuk mikro hara.',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 5,
            ],
        ];

        foreach ($rules as $rule) {
            RuleBaseLanjutan::create($rule);
        }

        $this->command->info('Rule Base Lanjutan berhasil di-seed: '.count($rules).' rules.');
    }
}
