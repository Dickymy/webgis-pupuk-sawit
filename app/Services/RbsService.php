<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RuleBaseLanjutan;
use App\Models\RekomendasiRbs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RbsService
{
    /**
     * Mapping warna daun → dugaan unsur hara (untuk confidence score).
     */
    private array $mappingVisualUnsur = [
        'Hijau Pucat'          => ['N'],
        'Kuning Merata'        => ['N', 'Zn'],
        'Kuning Tepi'          => ['K'],
        'Oranye/Kemerahan'     => ['K'],
        'Kuning Antar Tulang'  => ['Mg', 'Fe'],
        'Coklat Ujung'         => ['P', 'K'],
        'Bercak Nekrotik'      => ['K', 'P'],
    ];

    /**
     * Jalankan analisis RBS untuk satu blok lahan berdasarkan kondisi terbaru.
     *
     * @throws \Exception
     */
    public function analisis(BlokLahan $blok): array
    {
        // 1. Ambil kondisi lahan terbaru
        $kondisi = $blok->kondisiTerbaru;
        if (!$kondisi) {
            throw new \Exception("Data kondisi lahan belum tersedia untuk blok '{$blok->nama_blok}'.");
        }

        // 2. Cek kecukupan data (Fitur 7)
        $kecukupanData = $this->cekKecukupanData($kondisi);

        // 3. Cek apakah data kondisi cukup untuk analisis (minimal 1 field terisi)
        if (!$this->kondisiCukup($kondisi)) {
            return $this->hasilDataTidakCukup($blok, $kondisi, $kecukupanData);
        }

        // 4. Ambil kategori umur langsung dari blok (kriteria terintegrasi)
        $kategoriUmur = $blok->kategori_umur;

        // 5. Ambil semua rule aktif, urutkan dari prioritas tertinggi (nilai terkecil = lebih penting)
        $rules = RuleBaseLanjutan::aktif()->orderBy('prioritas')->get();

        // 6. Evaluasi setiap rule (Forward Chaining dengan Rule Chaining)
        $rulesTerpicu = [];
        $intermediateFlags = [];

        foreach ($rules as $rule) {
            if (!$this->cekPrasyaratIntermediate($rule, $intermediateFlags)) {
                continue;
            }

            if ($this->evaluasiRule($rule, $kondisi, $kategoriUmur)) {
                $rulesTerpicu[] = $rule;

                if (!empty($rule->kondisi_intermediate) && is_array($rule->kondisi_intermediate)) {
                    $intermediateFlags = array_merge($intermediateFlags, $rule->kondisi_intermediate);
                }
            }
        }

        // 7. Jika tidak ada rule terpicu, return status normal
        if (empty($rulesTerpicu)) {
            return $this->hasilNormal($blok, $kondisi, $kecukupanData);
        }

        // 8. Susun output dari semua rule terpicu
        return $this->susunHasil($blok, $kondisi, $rulesTerpicu, $kecukupanData);
    }

    /**
     * Jalankan analisis RBS untuk semua blok lahan yang memiliki kondisi.
     */
    public function analisisSemua(): array
    {
        $blokLahans = BlokLahan::whereHas('kondisiLahans')
            ->with(['kondisiTerbaru'])
            ->get();
        $results = [];
        $errors  = [];

        foreach ($blokLahans as $blok) {
            try {
                $results[] = [
                    'blok'   => $blok,
                    'result' => $this->analisis($blok),
                ];
            } catch (\Exception $e) {
                $errors[] = "Blok {$blok->nama_blok}: " . $e->getMessage();
            }
        }

        return ['results' => $results, 'errors' => $errors];
    }

    // ═══════════════════════════════════════════════════════════════════
    // FITUR 7: Cek Kecukupan Data
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Cek apakah data observasi cukup untuk rekomendasi yang kuat.
     */
    private function cekKecukupanData(KondisiLahan $kondisi): array
    {
        $fieldPenting = [
            'warna_daun'                 => 'Warna daun',
            'ph_tanah'                   => 'pH tanah',
            'kelembaban_tanah'           => 'Kelembaban tanah',
            'curah_hujan_kategori'       => 'Curah hujan',
            'musim_saat_ini'             => 'Musim saat ini',
            'kondisi_drainase'           => 'Kondisi drainase',
            'tanggal_pemupukan_terakhir' => 'Tanggal pemupukan terakhir',
        ];

        $dataKurang = [];
        $terisi = 0;

        foreach ($fieldPenting as $field => $label) {
            if ($kondisi->$field !== null && $kondisi->$field !== '') {
                $terisi++;
            } else {
                $dataKurang[] = $label;
            }
        }

        // Cek gejala_defisiensi terpisah
        $adaDugaanUnsur = !empty($kondisi->gejala_defisiensi);

        // Data dianggap cukup jika minimal 5 dari 7 field penting terisi
        $dataCukup = $terisi >= 5;

        // Atau tidak cukup jika: warna_daun kosong ATAU (pH kosong DAN drainase kosong)
        if ($kondisi->warna_daun === null) {
            $dataCukup = false;
        }
        if ($kondisi->ph_tanah === null && $kondisi->kondisi_drainase === null) {
            $dataCukup = false;
        }

        // Re-override: jika terisi >= 5, anggap cukup
        if ($terisi >= 5) {
            $dataCukup = true;
        }

        $pesan = $dataCukup
            ? 'Data observasi cukup untuk menjalankan analisis RBS.'
            : 'Data observasi belum cukup untuk menghasilkan rekomendasi yang kuat. Lengkapi data berikut: ' . implode(', ', $dataKurang) . '.';

        return [
            'data_cukup'  => $dataCukup,
            'data_kurang' => $dataKurang,
            'pesan'       => $pesan,
            'terisi'      => $terisi,
            'total_field' => count($fieldPenting),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // FITUR 3: Validitas Rekomendasi
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Tentukan validitas rekomendasi berdasarkan kelengkapan data.
     */
    private function tentukanValiditasRekomendasi(KondisiLahan $kondisi, array $kecukupanData): array
    {
        $warnaDaun    = $kondisi->warna_daun !== null;
        $phTanah      = $kondisi->ph_tanah !== null;
        $kelembaban   = $kondisi->kelembaban_tanah !== null;
        $curahHujan   = $kondisi->curah_hujan_kategori !== null;
        $drainase     = $kondisi->kondisi_drainase !== null;
        $tglPupuk     = $kondisi->tanggal_pemupukan_terakhir !== null;
        $musim        = $kondisi->musim_saat_ini !== null;

        // Cukup Kuat: warna daun + pH + (kelembaban ATAU curah hujan) + drainase
        $isCukupKuat = $warnaDaun
            && $phTanah
            && ($kelembaban || $curahHujan)
            && $drainase;

        if ($isCukupKuat) {
            $catatan = 'Rekomendasi cukup kuat karena didukung data warna daun, pH tanah, '
                . ($kelembaban ? 'kelembaban, ' : '')
                . ($curahHujan ? 'curah hujan, ' : '')
                . 'dan drainase.';
            return [
                'validitas' => 'Cukup Kuat',
                'catatan'   => rtrim($catatan, ', ') . '.',
            ];
        }

        // Default: Estimasi Visual
        $missing = [];
        if (!$phTanah) $missing[] = 'pH tanah';
        if (!$drainase) $missing[] = 'kondisi drainase';
        if (!$kelembaban && !$curahHujan) $missing[] = 'kelembaban/curah hujan';

        $catatan = 'Rekomendasi ini bersifat estimasi visual karena belum didukung data '
            . implode(' dan ', $missing) . '.';

        return [
            'validitas' => 'Estimasi Visual',
            'catatan'   => $catatan,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // FITUR 6: Confidence Score
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Hitung confidence score 0-100.
     */
    private function hitungConfidence(KondisiLahan $kondisi, array $rulesTerpicu, array $warnings = []): array
    {
        $score = 0;
        $details = [];

        // A. Kelengkapan Data — Maks 40 poin
        $fieldsPenting = [
            'warna_daun', 'ph_tanah', 'kelembaban_tanah', 'curah_hujan_kategori',
            'musim_saat_ini', 'kondisi_drainase', 'tanggal_pemupukan_terakhir',
        ];
        // Tambah gejala_defisiensi sebagai field ke-8
        $totalFields = count($fieldsPenting) + 1;
        $terisi = 0;
        foreach ($fieldsPenting as $f) {
            if ($kondisi->$f !== null && $kondisi->$f !== '') {
                $terisi++;
            }
        }
        if (!empty($kondisi->gejala_defisiensi)) {
            $terisi++;
        }

        $skorA = (int) round(($terisi / $totalFields) * 40);
        $score += $skorA;
        $details[] = "Kelengkapan data: {$terisi}/{$totalFields} field ({$skorA} poin)";

        // B. Jumlah Rule Terpicu — Maks 25 poin
        $jumlahRule = count($rulesTerpicu);
        $skorB = match(true) {
            $jumlahRule >= 3 => 25,
            $jumlahRule === 2 => 18,
            $jumlahRule === 1 => 12,
            default => 5,
        };
        $score += $skorB;
        $details[] = "Rule terpicu: {$jumlahRule} ({$skorB} poin)";

        // C. Kesesuaian Visual + Dugaan Unsur — Maks 20 poin
        $skorC = 0;
        $warnaDaun = $kondisi->warna_daun;
        $dugaanUnsur = $kondisi->gejala_defisiensi ?? [];

        if ($warnaDaun && !empty($dugaanUnsur)) {
            if ($this->isDugaanUnsurSesuaiWarnaDaun($warnaDaun, $dugaanUnsur)) {
                $skorC = 20;
            } else {
                $skorC = 10; // Ada data tapi tidak cocok mapping
            }
        } elseif ($warnaDaun || !empty($dugaanUnsur)) {
            $skorC = 5; // Hanya salah satu terisi
        }
        $score += $skorC;
        $details[] = "Kesesuaian visual-unsur: {$skorC} poin";

        // D. Penalti Data Kontradiktif — Maks -20 poin
        $penalti = 0;
        $warningsKonsistensi = $this->cekKonsistensiData($kondisi);
        $penalti = min(count($warningsKonsistensi) * 10, 20);
        $score -= $penalti;
        if ($penalti > 0) {
            $details[] = "Penalti kontradiksi: -{$penalti} poin";
        }

        // Clamp 0-100
        $score = max(0, min(100, $score));

        // Label
        if ($score >= 75) {
            $label = 'Tinggi';
        } elseif ($score >= 50) {
            $label = 'Sedang';
        } else {
            $label = 'Rendah';
        }

        // Catatan
        $dataKurangFields = [];
        foreach ($fieldsPenting as $f) {
            if ($kondisi->$f === null || $kondisi->$f === '') {
                $dataKurangFields[] = str_replace('_', ' ', $f);
            }
        }

        if ($label === 'Rendah') {
            $catatan = 'Keyakinan rendah karena data ' . implode(', ', array_slice($dataKurangFields, 0, 3)) . ' belum diisi.';
        } elseif ($label === 'Tinggi') {
            $catatan = 'Keyakinan tinggi karena data observasi lengkap dan beberapa rule saling mendukung.';
        } else {
            $catatan = 'Keyakinan sedang. Lengkapi data untuk meningkatkan akurasi rekomendasi.';
        }

        return [
            'score'        => $score,
            'label'        => $label,
            'catatan'      => $catatan,
            'data_kurang'  => $dataKurangFields,
        ];
    }

    /**
     * Cek apakah dugaan unsur sesuai dengan warna daun (mapping visual).
     */
    private function isDugaanUnsurSesuaiWarnaDaun(?string $warnaDaun, array $dugaanUnsur): bool
    {
        if (!$warnaDaun || empty($dugaanUnsur)) {
            return false;
        }

        $unsurCocok = $this->mappingVisualUnsur[$warnaDaun] ?? [];
        if (empty($unsurCocok)) {
            return false;
        }

        return !empty(array_intersect($dugaanUnsur, $unsurCocok));
    }

    /**
     * Cek konsistensi data (untuk penalti confidence).
     */
    private function cekKonsistensiData(KondisiLahan $kondisi): array
    {
        $warnings = [];

        $musim = $kondisi->musim_saat_ini;
        $kelembaban = $kondisi->kelembaban_tanah;
        $warnaDaun = $kondisi->warna_daun;
        $defisiensi = $kondisi->gejala_defisiensi ?? [];
        $curahHujan = $kondisi->curah_hujan_kategori;
        $drainase = $kondisi->kondisi_drainase;

        if ($musim === 'Musim Kemarau' && in_array($kelembaban, ['Lembab', 'Sangat Lembab'])) {
            $warnings[] = 'Musim kemarau tapi kelembaban tinggi';
        }
        if ($musim === 'Musim Hujan' && in_array($kelembaban, ['Kering', 'Sangat Kering'])) {
            $warnings[] = 'Musim hujan tapi kelembaban rendah';
        }
        if ($warnaDaun === 'Hijau Normal' && !empty($defisiensi)) {
            $warnings[] = 'Daun normal tapi ada dugaan defisiensi';
        }
        if ($curahHujan === 'Sangat Tinggi' && in_array($kelembaban, ['Kering', 'Sangat Kering'])) {
            $warnings[] = 'Curah hujan tinggi tapi kelembaban rendah';
        }
        if ($curahHujan === 'Sangat Rendah' && in_array($kelembaban, ['Lembab', 'Sangat Lembab'])) {
            $warnings[] = 'Curah hujan rendah tapi kelembaban tinggi';
        }
        if ($drainase === 'Buruk — Tergenang' && $curahHujan === 'Sangat Rendah') {
            $warnings[] = 'Drainase tergenang tapi curah hujan sangat rendah';
        }
        if ($drainase === 'Buruk — Tergenang' && $musim === 'Musim Kemarau') {
            $warnings[] = 'Drainase tergenang saat musim kemarau';
        }
        if ($musim === 'Musim Hujan' && $curahHujan === 'Sangat Rendah') {
            $warnings[] = 'Musim hujan tapi curah hujan sangat rendah';
        }
        if ($musim === 'Musim Kemarau' && $curahHujan === 'Sangat Tinggi') {
            $warnings[] = 'Musim kemarau tapi curah hujan sangat tinggi';
        }

        return $warnings;
    }

    // ═══════════════════════════════════════════════════════════════════
    // FITUR 2: Jadwal Pemupukan Per Tahap
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Generate jadwal pemupukan per tahap berdasarkan status dan dosis.
     */
    private function generateJadwalPemupukan(array $dataDosis, KondisiLahan $kondisi, string $statusDominan, BlokLahan $blok): array
    {
        // Jika status Tunda
        if ($statusDominan === 'Tunda') {
            $namaTahap = 'Tunda Pemupukan';
            $metodeAplikasi = 'Tidak dianjurkan pemupukan tanah saat ini';
            $catatan = 'Perbaiki faktor pembatas seperti drainase, hujan ekstrem, atau kekeringan terlebih dahulu.';
            $estimasiWaktu = 'Setelah kondisi lahan membaik';

            $isFlooded = ($kondisi->kondisi_drainase === 'Buruk — Tergenang') || ($kondisi->curah_hujan_kategori === 'Sangat Tinggi');
            $isDrought = ($kondisi->kelembaban_tanah === 'Sangat Kering') || ($kondisi->curah_hujan_kategori === 'Sangat Rendah');
            $isOldTree = ($blok->kategori_umur === 'Tua Renta') || ($blok->umur_tanaman !== null && $blok->umur_tanaman > 25);

            if ($isFlooded) {
                $namaTahap = 'Tunda Pemupukan & Perbaiki Drainase';
                $metodeAplikasi = 'Fokus pada normalisasi parit drainase, piringan bebas air, dan pembuatan pasar rintis kering.';
                $catatan = 'Hindari penaburan pupuk di atas air mengalir atau genangan karena pupuk akan terbuang percuma (leaching). Tunggu genangan surut dan tanah kembali lembab normal sebelum menjadwalkan pemupukan kembali.';
                $estimasiWaktu = '1-2 Bulan (Menunggu Drainase Diperbaiki)';
            } elseif ($isDrought) {
                $namaTahap = 'Tunda Pemupukan & Lakukan Mulsing / Konservasi Air';
                $metodeAplikasi = 'Aplikasikan janjang kosong (mulsing) di sekeliling piringan untuk menjaga kelembaban tanah.';
                $catatan = 'Jangan menaburkan pupuk kimia (Urea/KCl) pada tanah kering pecah-pecah karena pupuk akan menguap sia-sia. Lakukan pemupukan segera setelah ada curah hujan yang cukup.';
                $estimasiWaktu = 'Awal Musim Hujan (Curah Hujan Cukup)';
            } elseif ($isOldTree) {
                $namaTahap = 'Tunda Pemupukan & Evaluasi Kelayakan Replanting';
                $metodeAplikasi = 'Lakukan analisis biaya pemeliharaan versus produktivitas tandan buah segar (TBS) tanaman tua.';
                $catatan = 'Pemupukan pohon tua (>25 tahun) tidak lagi ekonomis karena tinggi pohon mempersulit panen dan efisiensi serapan pupuk sangat rendah. Pertimbangkan program peremajaan lahan.';
                $estimasiWaktu = 'Segera (Mulai Perencanaan Replanting)';
            }

            return [[
                'tahap'            => 1,
                'nama_tahap'       => $namaTahap,
                'estimasi_waktu'   => $estimasiWaktu,
                'persentase_urea'  => 0,
                'persentase_kcl'   => 0,
                'urea_kg'          => 0,
                'kcl_kg'           => 0,
                'urea_per_pokok'   => 0,
                'kcl_per_pokok'    => 0,
                'metode_aplikasi'  => $metodeAplikasi,
                'catatan'          => $catatan,
                'status_tahap'     => 'Ditunda',
            ]];
        }

        // Jika status Darurat (Defisiensi Berat)
        if ($statusDominan === 'Darurat') {
            return [[
                'tahap'            => 1,
                'nama_tahap'       => 'Tunda Pemupukan Kimia & Lakukan Koreksi Lahan',
                'estimasi_waktu'   => 'Segera (Bulan Ini)',
                'persentase_urea'  => 0,
                'persentase_kcl'   => 0,
                'urea_kg'          => 0,
                'kcl_kg'           => 0,
                'urea_per_pokok'   => 0,
                'kcl_per_pokok'    => 0,
                'metode_aplikasi'  => 'Lakukan pengapuran (Dolomit) atau penanganan hama/penyakit sesuai indikasi masalah.',
                'catatan'          => 'Status DARURAT (Defisiensi Berat): Sangat tidak dianjurkan melakukan pemupukan Urea dan KCl sebelum masalah utama teratasi. Aplikasikan Dolomit sebanyak 0.5–1.0 kg/pokok jika pH sangat masam, lalu uji kembali pH tanah setelah 2-3 bulan.',
                'status_tahap'     => 'Ditunda',
            ]];
        }

        $totalUrea = $dataDosis['total_urea'] ?? 0;
        $totalKcl = $dataDosis['total_kcl'] ?? 0;
        $dosisUrea = $dataDosis['dosis_urea'] ?? 0;
        $dosisKcl = $dataDosis['dosis_kcl'] ?? 0;

        // Jika tidak ada dosis, return empty
        if (!$totalUrea && !$totalKcl) {
            return [];
        }

        $jadwal = [];
        if ($kondisi->ada_gulma_dominan || $kondisi->ada_serangan_hama) {
            $catatanPrep = [];
            $metodePrep = [];
            if ($kondisi->ada_gulma_dominan) {
                $metodePrep[] = 'Pembersihan gulma (ring weeding) secara manual atau menggunakan herbisida secara selektif pada piringan pokok.';
                $catatanPrep[] = 'Piringan harus bersih (radius 1.5-2.0 meter dari batang) sebelum pemupukan agar pupuk terserap sepenuhnya oleh kelapa sawit.';
            }
            if ($kondisi->ada_serangan_hama) {
                $metodePrep[] = 'Pengendalian hama secara terpadu (PHT) dengan insektisida/pestisida yang sesuai.';
                $catatanPrep[] = 'Pastikan serangan hama terkendali terlebih dahulu sebelum pupuk kimia ditabur agar tanaman sawit dapat pulih maksimal.';
            }

            $jadwal[] = [
                'tahap'            => 1,
                'nama_tahap'       => 'Tahap Persiapan: Pengendalian Hama & Gulma',
                'estimasi_waktu'   => '7-14 hari sebelum pemupukan kimia',
                'persentase_urea'  => 0,
                'persentase_kcl'   => 0,
                'urea_kg'          => 0,
                'kcl_kg'           => 0,
                'urea_per_pokok'   => 0,
                'kcl_per_pokok'    => 0,
                'metode_aplikasi'  => implode(' ', $metodePrep),
                'catatan'          => implode(' ', $catatanPrep),
                'status_tahap'     => 'Wajib Dilakukan',
            ];
        }

        // Tentukan pembagian persentase berdasarkan status
        $pembagian = match($statusDominan) {
            'Darurat' => [70, 30],
            'Segera'  => [60, 40],
            default   => [50, 50],
        };

        // Tentukan catatan kontekstual berdasarkan kondisi
        $catatanTahap1 = 'Utamakan saat kelembaban normal dan tidak tergenang';
        $catatanTahap2 = 'Lakukan observasi ulang sebelum tahap 2';

        $curahHujan = $kondisi->curah_hujan_kategori;
        $drainase = $kondisi->kondisi_drainase;
        $kelembaban = $kondisi->kelembaban_tanah;

        if ($curahHujan === 'Sangat Tinggi' || $drainase === 'Buruk — Tergenang' || $kelembaban === 'Sangat Lembab') {
            $catatanTahap1 = 'Hindari pemupukan saat lahan tergenang. Tunggu kondisi tanah normal.';
            $catatanTahap2 = 'Pastikan drainase membaik sebelum aplikasi tahap 2.';
        }

        if ($curahHujan === 'Sangat Rendah' || $kelembaban === 'Sangat Kering') {
            $catatanTahap1 = 'Tunda sampai ada hujan cukup. Hindari aplikasi saat tanah terlalu kering.';
            $catatanTahap2 = 'Aplikasikan segera setelah hujan turun dan tanah lembab.';
        }

        // Tentukan Bulan Pelaksanaan Realistis Berdasarkan Musim & Tanggal Observasi
        $baseDate = $kondisi->tanggal_observasi ?? now();
        $bulanAnalisis = $baseDate->month;
        $tahunAnalisis = $baseDate->year;

        if (in_array($bulanAnalisis, [12, 1, 2])) {
            $bulan1 = 3; // Maret
            $tahun1 = ($bulanAnalisis == 12) ? $tahunAnalisis + 1 : $tahunAnalisis;
        } elseif (in_array($bulanAnalisis, [3, 4, 5])) {
            $bulan1 = $bulanAnalisis; // Bulan ini
            $tahun1 = $tahunAnalisis;
        } elseif (in_array($bulanAnalisis, [6, 7, 8])) {
            $bulan1 = 9; // September
            $tahun1 = $tahunAnalisis;
        } else { // 9, 10, 11
            $bulan1 = $bulanAnalisis; // Bulan ini
            $tahun1 = $tahunAnalisis;
        }

        $time1 = \Carbon\Carbon::create($tahun1, $bulan1, 1, 0, 0, 0);
        $time2 = $time1->copy()->addMonths(6);

        $waktu1A = $this->getNamaBulanIndo($time1->month) . ' ' . $time1->year;
        $waktu1B = $this->getNamaBulanIndo($time1->month) . ' ' . $time1->year . ' (Jeda 2-3 minggu setelah Urea)';
        $waktu2A = $this->getNamaBulanIndo($time2->month) . ' ' . $time2->year;
        $waktu2B = $this->getNamaBulanIndo($time2->month) . ' ' . $time2->year . ' (Jeda 2-3 minggu setelah Urea)';

        // Metode aplikasi berdasarkan umur tanaman
        $umurTanaman = $blok->umur_tanaman;
        $kategoriUmur = $blok->kategori_umur;

        if ($kategoriUmur === 'Belum Menghasilkan' || ($umurTanaman !== null && $umurTanaman < 3)) {
            $metodeUrea = 'Ditabur melingkar merata (lebar band 10-20 cm) sekitar 30-50 cm dari pangkal batang sawit TBM.';
            $metodeKcl  = 'Ditabur melingkar merata sekitar 30-50 cm dari pangkal batang di atas piringan bersih.';
        } else {
            $metodeUrea = 'Ditabur melingkar merata pada piringan bersih berjarak 1.5 - 2.0 meter dari pangkal batang (di bawah proyeksi tajuk terluar pelepah).';
            $metodeKcl  = 'Ditabur melingkar merata berjarak 1.5 - 2.0 meter dari pangkal batang (di bawah area akar rambut aktif).';
        }

        $startTahap = count($jadwal) + 1;

        $jadwal[] = [
            'tahap'            => $startTahap,
            'nama_tahap'       => 'Tahap 1A: Aplikasi Urea',
            'estimasi_waktu'   => $waktu1A,
            'persentase_urea'  => $pembagian[0],
            'persentase_kcl'   => 0,
            'urea_kg'          => round(($totalUrea * $pembagian[0]) / 100, 2),
            'kcl_kg'           => 0,
            'urea_per_pokok'   => round(($dosisUrea * $pembagian[0]) / 100, 2),
            'kcl_per_pokok'    => 0,
            'metode_aplikasi'  => $metodeUrea,
            'catatan'          => $catatanTahap1,
            'status_tahap'     => 'Direncanakan',
        ];
        $jadwal[] = [
            'tahap'            => $startTahap + 1,
            'nama_tahap'       => 'Tahap 1B: Aplikasi KCl',
            'estimasi_waktu'   => $waktu1B,
            'persentase_urea'  => 0,
            'persentase_kcl'   => $pembagian[0],
            'urea_kg'          => 0,
            'kcl_kg'           => round(($totalKcl * $pembagian[0]) / 100, 2),
            'urea_per_pokok'   => 0,
            'kcl_per_pokok'    => round(($dosisKcl * $pembagian[0]) / 100, 2),
            'metode_aplikasi'  => $metodeKcl,
            'catatan'          => 'Pastikan piringan bersih dari gulma. Jangan mencampur KCl langsung dengan Urea.',
            'status_tahap'     => 'Direncanakan',
        ];
        $jadwal[] = [
            'tahap'            => $startTahap + 2,
            'nama_tahap'       => 'Tahap 2A: Aplikasi Urea',
            'estimasi_waktu'   => $waktu2A,
            'persentase_urea'  => $pembagian[1],
            'persentase_kcl'   => 0,
            'urea_kg'          => round(($totalUrea * $pembagian[1]) / 100, 2),
            'kcl_kg'           => 0,
            'urea_per_pokok'   => round(($dosisUrea * $pembagian[1]) / 100, 2),
            'kcl_per_pokok'    => 0,
            'metode_aplikasi'  => $metodeUrea,
            'catatan'          => $catatanTahap2,
            'status_tahap'     => 'Direncanakan',
        ];
        $jadwal[] = [
            'tahap'            => $startTahap + 3,
            'nama_tahap'       => 'Tahap 2B: Aplikasi KCl',
            'estimasi_waktu'   => $waktu2B,
            'persentase_urea'  => 0,
            'persentase_kcl'   => $pembagian[1],
            'urea_kg'          => 0,
            'kcl_kg'           => round(($totalKcl * $pembagian[1]) / 100, 2),
            'urea_per_pokok'   => 0,
            'kcl_per_pokok'    => round(($dosisKcl * $pembagian[1]) / 100, 2),
            'metode_aplikasi'  => $metodeKcl,
            'catatan'          => 'Lakukan evaluasi kondisi visual pelepah dan daun kelapa sawit sebelum penaburan.',
            'status_tahap'     => 'Direncanakan',
        ];

        return $jadwal;
    }

    /**
     * Helper nama bulan bahasa indonesia.
     */
    private function getNamaBulanIndo(int $month): string
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $namaBulan[$month] ?? '';
    }

    // ═══════════════════════════════════════════════════════════════════
    // CORE: Evaluasi Rule
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Cek apakah prasyarat intermediate terpenuhi (Rule Chaining - A2).
     */
    private function cekPrasyaratIntermediate(RuleBaseLanjutan $rule, array $intermediateFlags): bool
    {
        if (empty($rule->prasyarat_intermediate) || !is_array($rule->prasyarat_intermediate)) {
            return true;
        }

        foreach ($rule->prasyarat_intermediate as $key => $value) {
            if (!isset($intermediateFlags[$key]) || $intermediateFlags[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluasi apakah sebuah rule cocok dengan kondisi saat ini.
     * Semua kondisi yang diisi di rule harus terpenuhi (AND logic).
     * Kondisi NULL di rule = tidak relevan / diabaikan.
     */
    private function evaluasiRule(RuleBaseLanjutan $rule, KondisiLahan $kondisi, ?string $kategoriUmur): bool
    {
        $jumlahKondisiDiRule = 0;
        $jumlahKondisiCocok = 0;

        // Cek warna daun
        if ($rule->kondisi_warna_daun !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->warna_daun === null) {
                return false;
            }
            if ($rule->kondisi_warna_daun !== $kondisi->warna_daun) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek range pH
        if ($rule->kondisi_ph_min !== null || $rule->kondisi_ph_max !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->ph_tanah === null) {
                return false;
            }
            if ($rule->kondisi_ph_min !== null && (float) $kondisi->ph_tanah < (float) $rule->kondisi_ph_min) {
                return false;
            }
            if ($rule->kondisi_ph_max !== null && (float) $kondisi->ph_tanah > (float) $rule->kondisi_ph_max) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek kelembaban
        if ($rule->kondisi_kelembaban !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->kelembaban_tanah === null) {
                return false;
            }
            if ($rule->kondisi_kelembaban !== $kondisi->kelembaban_tanah) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek curah hujan (Fitur 4)
        if ($rule->kondisi_curah_hujan_kategori !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->curah_hujan_kategori === null) {
                return false;
            }
            if ($rule->kondisi_curah_hujan_kategori !== $kondisi->curah_hujan_kategori) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek musim
        if ($rule->kondisi_musim !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->musim_saat_ini === null) {
                return false;
            }
            if ($rule->kondisi_musim !== $kondisi->musim_saat_ini) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek drainase
        if ($rule->kondisi_drainase !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->kondisi_drainase === null) {
                return false;
            }
            if ($rule->kondisi_drainase !== $kondisi->kondisi_drainase) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek defisiensi (array contains check)
        if ($rule->kondisi_defisiensi !== null) {
            $defisiensiInput = $kondisi->gejala_defisiensi ?? [];
            if (!empty($defisiensiInput)) {
                $jumlahKondisiDiRule++;
                if (!in_array($rule->kondisi_defisiensi, $defisiensiInput)) {
                    return false;
                }
                $jumlahKondisiCocok++;
            }
        }

        // Cek kondisi pelepah
        if ($rule->kondisi_pelepah !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->kondisi_pelepah === null) {
                return false;
            }
            if ($rule->kondisi_pelepah !== $kondisi->kondisi_pelepah) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek serangan hama
        if ($rule->ada_serangan_hama === true) {
            $jumlahKondisiDiRule++;
            if (!$kondisi->ada_serangan_hama) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek gulma dominan (Fitur 4)
        if ($rule->ada_gulma_dominan !== null) {
            $jumlahKondisiDiRule++;
            if ((bool) $kondisi->ada_gulma_dominan !== (bool) $rule->ada_gulma_dominan) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek kondisi tandan
        if ($rule->kondisi_tandan !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->kondisi_tandan === null) {
                return false;
            }
            if ($rule->kondisi_tandan !== $kondisi->kondisi_tandan) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Cek kategori umur
        if ($rule->kondisi_kategori_umur !== null) {
            $jumlahKondisiDiRule++;
            if ($rule->kondisi_kategori_umur !== $kategoriUmur) {
                return false;
            }
            $jumlahKondisiCocok++;
        }

        // Safety: minimal 1 kondisi yang benar-benar cocok
        if ($jumlahKondisiDiRule === 0 || $jumlahKondisiCocok === 0) {
            return false;
        }

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════
    // FITUR 1: Histori — Simpan Hasil (create, bukan updateOrCreate)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Simpan rekomendasi baru dengan histori (Fitur 1).
     * Jika hasil analisis sama persis dengan rekomendasi terakhir (kondisi_lahan_id dan status sama),
     * tidak membuat record baru — hanya update tanggal_analisis.
     */
    private function simpanDenganHistori(int $blokLahanId, array $data): RekomendasiRbs
    {
        return DB::transaction(function () use ($blokLahanId, $data) {
            // Cek apakah hasil sama dengan rekomendasi terakhir
            $existing = RekomendasiRbs::where('blok_lahan_id', $blokLahanId)
                ->where('is_latest', true)
                ->first();

            if ($existing && $this->hasilSamaDenganSebelumnya($existing, $data)) {
                // Hanya update tanggal analisis tanpa membuat record baru
                $existing->update(['tanggal_analisis' => $data['tanggal_analisis']]);
                $existing->touch(); // Force update the updated_at timestamp to sync with the latest analysis run
                return $existing;
            }

            // Set semua rekomendasi lama menjadi is_latest = false
            RekomendasiRbs::where('blok_lahan_id', $blokLahanId)
                ->where('is_latest', true)
                ->update(['is_latest' => false]);

            // Hitung nomor analisis
            $lastNomor = RekomendasiRbs::where('blok_lahan_id', $blokLahanId)->max('nomor_analisis');
            $data['nomor_analisis'] = ($lastNomor ?? 0) + 1;
            $data['is_latest'] = true;
            $data['blok_lahan_id'] = $blokLahanId;

            return RekomendasiRbs::create($data);
        });
    }

    /**
     * Cek apakah hasil analisis baru sama dengan rekomendasi sebelumnya.
     * Perbandingan berdasarkan: kondisi_lahan_id + status + jumlah_rule + dosis.
     */
    private function hasilSamaDenganSebelumnya(RekomendasiRbs $existing, array $newData): bool
    {
        // Jika struktur atau jumlah tahapan jadwal berubah, anggap hasil berbeda agar diperbarui
        $existingJadwal = $existing->jadwal_pemupukan ?? [];
        $newJadwal = $newData['jadwal_pemupukan'] ?? [];
        if (count($existingJadwal) !== count($newJadwal)) {
            return false;
        }

        return $existing->kondisi_lahan_id == $newData['kondisi_lahan_id']
            && $existing->status_kebutuhan_dominan === $newData['status_kebutuhan_dominan']
            && $existing->jumlah_rule_terpicu == $newData['jumlah_rule_terpicu']
            && (float) $existing->dosis_urea === (float) ($newData['dosis_urea'] ?? 0)
            && (float) $existing->dosis_kcl === (float) ($newData['dosis_kcl'] ?? 0);
    }

    /**
     * Susun hasil analisis dari rule-rule yang terpicu.
     */
    private function susunHasil(BlokLahan $blok, KondisiLahan $kondisi, array $rules, array $kecukupanData): array
    {
        // Tentukan status dominan (Tunda is prioritized to override Segera/Normal/Darurat)
        $hierarki = ['Tunda' => 4, 'Darurat' => 3, 'Segera' => 2, 'Normal' => 1];
        $statusDominan = collect($rules)
            ->sortByDesc(fn($r) => $hierarki[$r->status_kebutuhan] ?? 0)
            ->first()
            ->status_kebutuhan;

        // Kumpulkan masalah unik
        $masalah = collect($rules)->pluck('indikasi_masalah')->unique()->values()->toArray();

        // Kumpulkan rekomendasi pupuk (deduplicate by jenis_pupuk_utama)
        $pupuk = collect($rules)
            ->unique('jenis_pupuk_utama')
            ->map(fn($r) => [
                'jenis_utama'     => $r->jenis_pupuk_utama,
                'jenis_pendukung' => $r->jenis_pupuk_pendukung,
                'dosis'           => $r->dosis_anjuran,
                'metode'          => $r->metode_aplikasi,
                'waktu'           => $r->waktu_aplikasi,
            ])
            ->values()
            ->toArray();

        // Saran tindakan (top 3)
        $saranUtama = collect($rules)
            ->sortBy('prioritas')
            ->take(3)
            ->pluck('saran_tindakan')
            ->implode(' | ');

        if ($kondisi->ada_gulma_dominan || $kondisi->ada_serangan_hama) {
            $saranTambahan = [];
            if ($kondisi->ada_gulma_dominan) {
                $saranTambahan[] = 'Kendalikan gulma dominan di piringan sawit';
            }
            if ($kondisi->ada_serangan_hama) {
                $saranTambahan[] = 'Atasi serangan hama aktif';
            }
            $saranUtama = implode(' & ', $saranTambahan) . ' sebelum pemupukan kimia dilakukan. | ' . $saranUtama;
        }

        // Hitung dosis (Jika Darurat / Tunda, dosis Urea & KCl adalah 0 karena pemupukan kimia ditunda)
        if ($statusDominan === 'Darurat' || $statusDominan === 'Tunda') {
            $dosis = [
                'dosis_urea' => 0.0,
                'dosis_kcl'  => 0.0,
                'total_urea' => 0.0,
                'total_kcl'  => 0.0,
                'multiplier_waktu_info' => $statusDominan === 'Darurat' ? '[Pemupukan kimia ditunda: Atasi kondisi darurat terlebih dahulu]' : '[Pemupukan ditunda: Kondisi pembatas belum sesuai]',
            ];
        } else {
            $dosis = $this->hitungDosisStandar($blok, $kondisi);
        }

        // Catatan dosis
        $catatanDosis = $this->tentukanCatatanDosis($statusDominan, $masalah, $dosis, $kondisi);

        // Fitur 2: Jadwal pemupukan
        $jadwal = $this->generateJadwalPemupukan($dosis, $kondisi, $statusDominan, $blok);

        // Fitur 3: Validitas
        $validitas = $this->tentukanValiditasRekomendasi($kondisi, $kecukupanData);
        // Jika data tidak cukup, validitas otomatis Estimasi Visual
        if (!$kecukupanData['data_cukup']) {
            $validitas['validitas'] = 'Estimasi Visual';
            $validitas['catatan'] = 'Rekomendasi ini bersifat estimasi visual karena data observasi belum lengkap.';
        }

        // Fitur 6: Confidence
        $confidence = $this->hitungConfidence($kondisi, $rules);

        // Simpan dengan histori (Fitur 1)
        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id'         => $kondisi->id,
            'admin_id'                 => Auth::guard('admin')->id(),
            'tanggal_analisis'         => now()->toDateString(),
            'rules_terpicu'            => collect($rules)->map(fn($r) => [
                'rule_id'   => $r->id,
                'indikasi'  => $r->indikasi_masalah,
                'pupuk'     => $r->jenis_pupuk_utama,
                'status'    => $r->status_kebutuhan,
                'prioritas' => $r->prioritas,
            ])->toArray(),
            'masalah_teridentifikasi'  => $masalah,
            'rekomendasi_pupuk'        => $pupuk,
            'saran_tindakan_utama'     => $saranUtama,
            'status_kebutuhan_dominan' => $statusDominan,
            'jumlah_rule_terpicu'      => count($rules),
            'dosis_urea'               => $dosis['dosis_urea'],
            'dosis_kcl'                => $dosis['dosis_kcl'],
            'total_urea'               => $dosis['total_urea'],
            'total_kcl'                => $dosis['total_kcl'],
            'catatan_dosis'            => $catatanDosis,
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => $validitas['validitas'],
            'catatan_validitas'        => $validitas['catatan'],
            'confidence_score'         => $confidence['score'],
            'confidence_label'         => $confidence['label'],
            'catatan_confidence'       => $confidence['catatan'],
            'data_cukup'               => $kecukupanData['data_cukup'],
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Cek apakah data kondisi cukup untuk dianalisis.
     */
    private function kondisiCukup(KondisiLahan $kondisi): bool
    {
        return $kondisi->warna_daun !== null
            || $kondisi->ph_tanah !== null
            || $kondisi->kelembaban_tanah !== null
            || $kondisi->musim_saat_ini !== null
            || $kondisi->kondisi_drainase !== null
            || $kondisi->kondisi_pelepah !== null
            || $kondisi->kondisi_tandan !== null
            || !empty($kondisi->gejala_defisiensi)
            || $kondisi->ada_serangan_hama === true
            || $kondisi->curah_hujan_kategori !== null
            || $kondisi->ada_gulma_dominan === true;
    }

    /**
     * Return hasil ketika data kondisi tidak cukup untuk analisis.
     */
    private function hasilDataTidakCukup(BlokLahan $blok, KondisiLahan $kondisi, array $kecukupanData): array
    {
        $dosis = $this->hitungDosisStandar($blok, $kondisi);
        $jadwal = $this->generateJadwalPemupukan($dosis, $kondisi, 'Normal', $blok);
        $confidence = $this->hitungConfidence($kondisi, []);

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id'         => $kondisi->id,
            'admin_id'                 => Auth::guard('admin')->id(),
            'tanggal_analisis'         => now()->toDateString(),
            'rules_terpicu'            => [],
            'masalah_teridentifikasi'  => ['Data kondisi lahan belum lengkap untuk analisis'],
            'rekomendasi_pupuk'        => [['jenis_utama' => 'Pupuk Standar Rutin', 'dosis' => 'Sesuai jadwal pemupukan reguler — lengkapi data kondisi untuk rekomendasi spesifik']],
            'saran_tindakan_utama'     => 'Data observasi kondisi lahan belum cukup untuk memberikan rekomendasi spesifik. Silakan lengkapi data kondisi (warna daun, pH tanah, kelembaban, kondisi drainase, dll) lalu jalankan analisis ulang.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu'      => 0,
            'dosis_urea'               => $dosis['dosis_urea'],
            'dosis_kcl'                => $dosis['dosis_kcl'],
            'total_urea'               => $dosis['total_urea'],
            'total_kcl'                => $dosis['total_kcl'],
            'catatan_dosis'            => 'Dosis standar berdasarkan umur tanaman, jenis tanah, dan topografi. Lengkapi data kondisi lahan untuk mendapat rekomendasi yang lebih akurat.',
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => 'Estimasi Visual',
            'catatan_validitas'        => 'Data observasi tidak lengkap — rekomendasi bersifat estimasi.',
            'confidence_score'         => $confidence['score'],
            'confidence_label'         => $confidence['label'],
            'catatan_confidence'       => $confidence['catatan'],
            'data_cukup'               => false,
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Return status normal ketika tidak ada rule yang terpicu.
     */
    private function hasilNormal(BlokLahan $blok, KondisiLahan $kondisi, array $kecukupanData): array
    {
        $dosis = $this->hitungDosisStandar($blok, $kondisi);
        $jadwal = $this->generateJadwalPemupukan($dosis, $kondisi, 'Normal', $blok);
        $validitas = $this->tentukanValiditasRekomendasi($kondisi, $kecukupanData);
        $confidence = $this->hitungConfidence($kondisi, []);

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id'         => $kondisi->id,
            'admin_id'                 => Auth::guard('admin')->id(),
            'tanggal_analisis'         => now()->toDateString(),
            'rules_terpicu'            => [],
            'masalah_teridentifikasi'  => ['Tidak ada masalah teridentifikasi'],
            'rekomendasi_pupuk'        => [['jenis_utama' => 'Pupuk Standar Rutin', 'dosis' => 'Sesuai jadwal pemupukan reguler']],
            'saran_tindakan_utama'     => 'Lanjutkan program pemupukan standar. Kondisi lahan dalam batas normal.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu'      => 0,
            'dosis_urea'               => $dosis['dosis_urea'],
            'dosis_kcl'                => $dosis['dosis_kcl'],
            'total_urea'               => $dosis['total_urea'],
            'total_kcl'                => $dosis['total_kcl'],
            'catatan_dosis'            => 'Kondisi lahan normal. Dosis dapat diaplikasikan sesuai jadwal pemupukan standar.',
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => $validitas['validitas'],
            'catatan_validitas'        => $validitas['catatan'],
            'confidence_score'         => $confidence['score'],
            'confidence_label'         => $confidence['label'],
            'catatan_confidence'       => $confidence['catatan'],
            'data_cukup'               => $kecukupanData['data_cukup'],
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan catatan kontekstual untuk dosis berdasarkan status dan masalah.
     */
    private function tentukanCatatanDosis(string $statusDominan, array $masalah, array $dosis, KondisiLahan $kondisi): string
    {
        $masalahStr = implode(' ', $masalah);
        $multiplierInfo = $dosis['multiplier_waktu_info'] ?? '';

        if ($statusDominan === 'Tunda') {
            if (str_contains($masalahStr, 'Tergenang') || str_contains($masalahStr, 'Waterlogging')) {
                $catatan = 'TUNDA APLIKASI PUPUK TANAH. Lahan tergenang menyebabkan leaching. Perbaiki drainase terlebih dahulu, baru aplikasikan dosis ini setelah kondisi normal.';
            } elseif (str_contains($masalahStr, 'Kekeringan') || str_contains($masalahStr, 'Kemarau') || str_contains($masalahStr, 'kering')) {
                $catatan = 'TUNDA PUPUK KIMIA. Kondisi terlalu kering — pupuk tidak akan terlarut dan berisiko membakar akar. Tunggu hujan turun, baru aplikasikan dosis ini.';
            } elseif (str_contains($masalahStr, 'Tua Renta')) {
                $catatan = 'Efisiensi penyerapan hara sangat rendah pada tanaman tua. Pertimbangkan pengurangan dosis 40-50% atau evaluasi replanting.';
            } elseif (str_contains($masalahStr, 'Curah hujan sangat tinggi')) {
                $catatan = 'TUNDA PEMUPUKAN. Curah hujan terlalu tinggi menyebabkan pencucian hara. Tunggu curah hujan kembali normal.';
            } else {
                $catatan = 'Pemupukan ditunda sampai kondisi lahan diperbaiki. Dosis ini dapat diaplikasikan setelah masalah teratasi.';
            }
        } elseif ($statusDominan === 'Darurat') {
            if (str_contains($masalahStr, 'pH') || str_contains($masalahStr, 'Masam')) {
                $catatan = 'PERHATIAN: Jangan aplikasikan Urea/KCl sebelum pH tanah dinaikkan ke 5.0+. Lakukan pengapuran (Dolomit) terlebih dahulu. Dosis ini berlaku setelah pH normal.';
            } elseif (str_contains($masalahStr, 'Busuk') || str_contains($masalahStr, 'Ganoderma')) {
                $catatan = 'PRIORITASKAN penanganan penyakit terlebih dahulu. Dosis pupuk standar ini berlaku setelah kondisi tanaman membaik.';
            } else {
                $catatan = 'Status DARURAT — atasi masalah utama terlebih dahulu. Dosis ini adalah kebutuhan standar yang berlaku setelah kondisi diperbaiki.';
            }
        } elseif ($statusDominan === 'Segera') {
            $catatan = 'Segera aplikasikan dosis pupuk standar ini bersamaan dengan penanganan masalah yang teridentifikasi.';
            if ($kondisi->ada_gulma_dominan || $kondisi->ada_serangan_hama) {
                $notes = [];
                if ($kondisi->ada_gulma_dominan) {
                    $notes[] = 'kendalikan gulma dominan';
                }
                if ($kondisi->ada_serangan_hama) {
                    $notes[] = 'atasi serangan hama';
                }
                $catatan = 'PENTING: Harap ' . implode(' dan ', $notes) . ' sebelum pupuk kimia ditabur agar penyerapan hara oleh pokok sawit optimal.';
            }
        } else {
            $catatan = 'Kondisi lahan normal. Dosis dapat diaplikasikan sesuai jadwal pemupukan standar.';
        }

        if ($multiplierInfo) {
            $catatan .= ' ' . $multiplierInfo;
        }

        return $catatan;
    }

    /**
     * Hitung dosis standar Urea & KCl berdasarkan kriteria lahan.
     */
    private function hitungDosisStandar(BlokLahan $blok, ?KondisiLahan $kondisi = null): array
    {
        if (!$blok->tahun_tanam || !$blok->jenis_tanah || !$blok->topografi) {
            return [
                'dosis_urea' => null, 'dosis_kcl' => null,
                'total_urea' => null, 'total_kcl' => null,
                'multiplier_waktu_info' => '',
            ];
        }

        $umur = now()->year - $blok->tahun_tanam;
        $kategoriUmur = $this->tentukanKategoriUmur($umur);

        // ─────────────────────────────────────────────────────────────────
        // Base Dosis per Kategori Umur (kg/pokok/tahun)
        // Referensi: Pahan, I. (2015). Panduan Lengkap Kelapa Sawit:
        //            Manajemen Agribisnis dari Hulu hingga Hilir.
        //            Jakarta: Penebar Swadaya.
        //
        // Catatan: Angka merupakan nilai tengah dari rentang rekomendasi
        //          Pahan (2015) untuk tanah mineral umum.
        //
        // TBM (0–3 th)   : Urea 0.50–1.00, KCl 0.50–1.00 → tengah 0.75
        // TM Muda (4–8)  : Urea 1.50–2.00, KCl 1.50–2.00 → tengah 1.75
        // TM Prime (9–14): Urea 2.00–2.50, KCl 2.00–2.50 → tengah 2.25
        // TM Tua (15–25) : Urea 2.50–3.00, KCl 2.00–2.50 → tengah 2.75/2.25
        // Tua Renta (>25): Urea 1.50–2.00, KCl 1.50–2.00 → tengah 1.75
        // ─────────────────────────────────────────────────────────────────
        $baseDosis = match($kategoriUmur) {
            'Belum Menghasilkan' => ['urea' => 0.75, 'kcl' => 0.75],
            'Remaja'             => ['urea' => 1.75, 'kcl' => 1.75],
            'Menghasilkan Muda'  => ['urea' => 2.25, 'kcl' => 2.25],
            'Menghasilkan Tua'   => ['urea' => 2.75, 'kcl' => 2.25],
            'Tua Renta'          => ['urea' => 1.75, 'kcl' => 1.75],
            default              => ['urea' => 1.75, 'kcl' => 1.75],
        };

        // ─────────────────────────────────────────────────────────────────
        // Multiplier Koreksi Jenis Tanah
        // Referensi: Diadaptasi dari prinsip nutrient balance
        //            Fairhurst, T. & Hardter, R. (2003). Oil Palm:
        //            Management for Large and Sustainable Yields.
        //            International Potash Institute (IPI).
        //
        // Prinsip:
        // - Tanah berpasir: leaching tinggi → dosis N & K dinaikkan
        // - Tanah gambut: denitrifikasi tinggi (N turun), K sangat rendah
        //   → Urea dikurangi, KCl dinaikkan signifikan
        // - Tanah liat: retensi hara baik → dosis sedikit dikurangi
        // - Tanah PMK/Laterit: miskin hara → koreksi naik
        // ─────────────────────────────────────────────────────────────────
        $multiplierTanah = match($blok->jenis_tanah) {
            'Tanah Lempung'                     => ['urea' => 1.0,  'kcl' => 1.0],
            'Tanah Lempung Berpasir'            => ['urea' => 1.1,  'kcl' => 1.15],
            'Tanah Berpasir'                    => ['urea' => 1.25, 'kcl' => 1.35],
            'Tanah Liat'                        => ['urea' => 0.9,  'kcl' => 0.9],
            'Tanah Gambut'                      => ['urea' => 0.7,  'kcl' => 1.5],
            'Tanah Aluvial'                     => ['urea' => 1.0,  'kcl' => 1.0],
            'Tanah Podsolik Merah Kuning (PMK)' => ['urea' => 1.15, 'kcl' => 1.2],
            'Tanah Laterit'                     => ['urea' => 1.15, 'kcl' => 1.2],
            'Tanah Berbatu'                     => ['urea' => 1.2,  'kcl' => 1.2],
            default                             => ['urea' => 1.0,  'kcl' => 1.0],
        };

        // ─────────────────────────────────────────────────────────────────
        // Multiplier Koreksi Topografi
        // Referensi: Fairhurst & Hardter (2003) — prinsip run-off loss
        //
        // Prinsip:
        // - Datar: tidak ada koreksi (baseline)
        // - Bergelombang: run-off moderat → koreksi +10%
        // - Curam: run-off tinggi → koreksi +20%
        // ─────────────────────────────────────────────────────────────────
        $multiplierTopo = match($blok->topografi) {
            'Datar 0-15°'         => ['urea' => 1.0, 'kcl' => 1.0],
            'Bergelombang 15-30°' => ['urea' => 1.1, 'kcl' => 1.1],
            'Curam >30°'          => ['urea' => 1.2, 'kcl' => 1.2],
            default               => ['urea' => 1.0, 'kcl' => 1.0],
        };

        $multiplierWaktu = 1.0;
        $multiplierWaktuInfo = '';

        if ($kondisi && $kondisi->tanggal_pemupukan_terakhir) {
            $jarakHari = $kondisi->tanggal_pemupukan_terakhir->diffInDays(now());

            if ($jarakHari < 60) {
                $multiplierWaktu = 0.75;
                $multiplierWaktuInfo = "[Koreksi waktu: ×0.75 — terakhir dipupuk {$jarakHari} hari lalu, masih baru]";
            } elseif ($jarakHari <= 120) {
                $multiplierWaktu = 1.0;
                $multiplierWaktuInfo = "[Koreksi waktu: ×1.0 — jadwal pemupukan normal ({$jarakHari} hari)]";
            } else {
                $multiplierWaktu = 1.25;
                $multiplierWaktuInfo = "[Koreksi waktu: ×1.25 — terlambat pupuk {$jarakHari} hari, dosis ditingkatkan]";
            }
        }

        $dosisUrea = round($baseDosis['urea'] * $multiplierTanah['urea'] * $multiplierTopo['urea'] * $multiplierWaktu * 4) / 4;
        $dosisKcl  = round($baseDosis['kcl']  * $multiplierTanah['kcl']  * $multiplierTopo['kcl'] * $multiplierWaktu * 4) / 4;

        $totalUrea = $dosisUrea * $blok->sph * $blok->luas_ha;
        $totalKcl  = $dosisKcl  * $blok->sph * $blok->luas_ha;

        return [
            'dosis_urea'            => $dosisUrea,
            'dosis_kcl'             => $dosisKcl,
            'total_urea'            => $totalUrea,
            'total_kcl'             => $totalKcl,
            'multiplier_waktu_info' => $multiplierWaktuInfo,
        ];
    }

    /**
     * Tentukan kategori umur tanaman kelapa sawit.
     */
    private function tentukanKategoriUmur(int $umur): string
    {
        if ($umur < 3) return 'Belum Menghasilkan';
        if ($umur <= 8) return 'Remaja';
        if ($umur <= 14) return 'Menghasilkan Muda';
        if ($umur <= 25) return 'Menghasilkan Tua';
        return 'Tua Renta';
    }
}
