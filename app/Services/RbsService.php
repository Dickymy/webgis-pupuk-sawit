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
     * Mapping warna daun → dugaan unsur hara (untuk skor keandalan).
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

    private PahanDoseReferenceService $doseService;
    private FertilizationWindowService $windowService;
    private FertilizationCalculationService $calcService;
    private RecommendationReliabilityService $reliabilityService;
    private PlantPhaseResolver $phaseResolver;

    public function __construct(
        PahanDoseReferenceService $doseService,
        FertilizationWindowService $windowService,
        FertilizationCalculationService $calcService,
        RecommendationReliabilityService $reliabilityService,
        PlantPhaseResolver $phaseResolver
    ) {
        $this->doseService = $doseService;
        $this->windowService = $windowService;
        $this->calcService = $calcService;
        $this->reliabilityService = $reliabilityService;
        $this->phaseResolver = $phaseResolver;
    }

    /**
     * Jalankan analisis RBS untuk satu blok lahan berdasarkan kondisi terbaru.
     *
     * Versi: pahan-v2
     * Perubahan utama dari legacy-v1:
     * - Dosis mengikuti rentang Pahan 2013 (Tabel 9.13 & 9.14)
     * - Multiplier tanah/topografi/waktu dinonaktifkan
     * - Curah hujan numerik (mm/bulan) menentukan kelayakan waktu
     * - Interval < 60 hari → tunda (tanpa mengubah dosis tahunan)
     * - Keterlambatan > 120 hari → ditandai tanpa menaikkan dosis
     * - Confidence score diganti menjadi Skor Kelengkapan & Keandalan Data
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

        // 2. Cek kecukupan data
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
        // Eager-load kondisi terbaru + preload rules sekali untuk semua blok
        $blokLahans = BlokLahan::whereHas('kondisiLahans')
            ->with(['kondisiTerbaru', 'anggota'])
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

        // Hitung dosis referensi Pahan (selalu dihitung untuk kebutuhan tahunan)
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi);

        // Evaluasi kelayakan waktu
        $window = $dosisRef['window'];

        // Status kondisi tanaman
        $statusKondisiTanaman = $this->tentukanStatusKondisiTanaman($rules);

        // Status kelayakan aplikasi
        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::PERLU_VERIFIKASI_DATA;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : 'Data kelayakan waktu belum tersedia.';

        // Dosis untuk kolom lama (kompatibilitas):
        // Jika Darurat / Tunda OLEH RULE → tetap tampilkan 0 di kolom lama
        // Kebutuhan tahunan tetap tersimpan di kolom baru
        if ($statusDominan === 'Darurat' || $statusDominan === 'Tunda') {
            $dosisAplikasi = [
                'dosis_urea' => 0.0,
                'dosis_kcl'  => 0.0,
                'total_urea' => 0.0,
                'total_kcl'  => 0.0,
            ];
        } elseif ($window && !$window['layak']) {
            // Waktu tidak layak → dosis aplikasi saat ini = 0, tapi kebutuhan tahunan tetap ada
            $dosisAplikasi = [
                'dosis_urea' => 0.0,
                'dosis_kcl'  => 0.0,
                'total_urea' => 0.0,
                'total_kcl'  => 0.0,
            ];
        } else {
            $dosisAplikasi = [
                'dosis_urea' => $dosisRef['dosis_urea'],
                'dosis_kcl'  => $dosisRef['dosis_kcl'],
                'total_urea' => $dosisRef['total_urea'],
                'total_kcl'  => $dosisRef['total_kcl'],
            ];
        }

        // Catatan dosis
        $catatanDosis = $this->tentukanCatatanDosis($statusDominan, $masalah, $dosisAplikasi, $kondisi);

        // Jadwal pemupukan
        $jadwal = $this->generateJadwalPemupukan($dosisAplikasi, $kondisi, $statusDominan, $blok);

        // Validitas
        $validitas = $this->tentukanValiditasRekomendasi($kondisi, $kecukupanData);
        if (!$kecukupanData['data_cukup']) {
            $validitas['validitas'] = 'Estimasi Visual';
            $validitas['catatan'] = 'Rekomendasi ini bersifat estimasi visual karena data observasi belum lengkap.';
        }

        // Skor Kelengkapan & Keandalan Data (menggantikan confidence score lama)
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, $rules);

        // Dasar perhitungan (snapshot transparan)
        $dasarPerhitungan = [
            'dose_reference' => $dosisRef['dose_reference'] ?? null,
            'calculation'    => $dosisRef['calculation'] ?? null,
            'strategy'       => config('fertilization.reference_dose_strategy'),
            'catatan'        => 'Dosis berdasarkan rentang Pahan 2013, Tabel 9.13 & 9.14. Multiplier tanah/topografi/waktu TIDAK aktif.',
        ];

        // Phase info
        $phaseInfo = $this->phaseResolver->resolve($blok);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        // Simpan dengan histori
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
            // Kolom lama — diisi dari dosis aplikasi (kompatibilitas)
            'dosis_urea'               => $dosisAplikasi['dosis_urea'],
            'dosis_kcl'                => $dosisAplikasi['dosis_kcl'],
            'total_urea'               => $dosisAplikasi['total_urea'],
            'total_kcl'                => $dosisAplikasi['total_kcl'],
            'catatan_dosis'            => $catatanDosis,
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => $validitas['validitas'],
            'catatan_validitas'        => $validitas['catatan'],
            // Skor keandalan (mengisi kolom confidence lama + kolom baru)
            'confidence_score'         => $reliability['score'],
            'confidence_label'         => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence'       => 'Tingkat Kelengkapan & Keandalan Data: ' . $reliability['kategori'] . '. ' . implode(' ', array_slice($reliability['saran_peningkatan'], 0, 2)),
            'data_cukup'               => $kecukupanData['data_cukup'],
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
            // Kolom baru Pahan-v2
            'fase_tanaman_snapshot'    => $phaseInfo['fase'],
            'umur_tanaman_snapshot'    => $blok->umur_tanaman,
            'urea_min_kg_per_pokok_tahun'     => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun'     => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun'      => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun'      => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis'  => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot'    => $dosisRef['calculation']['jumlah_pokok'] ?? (int)($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json'   => $dasarPerhitungan,
            'peringatan_json'          => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score'   => $reliability['score'],
            'kategori_keandalan'       => $reliability['kategori'],
            'rincian_skor_json'        => $reliability['rincian'],
            'status_kondisi_tanaman'   => $statusKondisiTanaman,
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan'         => $alasanKelayakan,
            'versi_mesin_rekomendasi'  => config('fertilization.engine_version', 'pahan-v2'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan status kondisi tanaman berdasarkan rules terpicu.
     */
    private function tentukanStatusKondisiTanaman(array $rules): string
    {
        if (empty($rules)) return 'NORMAL_VISUAL';

        $statuses = collect($rules)->pluck('status_kebutuhan');

        if ($statuses->contains('Darurat')) return 'GEJALA_BERAT';
        if ($statuses->contains('Segera')) return 'TERINDIKASI_DEFISIENSI';
        if ($statuses->contains('Tunda')) return 'PERLU_VERIFIKASI';

        return 'NORMAL_VISUAL';
    }

    /**
     * Map skor keandalan ke label lama (Tinggi/Sedang/Rendah) untuk kompatibilitas.
     */
    private function mapReliabilityToLabel(int $score): string
    {
        if ($score >= 70) return 'Tinggi';
        if ($score >= 50) return 'Sedang';
        return 'Rendah';
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
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi);
        $jadwal = $this->generateJadwalPemupukan(
            ['dosis_urea' => $dosisRef['dosis_urea'], 'dosis_kcl' => $dosisRef['dosis_kcl'], 'total_urea' => $dosisRef['total_urea'], 'total_kcl' => $dosisRef['total_kcl']],
            $kondisi, 'Normal', $blok
        );
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $phaseInfo = $this->phaseResolver->resolve($blok);
        $doseReference = $dosisRef['dose_reference'] ?? null;

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
            'dosis_urea'               => $dosisRef['dosis_urea'],
            'dosis_kcl'                => $dosisRef['dosis_kcl'],
            'total_urea'               => $dosisRef['total_urea'],
            'total_kcl'                => $dosisRef['total_kcl'],
            'catatan_dosis'            => 'Estimasi dosis kerja berdasarkan rentang referensi Pahan (2013). Lengkapi data kondisi lahan untuk rekomendasi lebih akurat.',
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => 'Estimasi Visual',
            'catatan_validitas'        => 'Data observasi tidak lengkap — rekomendasi bersifat estimasi.',
            'confidence_score'         => $reliability['score'],
            'confidence_label'         => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence'       => 'Tingkat Kelengkapan & Keandalan Data: ' . $reliability['kategori'],
            'data_cukup'               => false,
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
            // Kolom baru Pahan-v2
            'fase_tanaman_snapshot'    => $phaseInfo['fase'],
            'umur_tanaman_snapshot'    => $blok->umur_tanaman,
            'urea_min_kg_per_pokok_tahun'     => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun'     => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun'      => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun'      => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis'  => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot'    => $dosisRef['calculation']['jumlah_pokok'] ?? (int)($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json'   => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Data tidak cukup untuk analisis detail.'],
            'peringatan_json'          => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score'   => $reliability['score'],
            'kategori_keandalan'       => $reliability['kategori'],
            'rincian_skor_json'        => $reliability['rincian'],
            'status_kondisi_tanaman'   => 'BELUM_DIOBSERVASI',
            'status_kelayakan_aplikasi' => FertilizationWindowService::PERLU_VERIFIKASI_DATA,
            'alasan_kelayakan'         => 'Data kondisi belum lengkap untuk menentukan kelayakan waktu.',
            'versi_mesin_rekomendasi'  => config('fertilization.engine_version', 'pahan-v2'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Return status normal ketika tidak ada rule yang terpicu.
     */
    private function hasilNormal(BlokLahan $blok, KondisiLahan $kondisi, array $kecukupanData): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi);
        $window = $dosisRef['window'];

        // Jika waktu tidak layak, dosis aplikasi saat ini = 0
        $dosisAplikasi = $dosisRef;
        if ($window && !$window['layak']) {
            $dosisAplikasi['dosis_urea'] = 0.0;
            $dosisAplikasi['dosis_kcl'] = 0.0;
            $dosisAplikasi['total_urea'] = 0.0;
            $dosisAplikasi['total_kcl'] = 0.0;
        }

        $jadwal = $this->generateJadwalPemupukan(
            ['dosis_urea' => $dosisAplikasi['dosis_urea'], 'dosis_kcl' => $dosisAplikasi['dosis_kcl'], 'total_urea' => $dosisAplikasi['total_urea'], 'total_kcl' => $dosisAplikasi['total_kcl']],
            $kondisi, 'Normal', $blok
        );
        $validitas = $this->tentukanValiditasRekomendasi($kondisi, $kecukupanData);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $phaseInfo = $this->phaseResolver->resolve($blok);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::LAYAK;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : '';

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
            'dosis_urea'               => $dosisAplikasi['dosis_urea'],
            'dosis_kcl'                => $dosisAplikasi['dosis_kcl'],
            'total_urea'               => $dosisAplikasi['total_urea'],
            'total_kcl'                => $dosisAplikasi['total_kcl'],
            'catatan_dosis'            => $window && !$window['layak']
                ? 'Kebutuhan tahunan tetap ada namun aplikasi saat ini ditunda. Alasan: ' . implode('; ', $window['alasan'])
                : 'Kondisi lahan normal. Dosis estimasi kerja dari rentang referensi Pahan (2013).',
            'jadwal_pemupukan'         => $jadwal,
            'validitas_rekomendasi'    => $validitas['validitas'],
            'catatan_validitas'        => $validitas['catatan'],
            'confidence_score'         => $reliability['score'],
            'confidence_label'         => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence'       => 'Tingkat Kelengkapan & Keandalan Data: ' . $reliability['kategori'],
            'data_cukup'               => $kecukupanData['data_cukup'],
            'data_kurang'              => $kecukupanData['data_kurang'],
            'notifikasi_data'          => $kecukupanData['pesan'],
            // Kolom baru Pahan-v2
            'fase_tanaman_snapshot'    => $phaseInfo['fase'],
            'umur_tanaman_snapshot'    => $blok->umur_tanaman,
            'urea_min_kg_per_pokok_tahun'     => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun'     => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun'      => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun'      => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis'  => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot'    => $dosisRef['calculation']['jumlah_pokok'] ?? (int)($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json'   => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Kondisi normal, dosis dari rentang referensi.'],
            'peringatan_json'          => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score'   => $reliability['score'],
            'kategori_keandalan'       => $reliability['kategori'],
            'rincian_skor_json'        => $reliability['rincian'],
            'status_kondisi_tanaman'   => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan'         => $alasanKelayakan,
            'versi_mesin_rekomendasi'  => config('fertilization.engine_version', 'pahan-v2'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan catatan kontekstual untuk dosis berdasarkan status dan masalah.
     */
    private function tentukanCatatanDosis(string $statusDominan, array $masalah, array $dosis, KondisiLahan $kondisi): string
    {
        $masalahStr = implode(' ', $masalah);

        if ($statusDominan === 'Tunda') {
            if (str_contains($masalahStr, 'Tergenang') || str_contains($masalahStr, 'Waterlogging')) {
                $catatan = 'TUNDA APLIKASI PUPUK TANAH. Lahan tergenang menyebabkan leaching. Perbaiki drainase terlebih dahulu, baru aplikasikan dosis ini setelah kondisi normal.';
            } elseif (str_contains($masalahStr, 'Kekeringan') || str_contains($masalahStr, 'Kemarau') || str_contains($masalahStr, 'kering')) {
                $catatan = 'TUNDA PUPUK KIMIA. Kondisi terlalu kering — pupuk tidak akan terlarut dan berisiko membakar akar. Tunggu hujan turun, baru aplikasikan dosis ini.';
            } elseif (str_contains($masalahStr, 'Tua Renta')) {
                $catatan = 'Efisiensi penyerapan hara sangat rendah pada tanaman tua. Pertimbangkan evaluasi replanting.';
            } elseif (str_contains($masalahStr, 'Curah hujan sangat tinggi')) {
                $catatan = 'TUNDA PEMUPUKAN. Curah hujan terlalu tinggi menyebabkan pencucian hara. Tunggu curah hujan kembali normal.';
            } else {
                $catatan = 'Pemupukan ditunda sampai kondisi lahan diperbaiki. Kebutuhan tahunan tetap tercatat, dosis dapat diaplikasikan setelah masalah teratasi.';
            }
        } elseif ($statusDominan === 'Darurat') {
            if (str_contains($masalahStr, 'pH') || str_contains($masalahStr, 'Masam')) {
                $catatan = 'PERHATIAN: Jangan aplikasikan Urea/KCl sebelum pH tanah dinaikkan ke 5.0+. Lakukan pengapuran (Dolomit) terlebih dahulu. Dosis kebutuhan tahunan berlaku setelah pH normal.';
            } elseif (str_contains($masalahStr, 'Busuk') || str_contains($masalahStr, 'Ganoderma')) {
                $catatan = 'PRIORITASKAN penanganan penyakit terlebih dahulu. Dosis pupuk kebutuhan tahunan berlaku setelah kondisi tanaman membaik.';
            } else {
                $catatan = 'Status DARURAT — atasi masalah utama terlebih dahulu. Kebutuhan tahunan tetap tercatat untuk referensi.';
            }
        } elseif ($statusDominan === 'Segera') {
            $catatan = 'Segera aplikasikan dosis pupuk estimasi kerja ini bersamaan dengan penanganan masalah yang teridentifikasi.';
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
            $catatan = 'Kondisi lahan normal. Estimasi dosis kerja dari rentang referensi Pahan (2013) dapat diaplikasikan sesuai jadwal.';
        }

        return $catatan;
    }

    /**
     * Hitung dosis standar Urea & KCl berdasarkan referensi Pahan 2013.
     *
     * PERUBAHAN PAHAN-V2:
     * - Dosis diambil dari tabel referensi Pahan (Tabel 9.13 & 9.14, hal. 163-164)
     * - Multiplier tanah, topografi, dan waktu DINONAKTIFKAN
     * - Sistem mengembalikan rentang (min/max) + estimasi titik tengah
     * - Waktu pemupukan diatur oleh FertilizationWindowService, bukan mengubah dosis
     *
     * Kolom lama (dosis_urea, dosis_kcl, total_urea, total_kcl) tetap diisi
     * dari nilai estimasi agar kompatibilitas view lama terjaga.
     */
    private function hitungDosisStandar(BlokLahan $blok, ?KondisiLahan $kondisi = null): array
    {
        // Gunakan PahanDoseReferenceService
        $doseRef = $this->doseService->getDoseReference($blok);

        if ($doseRef['urea']['estimate'] === null) {
            return [
                'dosis_urea' => null,
                'dosis_kcl'  => null,
                'total_urea' => null,
                'total_kcl'  => null,
                'dose_reference' => $doseRef,
                'calculation' => null,
                'window' => null,
                'peringatan' => $doseRef['warnings'],
            ];
        }

        // Hitung total via FertilizationCalculationService
        $calc = $this->calcService->calculate($blok, $doseRef);

        // Evaluasi kelayakan waktu via FertilizationWindowService
        $window = null;
        if ($kondisi) {
            $window = $this->windowService->evaluate($kondisi);
        }

        // Peringatan
        $peringatan = $doseRef['warnings'];
        if ($window && !$window['layak']) {
            $peringatan = array_merge($peringatan, $window['alasan']);
        }

        return [
            'dosis_urea'     => $doseRef['urea']['estimate'],
            'dosis_kcl'      => $doseRef['kcl']['estimate'],
            'total_urea'     => $calc['urea']['est_total'],
            'total_kcl'      => $calc['kcl']['est_total'],
            'dose_reference' => $doseRef,
            'calculation'    => $calc,
            'window'         => $window,
            'peringatan'     => $peringatan,
        ];
    }

    /**
     * Tentukan kategori umur tanaman kelapa sawit.
     * Dipertahankan untuk kompatibilitas tampilan dashboard/statistik.
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
