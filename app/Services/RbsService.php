<?php

namespace App\Services;

use App\Enums\ApplicationFeasibilityStatus;
use App\Enums\PlantConditionStatus;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RbsService
{
    /**
     * Mapping warna daun → dugaan unsur hara (untuk skor keandalan).
     */
    private array $mappingVisualUnsur = [
        'Hijau Pucat' => ['N'],
        'Kuning Merata' => ['N', 'Zn'],
        'Kuning Tepi' => ['K'],
        'Oranye/Kemerahan' => ['K'],
        'Kuning Antar Tulang' => ['Mg', 'Fe'],
        'Coklat Ujung' => ['P', 'K'],
        'Bercak Nekrotik' => ['K', 'P'],
    ];

    private PahanDoseReferenceService $doseService;

    private FertilizationWindowService $windowService;

    private FertilizationCalculationService $calcService;

    private RecommendationReliabilityService $reliabilityService;

    private PlantPhaseResolver $phaseResolver;

    private ObservationCompletenessService $completenessService;

    private PlantAgeService $ageService;

    private PlantContextService $contextService;

    private FertilizationScheduleService $scheduleService;

    private SupportingFertilizerSanitizer $sanitizer;

    public function __construct(
        PahanDoseReferenceService $doseService,
        FertilizationWindowService $windowService,
        FertilizationCalculationService $calcService,
        RecommendationReliabilityService $reliabilityService,
        PlantPhaseResolver $phaseResolver,
        ObservationCompletenessService $completenessService,
        PlantAgeService $ageService,
        PlantContextService $contextService,
        FertilizationScheduleService $scheduleService,
        SupportingFertilizerSanitizer $sanitizer
    ) {
        $this->doseService = $doseService;
        $this->windowService = $windowService;
        $this->calcService = $calcService;
        $this->reliabilityService = $reliabilityService;
        $this->phaseResolver = $phaseResolver;
        $this->completenessService = $completenessService;
        $this->ageService = $ageService;
        $this->contextService = $contextService;
        $this->scheduleService = $scheduleService;
        $this->sanitizer = $sanitizer;
    }

    /**
     * Jalankan analisis RBS untuk satu blok lahan berdasarkan kondisi terbaru.
     *
     * Versi: pahan-v2.3
     * Perubahan utama dari v2.2:
     * - Fase historis mengikuti umur pada tanggal observasi (PlantContextService)
     * - Status kondisi terpisah penuh dari status kelayakan
     * - Jadwal default 50/50, tanpa Maret/September otomatis
     * - Pupuk pendukung disanitasi
     * - Kebutuhan tahunan tetap tampil saat ditunda
     *
     * @throws \Exception
     */
    public function analisis(BlokLahan $blok): array
    {
        // 1. Ambil kondisi lahan terbaru
        $kondisi = $blok->kondisiTerbaru;
        if (! $kondisi) {
            throw new \Exception("Data kondisi lahan belum tersedia untuk blok '{$blok->nama_blok}'.");
        }

        // 2. Resolve konteks tanaman pada tanggal observasi (BUKAN saat ini)
        $tanggalObservasi = $kondisi->tanggal_observasi ?? now();
        $plantContext = $this->contextService->resolve($blok, $tanggalObservasi);
        $ageInfo = $this->ageService->calculateAgeAt($blok, $tanggalObservasi);

        // 3. Evaluasi kelengkapan observasi (satu-satunya sumber)
        $completeness = $this->completenessService->evaluate($kondisi);

        // 4. Cek apakah data kondisi cukup untuk analisis (minimal 1 field terisi)
        if (! $this->kondisiCukup($kondisi)) {
            return $this->hasilDataTidakCukup($blok, $kondisi, $plantContext, $ageInfo);
        }

        // 5. Jika data tidak cukup untuk diagnosis spesifik, kembalikan dosis dasar saja
        if (! $completeness['can_run_diagnosis']) {
            return $this->hasilDosisDasarTanpaDiagnosis($blok, $kondisi, $completeness, $plantContext, $ageInfo);
        }

        // 6. Ambil kategori umur berdasarkan umur saat observasi
        $umurSaatObservasi = $plantContext['umur'];
        $kategoriUmur = $this->tentukanKategoriUmurDariNilai($umurSaatObservasi);

        // 7. Ambil semua rule aktif, urutkan dari prioritas tertinggi
        $rules = RuleBaseLanjutan::aktif()->orderBy('prioritas')->get();

        // 8. Evaluasi setiap rule (Forward Chaining dengan Rule Chaining)
        $rulesTerpicu = [];
        $intermediateFlags = [];

        foreach ($rules as $rule) {
            if (! $this->cekPrasyaratIntermediate($rule, $intermediateFlags)) {
                continue;
            }

            if ($this->evaluasiRule($rule, $kondisi, $kategoriUmur)) {
                $rulesTerpicu[] = $rule;

                if (! empty($rule->kondisi_intermediate) && is_array($rule->kondisi_intermediate)) {
                    $intermediateFlags = array_merge($intermediateFlags, $rule->kondisi_intermediate);
                }
            }
        }

        // 9. Jika tidak ada rule terpicu, return status normal
        if (empty($rulesTerpicu)) {
            return $this->hasilNormal($blok, $kondisi, $plantContext, $ageInfo);
        }

        // 10. Susun output dari semua rule terpicu
        return $this->susunHasil($blok, $kondisi, $rulesTerpicu, $plantContext, $ageInfo);
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
        $errors = [];

        foreach ($blokLahans as $blok) {
            try {
                $results[] = [
                    'blok' => $blok,
                    'result' => $this->analisis($blok),
                ];
            } catch (\Exception $e) {
                $errors[] = "Blok {$blok->nama_blok}: ".$e->getMessage();
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
            'warna_daun' => 'Warna daun',
            'ph_tanah' => 'pH tanah',
            'kelembaban_tanah' => 'Kelembaban tanah',
            'curah_hujan_kategori' => 'Curah hujan',
            'musim_saat_ini' => 'Musim saat ini',
            'kondisi_drainase' => 'Kondisi drainase',
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
        $adaDugaanUnsur = ! empty($kondisi->gejala_defisiensi);

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
            : 'Data observasi belum cukup untuk menghasilkan rekomendasi yang kuat. Lengkapi data berikut: '.implode(', ', $dataKurang).'.';

        return [
            'data_cukup' => $dataCukup,
            'data_kurang' => $dataKurang,
            'pesan' => $pesan,
            'terisi' => $terisi,
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
        $warnaDaun = $kondisi->warna_daun !== null;
        $phTanah = $kondisi->ph_tanah !== null;
        $kelembaban = $kondisi->kelembaban_tanah !== null;
        $curahHujan = $kondisi->curah_hujan_kategori !== null;
        $drainase = $kondisi->kondisi_drainase !== null;
        $tglPupuk = $kondisi->tanggal_pemupukan_terakhir !== null;
        $musim = $kondisi->musim_saat_ini !== null;

        // Cukup Kuat: warna daun + pH + (kelembaban ATAU curah hujan) + drainase
        $isCukupKuat = $warnaDaun
            && $phTanah
            && ($kelembaban || $curahHujan)
            && $drainase;

        if ($isCukupKuat) {
            $catatan = 'Rekomendasi cukup kuat karena didukung data warna daun, pH tanah, '
                .($kelembaban ? 'kelembaban, ' : '')
                .($curahHujan ? 'curah hujan, ' : '')
                .'dan drainase.';

            return [
                'validitas' => 'Cukup Kuat',
                'catatan' => rtrim($catatan, ', ').'.',
            ];
        }

        // Default: Estimasi Visual
        $missing = [];
        if (! $phTanah) {
            $missing[] = 'pH tanah';
        }
        if (! $drainase) {
            $missing[] = 'kondisi drainase';
        }
        if (! $kelembaban && ! $curahHujan) {
            $missing[] = 'kelembaban/curah hujan';
        }

        $catatan = 'Rekomendasi ini bersifat estimasi visual karena belum didukung data '
            .implode(' dan ', $missing).'.';

        return [
            'validitas' => 'Estimasi Visual',
            'catatan' => $catatan,
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
        if (! empty($kondisi->gejala_defisiensi)) {
            $terisi++;
        }

        $skorA = (int) round(($terisi / $totalFields) * 40);
        $score += $skorA;
        $details[] = "Kelengkapan data: {$terisi}/{$totalFields} field ({$skorA} poin)";

        // B. Jumlah Rule Terpicu — Maks 25 poin
        $jumlahRule = count($rulesTerpicu);
        $skorB = match (true) {
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

        if ($warnaDaun && ! empty($dugaanUnsur)) {
            if ($this->isDugaanUnsurSesuaiWarnaDaun($warnaDaun, $dugaanUnsur)) {
                $skorC = 20;
            } else {
                $skorC = 10; // Ada data tapi tidak cocok mapping
            }
        } elseif ($warnaDaun || ! empty($dugaanUnsur)) {
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
            $catatan = 'Keyakinan rendah karena data '.implode(', ', array_slice($dataKurangFields, 0, 3)).' belum diisi.';
        } elseif ($label === 'Tinggi') {
            $catatan = 'Keyakinan tinggi karena data observasi lengkap dan beberapa rule saling mendukung.';
        } else {
            $catatan = 'Keyakinan sedang. Lengkapi data untuk meningkatkan akurasi rekomendasi.';
        }

        return [
            'score' => $score,
            'label' => $label,
            'catatan' => $catatan,
            'data_kurang' => $dataKurangFields,
        ];
    }

    /**
     * Cek apakah dugaan unsur sesuai dengan warna daun (mapping visual).
     */
    private function isDugaanUnsurSesuaiWarnaDaun(?string $warnaDaun, array $dugaanUnsur): bool
    {
        if (! $warnaDaun || empty($dugaanUnsur)) {
            return false;
        }

        $unsurCocok = $this->mappingVisualUnsur[$warnaDaun] ?? [];
        if (empty($unsurCocok)) {
            return false;
        }

        return ! empty(array_intersect($dugaanUnsur, $unsurCocok));
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
        if ($warnaDaun === 'Hijau Normal' && ! empty($defisiensi)) {
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
    // CORE: Evaluasi Rule
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Cek apakah prasyarat intermediate terpenuhi (Rule Chaining - A2).
     */
    private function cekPrasyaratIntermediate(RuleBaseLanjutan $rule, array $intermediateFlags): bool
    {
        if (empty($rule->prasyarat_intermediate) || ! is_array($rule->prasyarat_intermediate)) {
            return true;
        }

        foreach ($rule->prasyarat_intermediate as $key => $value) {
            if (! isset($intermediateFlags[$key]) || $intermediateFlags[$key] !== $value) {
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
        // PERBAIKAN: Jika rule mensyaratkan defisiensi tertentu,
        // kondisi WAJIB memiliki data defisiensi yang cocok (AND logic ketat)
        if ($rule->kondisi_defisiensi !== null) {
            $jumlahKondisiDiRule++;

            $defisiensiInput = $kondisi->gejala_defisiensi ?? [];

            // Jika input defisiensi kosong, rule tidak boleh terpicu
            if (empty($defisiensiInput)) {
                return false;
            }

            // Strict comparison untuk mencegah type juggling
            if (! in_array($rule->kondisi_defisiensi, $defisiensiInput, true)) {
                return false;
            }

            $jumlahKondisiCocok++;
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
            if (! $kondisi->ada_serangan_hama) {
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
            // Generate fingerprint untuk data baru
            $fingerprint = $this->generateFingerprint($data);
            $data['analysis_fingerprint'] = $fingerprint;

            // Cek apakah hasil sama dengan rekomendasi terakhir
            $existing = RekomendasiRbs::where('blok_lahan_id', $blokLahanId)
                ->where('is_latest', true)
                ->first();

            if ($existing && $this->hasilSamaDenganSebelumnya($existing, $data)) {
                // Hanya update tanggal analisis dan field yang berubah seiring waktu
                $existing->update([
                    'tanggal_analisis' => $data['tanggal_analisis'],
                    'analysis_fingerprint' => $fingerprint,
                    'alasan_kelayakan' => $data['alasan_kelayakan'] ?? $existing->alasan_kelayakan,
                    'status_kelayakan_aplikasi' => $data['status_kelayakan_aplikasi'] ?? $existing->status_kelayakan_aplikasi,
                    'catatan_dosis' => $data['catatan_dosis'] ?? $existing->catatan_dosis,
                ]);
                $existing->touch();

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
     * Menggunakan fingerprint SHA-256 dari data penting untuk perbandingan akurat.
     */
    private function hasilSamaDenganSebelumnya(RekomendasiRbs $existing, array $newData): bool
    {
        $newFingerprint = $this->generateFingerprint($newData);

        // Jika existing sudah punya fingerprint, bandingkan langsung
        if ($existing->analysis_fingerprint) {
            return $existing->analysis_fingerprint === $newFingerprint;
        }

        // Fallback untuk data lama tanpa fingerprint: bandingkan field utama
        return $existing->kondisi_lahan_id == $newData['kondisi_lahan_id']
            && $existing->status_kebutuhan_dominan === $newData['status_kebutuhan_dominan']
            && $existing->jumlah_rule_terpicu == $newData['jumlah_rule_terpicu']
            && (float) $existing->dosis_urea === (float) ($newData['dosis_urea'] ?? 0)
            && (float) $existing->dosis_kcl === (float) ($newData['dosis_kcl'] ?? 0)
            && $existing->status_kondisi_tanaman === ($newData['status_kondisi_tanaman'] ?? null)
            && $existing->status_kelayakan_aplikasi === ($newData['status_kelayakan_aplikasi'] ?? null);
    }

    /**
     * Generate fingerprint SHA-256 dari data analisis penting.
     * Digunakan untuk deteksi perubahan bermakna pada hasil.
     */
    private function generateFingerprint(array $data): string
    {
        // Ekstrak kode rule dari rules_terpicu
        $rulesCodes = collect($data['rules_terpicu'] ?? [])
            ->pluck('indikasi')
            ->sort()
            ->values()
            ->toArray();

        $fingerprintData = [
            'kondisi_lahan_id' => $data['kondisi_lahan_id'] ?? null,
            'versi_mesin' => $data['versi_mesin_rekomendasi'] ?? null,
            'fase' => $data['fase_tanaman_snapshot'] ?? null,
            'umur' => $data['umur_tanaman_snapshot'] ?? null,
            'strategi_estimasi' => $data['strategi_estimasi_dosis'] ?? null,
            'urea_estimasi' => $data['urea_estimasi_kg_per_pokok_tahun'] ?? null,
            'kcl_estimasi' => $data['kcl_estimasi_kg_per_pokok_tahun'] ?? null,
            'status_kondisi' => $data['status_kondisi_tanaman'] ?? null,
            'status_kelayakan' => $data['status_kelayakan_aplikasi'] ?? null,
            'rules_terpicu' => $rulesCodes,
            'jumlah_jadwal' => count($data['jadwal_pemupukan'] ?? []),
            'kelengkapan_data_score' => $data['kelengkapan_data_score'] ?? null,
        ];

        // JSON encode dengan key sorting agar hasilnya deterministik
        ksort($fingerprintData);
        $json = json_encode($fingerprintData, JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json);
    }

    /**
     * Susun hasil analisis dari rule-rule yang terpicu.
     */
    private function susunHasil(BlokLahan $blok, KondisiLahan $kondisi, array $rules, array $plantContext, array $ageInfo = []): array
    {
        // Tentukan status dominan (Tunda is prioritized to override Segera/Normal/Darurat)
        $hierarki = ['Tunda' => 4, 'Darurat' => 3, 'Segera' => 2, 'Normal' => 1];
        $statusDominan = collect($rules)
            ->sortByDesc(fn ($r) => $hierarki[$r->status_kebutuhan] ?? 0)
            ->first()
            ->status_kebutuhan;

        // Kumpulkan masalah unik
        $masalah = collect($rules)->pluck('indikasi_masalah')->unique()->values()->toArray();

        // Kumpulkan rekomendasi pupuk (melalui sanitizer)
        $pupuk = $this->sanitizer->sanitize($rules);

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
            $saranUtama = implode(' & ', $saranTambahan).' sebelum pemupukan kimia dilakukan. | '.$saranUtama;
        }

        // Hitung dosis referensi Pahan (selalu dihitung untuk kebutuhan tahunan)
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $ageInfo);

        // Evaluasi kelayakan waktu
        $window = $dosisRef['window'];

        // Status kondisi tanaman (HANYA dari DIAGNOSIS_VISUAL)
        $statusKondisiTanaman = $this->tentukanStatusKondisiTanaman($rules);

        // Status kelayakan aplikasi (HANYA dari FertilizationWindowService)
        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::PERLU_VERIFIKASI_DATA;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : 'Data kelayakan waktu belum tersedia.';

        // Dosis aplikasi saat ini:
        // - Kelayakan tidak layak → 0 (tapi kebutuhan tahunan tetap)
        // - Kondisi berat TIDAK otomatis menunda (pemisahan status)
        if ($window && ! $window['layak']) {
            $dosisAplikasi = [
                'dosis_urea' => 0.0,
                'dosis_kcl' => 0.0,
                'total_urea' => 0.0,
                'total_kcl' => 0.0,
            ];
        } else {
            $dosisAplikasi = [
                'dosis_urea' => $dosisRef['dosis_urea'],
                'dosis_kcl' => $dosisRef['dosis_kcl'],
                'total_urea' => $dosisRef['total_urea'],
                'total_kcl' => $dosisRef['total_kcl'],
            ];
        }

        // Catatan dosis
        $catatanDosis = $this->tentukanCatatanDosis($statusDominan, $masalah, $dosisAplikasi, $kondisi);

        // Jadwal pemupukan via FertilizationScheduleService (v2.3)
        $jadwal = $this->scheduleService->generate(
            $dosisAplikasi,
            $kondisi,
            $blok,
            $window ?? ['layak' => true, 'alasan' => []],
            $plantContext
        );

        // Sanitasi pupuk pendukung
        $pupukSanitized = $this->sanitizer->sanitize($rules);

        // Completeness
        $completeness = $this->completenessService->evaluate($kondisi);

        // Validitas
        $validitas = $completeness['can_run_diagnosis'] ? 'Cukup Kuat' : 'Estimasi Visual';
        $catatanValiditas = $completeness['reason'];

        // Skor Kelengkapan & Keandalan Data
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, $rules);

        // Dasar perhitungan (snapshot transparan)
        $dasarPerhitungan = [
            'dose_reference' => $dosisRef['dose_reference'] ?? null,
            'calculation' => $dosisRef['calculation'] ?? null,
            'strategy' => config('fertilization.reference_dose_strategy'),
            'catatan' => 'Dosis berdasarkan rentang Pahan 2013, Tabel 9.13 & 9.14. Multiplier tanah/topografi/waktu TIDAK aktif.',
        ];

        $doseReference = $dosisRef['dose_reference'] ?? null;

        // Simpan dengan histori
        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => collect($rules)->map(fn ($r) => [
                'rule_id' => $r->id,
                'indikasi' => $r->indikasi_masalah,
                'pupuk' => $r->jenis_pupuk_utama,
                'status' => $r->status_kebutuhan,
                'prioritas' => $r->prioritas,
            ])->toArray(),
            'masalah_teridentifikasi' => $masalah,
            'rekomendasi_pupuk' => $pupukSanitized,
            'saran_tindakan_utama' => $saranUtama,
            'status_kebutuhan_dominan' => $statusDominan,
            'jumlah_rule_terpicu' => count($rules),
            // Kolom lama — kompatibilitas
            'dosis_urea' => $dosisAplikasi['dosis_urea'],
            'dosis_kcl' => $dosisAplikasi['dosis_kcl'],
            'total_urea' => $dosisAplikasi['total_urea'],
            'total_kcl' => $dosisAplikasi['total_kcl'],
            'catatan_dosis' => $catatanDosis,
            'jadwal_pemupukan' => $jadwal,
            'validitas_rekomendasi' => $validitas,
            'catatan_validitas' => $catatanValiditas,
            // Skor keandalan
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Tingkat Kelengkapan & Keandalan Data: '.$reliability['kategori'].'. '.implode(' ', array_slice($reliability['saran_peningkatan'], 0, 2)),
            'data_cukup' => $completeness['can_run_diagnosis'],
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.3 — fase historis via PlantContextService
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $dosisRef['calculation']['jumlah_pokok'] ?? (int) ($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json' => $dasarPerhitungan,
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => $statusKondisiTanaman,
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan' => $alasanKelayakan,
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.3'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan status kondisi tanaman berdasarkan rules terpicu.
     *
     * PERBAIKAN v2.2:
     * - Hanya rule jenis_rule = DIAGNOSIS_VISUAL yang mempengaruhi status kondisi
     * - Rule PEMBATAS_APLIKASI, SARAN_PENDUKUNG, PERINGATAN_DATA TIDAK mengubah status
     * - Mapping dari tingkat_keparahan ke PlantConditionStatus
     */
    private function tentukanStatusKondisiTanaman(array $rules): string
    {
        if (empty($rules)) {
            return PlantConditionStatus::NORMAL_VISUAL->value;
        }

        // Filter hanya rule DIAGNOSIS_VISUAL
        $diagnosisRules = collect($rules)->filter(function ($rule) {
            return $rule->jenis_rule === 'DIAGNOSIS_VISUAL';
        });

        if ($diagnosisRules->isEmpty()) {
            return PlantConditionStatus::NORMAL_VISUAL->value;
        }

        // Ambil tingkat keparahan tertinggi dari rule DIAGNOSIS_VISUAL
        $severityOrder = ['BERAT' => 4, 'SEDANG' => 3, 'RINGAN' => 2, 'PERLU_VERIFIKASI' => 1, 'NORMAL' => 0];

        $maxSeverity = $diagnosisRules->sortByDesc(function ($rule) use ($severityOrder) {
            return $severityOrder[$rule->tingkat_keparahan ?? 'NORMAL'] ?? 0;
        })->first();

        $severity = $maxSeverity->tingkat_keparahan ?? 'NORMAL';

        return PlantConditionStatus::fromSeverity($severity)->value;
    }

    /**
     * Map skor keandalan ke label lama (Tinggi/Sedang/Rendah) untuk kompatibilitas.
     */
    private function mapReliabilityToLabel(int $score): string
    {
        if ($score >= 70) {
            return 'Tinggi';
        }
        if ($score >= 50) {
            return 'Sedang';
        }

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
            || ! empty($kondisi->gejala_defisiensi)
            || $kondisi->ada_serangan_hama === true
            || $kondisi->curah_hujan_kategori !== null
            || $kondisi->ada_gulma_dominan === true;
    }

    /**
     * Return hasil ketika data kondisi tidak cukup untuk analisis.
     */
    private function hasilDataTidakCukup(BlokLahan $blok, KondisiLahan $kondisi, array $plantContext, array $ageInfo): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $ageInfo);
        $jadwal = [];
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        $completeness = $this->completenessService->evaluate($kondisi);

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Data kondisi lahan belum lengkap untuk analisis'],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Pupuk Standar Rutin', 'dosis' => 'Sesuai jadwal pemupukan reguler — lengkapi data kondisi untuk rekomendasi spesifik']],
            'saran_tindakan_utama' => 'Data observasi kondisi lahan belum cukup untuk memberikan rekomendasi spesifik. Silakan lengkapi data kondisi (warna daun, pH tanah, kelembaban, kondisi drainase, dll) lalu jalankan analisis ulang.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 0.0,
            'dosis_kcl' => 0.0,
            'total_urea' => 0.0,
            'total_kcl' => 0.0,
            'catatan_dosis' => 'Data observasi belum cukup untuk menghasilkan jadwal operasional. Kebutuhan tahunan tetap tercatat di kolom khusus. Lengkapi data kondisi lahan untuk rekomendasi lebih akurat.',
            'jadwal_pemupukan' => $jadwal,
            'validitas_rekomendasi' => 'Estimasi Visual',
            'catatan_validitas' => 'Data observasi tidak lengkap — rekomendasi bersifat estimasi.',
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Tingkat Kelengkapan & Keandalan Data: '.$reliability['kategori'],
            'data_cukup' => false,
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.3 — menggunakan plantContext
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $dosisRef['calculation']['jumlah_pokok'] ?? (int) ($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Data tidak cukup untuk analisis detail. Kebutuhan tahunan tersimpan.'],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::BELUM_DIOBSERVASI->value,
            'status_kelayakan_aplikasi' => ApplicationFeasibilityStatus::PERLU_VERIFIKASI_DATA->value,
            'alasan_kelayakan' => 'Data kondisi belum lengkap untuk menentukan kelayakan waktu.',
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.3'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Return hasil ketika data cukup untuk dosis dasar tapi TIDAK cukup untuk diagnosis RBS.
     * Kebutuhan tahunan tetap dihitung, tapi tidak ada diagnosis spesifik.
     */
    private function hasilDosisDasarTanpaDiagnosis(BlokLahan $blok, KondisiLahan $kondisi, array $completeness, array $plantContext, array $ageInfo): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $ageInfo);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        $saranLengkapi = 'Data observasi belum lengkap untuk diagnosis spesifik. Lengkapi: '.implode(', ', $completeness['missing_fields']).'.';

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Data belum cukup untuk diagnosis spesifik — kebutuhan tahunan tetap dihitung'],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Pupuk Standar Rutin (Urea + KCl)', 'dosis' => 'Sesuai kebutuhan tahunan Pahan — lengkapi data untuk rekomendasi spesifik']],
            'saran_tindakan_utama' => $saranLengkapi,
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 0.0,
            'dosis_kcl' => 0.0,
            'total_urea' => 0.0,
            'total_kcl' => 0.0,
            'catatan_dosis' => 'Data observasi belum memenuhi syarat minimum untuk diagnosis. Kebutuhan tahunan tetap tersimpan. Lengkapi data untuk mendapatkan jadwal operasional.',
            'jadwal_pemupukan' => [],
            'validitas_rekomendasi' => 'Estimasi Visual',
            'catatan_validitas' => $completeness['reason'],
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Tingkat Kelengkapan & Keandalan Data: '.$reliability['kategori'],
            'data_cukup' => false,
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.3 — menggunakan plantContext
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $dosisRef['calculation']['jumlah_pokok'] ?? (int) ($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Dosis dasar dihitung, diagnosis belum dijalankan.'],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::PERLU_VERIFIKASI->value,
            'status_kelayakan_aplikasi' => ApplicationFeasibilityStatus::PERLU_VERIFIKASI_DATA->value,
            'alasan_kelayakan' => $completeness['reason'],
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.3'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan kategori umur dari nilai integer umur.
     */
    private function tentukanKategoriUmurDariNilai(?int $umur): ?string
    {
        if ($umur === null) {
            return null;
        }

        if ($umur < 3) {
            return 'Belum Menghasilkan';
        }
        if ($umur <= 8) {
            return 'Remaja';
        }
        if ($umur <= 14) {
            return 'Menghasilkan Muda';
        }
        if ($umur <= 25) {
            return 'Menghasilkan Tua';
        }

        return 'Tua Renta';
    }

    /**
     * Return status normal ketika tidak ada rule yang terpicu.
     */
    private function hasilNormal(BlokLahan $blok, KondisiLahan $kondisi, array $plantContext, array $ageInfo = []): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $ageInfo);
        $window = $dosisRef['window'];

        // Jika waktu tidak layak, dosis aplikasi saat ini = 0
        $dosisAplikasi = $dosisRef;
        if ($window && ! $window['layak']) {
            $dosisAplikasi['dosis_urea'] = 0.0;
            $dosisAplikasi['dosis_kcl'] = 0.0;
            $dosisAplikasi['total_urea'] = 0.0;
            $dosisAplikasi['total_kcl'] = 0.0;
        }

        // Jadwal baru via FertilizationScheduleService
        $jadwal = $this->scheduleService->generate(
            ['dosis_urea' => $dosisAplikasi['dosis_urea'], 'dosis_kcl' => $dosisAplikasi['dosis_kcl'], 'total_urea' => $dosisAplikasi['total_urea'], 'total_kcl' => $dosisAplikasi['total_kcl']],
            $kondisi,
            $blok,
            $window ?? ['layak' => true, 'alasan' => []],
            $plantContext
        );

        $completeness = $this->completenessService->evaluate($kondisi);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::LAYAK;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : '';

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Tidak ada masalah teridentifikasi'],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Pupuk Standar Rutin', 'dosis' => 'Sesuai jadwal pemupukan reguler']],
            'saran_tindakan_utama' => 'Lanjutkan program pemupukan standar. Kondisi lahan dalam batas normal.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => $dosisAplikasi['dosis_urea'],
            'dosis_kcl' => $dosisAplikasi['dosis_kcl'],
            'total_urea' => $dosisAplikasi['total_urea'],
            'total_kcl' => $dosisAplikasi['total_kcl'],
            'catatan_dosis' => $window && ! $window['layak']
                ? 'Kebutuhan tahunan tetap ada namun aplikasi saat ini ditunda. Alasan: '.implode('; ', $window['alasan'])
                : 'Kondisi lahan normal. Dosis estimasi kerja dari rentang referensi Pahan (2013).',
            'jadwal_pemupukan' => $jadwal,
            'validitas_rekomendasi' => $completeness['can_run_diagnosis'] ? 'Cukup Kuat' : 'Estimasi Visual',
            'catatan_validitas' => $completeness['reason'],
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Tingkat Kelengkapan & Keandalan Data: '.$reliability['kategori'],
            'data_cukup' => $completeness['can_run_diagnosis'],
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.3
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $dosisRef['calculation']['jumlah_pokok'] ?? (int) ($blok->luas_ha * $blok->sph),
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Kondisi normal, dosis dari rentang referensi.'],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::NORMAL_VISUAL->value,
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan' => $alasanKelayakan,
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.3'),
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
                $catatan = 'PENTING: Harap '.implode(' dan ', $notes).' sebelum pupuk kimia ditabur agar penyerapan hara oleh pokok sawit optimal.';
            }
        } else {
            $catatan = 'Kondisi lahan normal. Estimasi dosis kerja dari rentang referensi Pahan (2013) dapat diaplikasikan sesuai jadwal.';
        }

        return $catatan;
    }

    /**
     * Hitung dosis standar Urea & KCl berdasarkan referensi Pahan 2013.
     *
     * PERUBAHAN PAHAN-V2.2:
     * - Dosis diambil berdasarkan umur PADA TANGGAL OBSERVASI
     * - Menggunakan getDoseReferenceForContext() jika ageInfo tersedia
     * - Multiplier tanah/topografi/waktu DINONAKTIFKAN
     *
     * @param  array|null  $ageInfo  Output dari PlantAgeService::calculateAgeAt()
     */
    private function hitungDosisStandar(BlokLahan $blok, ?KondisiLahan $kondisi = null, ?array $ageInfo = null): array
    {
        // PERBAIKAN v2.2: Gunakan umur dan fase saat observasi jika tersedia
        if ($ageInfo && $ageInfo['umur'] !== null) {
            $umurObservasi = $ageInfo['umur'];
            $phaseInfo = $this->phaseResolver->resolve($blok);
            $fase = $phaseInfo['fase'];

            // Jika fase null tapi umur jelas, auto-resolve
            if ($fase === null && $umurObservasi !== null) {
                if ($umurObservasi < 3) {
                    $fase = 'TBM';
                } elseif ($umurObservasi > 3) {
                    $fase = 'TM';
                }
            }

            if ($fase !== null) {
                $doseRef = $this->doseService->getDoseReferenceForContext($blok, $umurObservasi, $fase);
            } else {
                $doseRef = $this->doseService->getDoseReference($blok);
            }
        } else {
            $doseRef = $this->doseService->getDoseReference($blok);
        }

        if ($doseRef['urea']['estimate'] === null) {
            return [
                'dosis_urea' => null,
                'dosis_kcl' => null,
                'total_urea' => null,
                'total_kcl' => null,
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
        if ($window && ! $window['layak']) {
            $peringatan = array_merge($peringatan, $window['alasan']);
        }

        return [
            'dosis_urea' => $doseRef['urea']['estimate'],
            'dosis_kcl' => $doseRef['kcl']['estimate'],
            'total_urea' => $calc['urea']['est_total'],
            'total_kcl' => $calc['kcl']['est_total'],
            'dose_reference' => $doseRef,
            'calculation' => $calc,
            'window' => $window,
            'peringatan' => $peringatan,
        ];
    }

    /**
     * Tentukan kategori umur tanaman kelapa sawit.
     * Dipertahankan untuk kompatibilitas tampilan dashboard/statistik.
     */
    private function tentukanKategoriUmur(int $umur): string
    {
        if ($umur < 3) {
            return 'Belum Menghasilkan';
        }
        if ($umur <= 8) {
            return 'Remaja';
        }
        if ($umur <= 14) {
            return 'Menghasilkan Muda';
        }
        if ($umur <= 25) {
            return 'Menghasilkan Tua';
        }

        return 'Tua Renta';
    }
}
