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
    private PahanDoseReferenceService $doseService;

    private FertilizationWindowService $windowService;

    private FertilizationCalculationService $calcService;

    private RecommendationReliabilityService $reliabilityService;

    private ObservationCompletenessService $completenessService;

    private PlantContextService $contextService;

    private FertilizationScheduleService $scheduleService;

    private SupportingFertilizerSanitizer $sanitizer;

    private AnnualFertilizerSnapshotBuilder $snapshotBuilder;

    private FertilizationRealizationService $realizationService;

    private CurrentApplicationCalculator $currentAppCalculator;

    private ProgramPemupukanService $programService;

    private ProgramStatusService $programStatusService;

    public function __construct(
        PahanDoseReferenceService $doseService,
        FertilizationWindowService $windowService,
        FertilizationCalculationService $calcService,
        RecommendationReliabilityService $reliabilityService,
        ObservationCompletenessService $completenessService,
        PlantContextService $contextService,
        FertilizationScheduleService $scheduleService,
        SupportingFertilizerSanitizer $sanitizer,
        AnnualFertilizerSnapshotBuilder $snapshotBuilder,
        FertilizationRealizationService $realizationService,
        CurrentApplicationCalculator $currentAppCalculator,
        ProgramPemupukanService $programService,
        ProgramStatusService $programStatusService
    ) {
        $this->doseService = $doseService;
        $this->windowService = $windowService;
        $this->calcService = $calcService;
        $this->reliabilityService = $reliabilityService;
        $this->completenessService = $completenessService;
        $this->contextService = $contextService;
        $this->scheduleService = $scheduleService;
        $this->sanitizer = $sanitizer;
        $this->snapshotBuilder = $snapshotBuilder;
        $this->realizationService = $realizationService;
        $this->currentAppCalculator = $currentAppCalculator;
        $this->programService = $programService;
        $this->programStatusService = $programStatusService;
    }

    /**
     * Jalankan analisis RBS untuk satu blok lahan berdasarkan kondisi terbaru.
     *
     * Versi: pahan-v2.6
     * Perubahan utama dari v2.4:
     * - Aplikasi saat ini = tahap aktif (50% Tahap 1, sisa aktual Tahap 2)
     * - Integrasi realisasi pemupukan
     * - Fingerprint mencakup luas/SPH/realisasi
     * - Snapshot luas dan SPH disimpan
     * - CurrentApplicationCalculator menentukan jumlah tahap aktif
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

        // 3. Evaluasi kelengkapan observasi (satu-satunya sumber)
        $completeness = $this->completenessService->evaluate($kondisi);

        // 4. Cek verifikasi fase: umur 3 tanpa verifikasi tidak menghasilkan dosis
        if ($plantContext['umur'] === 3 && $plantContext['fase'] === null && $plantContext['needs_phase_verification']) {
            return $this->hasilPerluVerifikasiFase($blok, $kondisi, $plantContext);
        }

        // 5. Cek apakah data kondisi cukup untuk analisis (minimal 1 field terisi)
        if (! $this->kondisiCukupMinimal($kondisi)) {
            return $this->hasilDataTidakCukup($blok, $kondisi, $plantContext);
        }

        // 6. Jika data tidak cukup untuk diagnosis spesifik, kembalikan dosis dasar saja
        if (! $completeness['can_run_diagnosis']) {
            return $this->hasilDosisDasarTanpaDiagnosis($blok, $kondisi, $completeness, $plantContext);
        }

        // 7. Ambil kategori umur berdasarkan umur saat observasi
        $umurSaatObservasi = $plantContext['umur'];
        $kategoriUmur = $this->tentukanKategoriUmurDariNilai($umurSaatObservasi);

        // 8. Ambil semua rule aktif, urutkan dari prioritas tertinggi
        $rules = RuleBaseLanjutan::aktif()->orderBy('prioritas')->get();

        // 9. Forward chaining sampai tidak ada fakta baru (fixpoint).
        // Rule hanya boleh terpicu sekali, tetapi seluruh agenda dievaluasi ulang
        // ketika sebuah rule menghasilkan fakta intermediate baru.
        $rulesTerpicu = [];
        $triggeredRuleIds = [];
        $intermediateFlags = [];
        $factsChanged = true;
        $iteration = 0;
        $maxIterations = max(1, $rules->count());

        while ($factsChanged && $iteration < $maxIterations) {
            $factsChanged = false;
            $iteration++;

            foreach ($rules as $rule) {
                if (isset($triggeredRuleIds[$rule->id])) {
                    continue;
                }

                if (! $this->cekPrasyaratIntermediate($rule, $intermediateFlags)) {
                    continue;
                }

                if (! $this->evaluasiRule($rule, $kondisi, $kategoriUmur, $blok)) {
                    continue;
                }

                $rulesTerpicu[] = $rule;
                $triggeredRuleIds[$rule->id] = true;

                if (! empty($rule->kondisi_intermediate) && is_array($rule->kondisi_intermediate)) {
                    $newFacts = array_merge($intermediateFlags, $rule->kondisi_intermediate);
                    if ($newFacts !== $intermediateFlags) {
                        $intermediateFlags = $newFacts;
                        $factsChanged = true;
                    }
                }
            }
        }

        $hasVisualRule = collect($rulesTerpicu)
            ->contains(fn ($rule) => $rule->jenis_rule === 'DIAGNOSIS_VISUAL');
        $normalLeafCondition = config('observation.normal_leaf_condition', 'Hijau Normal');

        // Gejala selain kondisi normal tidak boleh dianggap normal hanya karena
        // belum memiliki rule aktif yang cocok.
        if (! $hasVisualRule && $kondisi->warna_daun !== $normalLeafCondition) {
            $reviewCompleteness = $completeness;
            $reviewCompleteness['can_run_diagnosis'] = false;
            $reviewCompleteness['unmatched_leaf'] = true;
            $reviewCompleteness['missing_fields'] = [];
            $reviewCompleteness['reason'] = filled($kondisi->warna_daun)
                ? "Gejala '{$kondisi->warna_daun}' belum sesuai dengan rule kondisi daun yang aktif. Lakukan pemeriksaan lapangan lanjutan."
                : 'Hasil pemeriksaan daun belum dapat dipastikan. Lakukan pemeriksaan lapangan lanjutan.';

            return $this->hasilDosisDasarTanpaDiagnosis($blok, $kondisi, $reviewCompleteness, $plantContext);
        }

        // 10. Kondisi normal hanya berasal dari pilihan normal yang eksplisit.
        if (empty($rulesTerpicu)) {
            return $this->hasilNormal($blok, $kondisi, $plantContext);
        }

        // 11. Susun output dari semua rule terpicu
        return $this->susunHasil($blok, $kondisi, $rulesTerpicu, $plantContext);
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

    // Evaluasi aturan secara berurutan.

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
    private function evaluasiRule(RuleBaseLanjutan $rule, KondisiLahan $kondisi, ?string $kategoriUmur, BlokLahan $blok): bool
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

        // Cek topografi
        if ($rule->kondisi_topografi !== null) {
            $jumlahKondisiDiRule++;
            if ($blok->topografi === null) {
                return false;
            }
            if ($rule->kondisi_topografi !== $blok->topografi) {
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

        // Cek rentang curah hujan numerik agar ambang rule dapat ditelusuri.
        if ($rule->kondisi_curah_hujan_min_mm !== null || $rule->kondisi_curah_hujan_max_mm !== null) {
            $jumlahKondisiDiRule++;
            if ($kondisi->curah_hujan_mm_bulanan === null) {
                return false;
            }

            $curahHujan = (float) $kondisi->curah_hujan_mm_bulanan;
            if ($rule->kondisi_curah_hujan_min_mm !== null
                && $curahHujan < (float) $rule->kondisi_curah_hujan_min_mm) {
                return false;
            }
            if ($rule->kondisi_curah_hujan_max_mm !== null
                && $curahHujan > (float) $rule->kondisi_curah_hujan_max_mm) {
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

        // Rule kesimpulan boleh bergantung penuh pada fakta intermediate.
        // Rule biasa tetap wajib memiliki minimal satu kondisi observasi yang cocok.
        if ($jumlahKondisiDiRule === 0) {
            return ! empty($rule->prasyarat_intermediate);
        }

        if ($jumlahKondisiCocok === 0) {
            return false;
        }

        return true;
    }

    // Simpan hasil sebagai histori rekomendasi.

    /**
     * Simpan rekomendasi baru dengan histori (Fitur 1).
     * Jika hasil analisis sama persis dengan rekomendasi terakhir (kondisi_lahan_id dan status sama),
     * tidak membuat record baru — hanya update tanggal_analisis.
     */
    private function simpanDenganHistori(int $blokLahanId, array $data): RekomendasiRbs
    {
        return DB::transaction(function () use ($blokLahanId, $data) {
            // Resolve program sebelum fingerprint agar program_pemupukan_id benar-benar
            // menjadi bagian identitas hasil analisis.
            $blok = BlokLahan::whereKey($blokLahanId)->lockForUpdate()->first();
            $tahunProgram = now()->year;
            if ($blok && ($data['urea_total_estimasi_tahunan'] ?? null) > 0) {
                $program = $this->programService->resolveActiveProgram($blok, $tahunProgram);
                $data['program_pemupukan_id'] = $program->id;
            }

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
                    'alasan_tahap' => $data['alasan_tahap'] ?? $existing->alasan_tahap,
                    'jadwal_pemupukan' => $data['jadwal_pemupukan'] ?? $existing->jadwal_pemupukan,
                    'saran_tindakan_utama' => $data['saran_tindakan_utama'] ?? $existing->saran_tindakan_utama,
                    'catatan_validitas' => $data['catatan_validitas'] ?? $existing->catatan_validitas,
                    'notifikasi_data' => $data['notifikasi_data'] ?? $existing->notifikasi_data,
                    'catatan_confidence' => $data['catatan_confidence'] ?? $existing->catatan_confidence,
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

        // Pahan v2.5: Fingerprint menyertakan luas, SPH, jumlah pokok, total tahunan,
        // aplikasi saat ini, sisa tahunan, tahap aktif, dan ringkasan realisasi
        // Pahan v2.8: Tambah program_pemupukan_id dan status_program
        $fingerprintData = [
            'kondisi_lahan_id' => $data['kondisi_lahan_id'] ?? null,
            'program_pemupukan_id' => $data['program_pemupukan_id'] ?? null,
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
            // Pahan v2.5: komponen baru fingerprint
            'luas_ha_snapshot' => $data['luas_ha_snapshot'] ?? null,
            'sph_snapshot' => $data['sph_snapshot'] ?? null,
            'jumlah_pokok_snapshot' => $data['jumlah_pokok_snapshot'] ?? null,
            'urea_total_estimasi_tahunan' => $data['urea_total_estimasi_tahunan'] ?? null,
            'kcl_total_estimasi_tahunan' => $data['kcl_total_estimasi_tahunan'] ?? null,
            'urea_aplikasi_saat_ini' => $data['urea_aplikasi_saat_ini'] ?? null,
            'kcl_aplikasi_saat_ini' => $data['kcl_aplikasi_saat_ini'] ?? null,
            'urea_sisa_tahunan' => $data['urea_sisa_tahunan'] ?? null,
            'kcl_sisa_tahunan' => $data['kcl_sisa_tahunan'] ?? null,
            'active_stage' => $data['active_stage'] ?? null,
            'status_stage' => $data['status_stage'] ?? null,
        ];

        // JSON encode dengan key sorting agar hasilnya deterministik
        ksort($fingerprintData);
        $json = json_encode($fingerprintData, JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json);
    }

    /**
     * Susun hasil analisis dari rule-rule yang terpicu.
     */
    private function susunHasil(BlokLahan $blok, KondisiLahan $kondisi, array $rules, array $plantContext): array
    {
        // Tentukan status dominan (legacy — hanya untuk kompatibilitas)
        // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
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
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $plantContext);

        // Evaluasi kelayakan waktu
        $window = $dosisRef['window'];

        // Status kondisi tanaman (HANYA dari DIAGNOSIS_VISUAL)
        $statusKondisiTanaman = $this->tentukanStatusKondisiTanaman($rules, $kondisi);

        // Status kelayakan aplikasi (HANYA dari FertilizationWindowService)
        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::PERLU_VERIFIKASI_DATA;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : 'Data penentuan waktu pemupukan belum tersedia.';

        // Build annual snapshot
        $doseReference = $dosisRef['dose_reference'] ?? null;
        $isApplicable = $window ? $window['layak'] : false;
        $annualSnapshot = $this->snapshotBuilder->build($blok, $doseReference ?? ['urea' => ['estimate' => null], 'kcl' => ['estimate' => null]], $isApplicable);

        // Pahan v2.5: Hitung aplikasi saat ini via CurrentApplicationCalculator
        // Pahan v2.8: Gunakan program-based realization summary
        $tahunProgram = now()->year;
        $program = $this->programService->getActiveProgram($blok, $tahunProgram);
        $realizationSummary = $program
            ? $this->realizationService->getRealizationSummaryForProgram($program)
            : $this->realizationService->getRealizationSummary($blok);
        $currentApp = $this->currentAppCalculator->calculate([
            'annual_snapshot' => $annualSnapshot,
            'window_result' => $window ?? ['layak' => false],
            'realization_summary' => $realizationSummary,
            'analysis_date' => now(),
        ]);

        // Override aplikasi saat ini dari CurrentApplicationCalculator
        $annualSnapshot['urea_aplikasi_saat_ini'] = $currentApp['urea_aplikasi_saat_ini'];
        $annualSnapshot['kcl_aplikasi_saat_ini'] = $currentApp['kcl_aplikasi_saat_ini'];

        // Catatan dosis (v2.5: berdasarkan kelayakan, bukan status legacy)
        $catatanDosis = $this->tentukanCatatanDosis(
            $statusKelayakan,
            $window ? $window['alasan'] : [],
            $statusKondisiTanaman,
            $masalah
        );

        // Jadwal pemupukan via FertilizationScheduleService (v2.5: menggunakan aplikasi saat ini dari CurrentApplicationCalculator)
        $jadwal = $this->scheduleService->generate(
            ['dosis_urea' => $dosisRef['dosis_urea'] ?? 0, 'dosis_kcl' => $dosisRef['dosis_kcl'] ?? 0, 'total_urea' => $currentApp['urea_aplikasi_saat_ini'], 'total_kcl' => $currentApp['kcl_aplikasi_saat_ini'], 'active_stage' => $currentApp['active_stage'], 'status_stage' => $currentApp['status_stage'], 'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'] ?? null],
            $kondisi,
            $blok,
            $window ?? ['layak' => false, 'alasan' => ['Data penentuan waktu pemupukan belum tersedia']],
            $plantContext
        );

        // Sanitasi pupuk pendukung
        $pupukSanitized = $this->sanitizer->sanitize($rules);

        // Completeness
        $completeness = $this->completenessService->evaluate($kondisi);

        // Validitas
        $validitas = $completeness['can_run_diagnosis'] ? 'Cukup Kuat' : 'Estimasi Visual';
        $catatanValiditas = $completeness['reason'];

        // Kelengkapan data pendukung
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, $rules);

        // Dasar perhitungan (snapshot transparan)
        $dasarPerhitungan = [
            'dose_reference' => $dosisRef['dose_reference'] ?? null,
            'calculation' => $dosisRef['calculation'] ?? null,
            'strategy' => config('fertilization.reference_dose_strategy'),
            'catatan' => 'Dosis berdasarkan acuan Iyung Pahan (2013). Penyesuaian tanah, topografi, dan waktu tidak digunakan.',
        ];

        // Simpan dengan histori
        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => collect($rules)->map(fn ($r) => [
                'rule_id' => $r->id,
                'kode_rule' => $r->kode_rule,
                'jenis_rule' => $r->jenis_rule,
                'indikasi' => $r->indikasi_masalah,
                'pupuk' => $r->jenis_pupuk_utama,
                'status' => $r->status_kebutuhan,
                'prioritas' => $r->prioritas,
                'sumber_judul' => $r->sumber_judul,
                'sumber_penulis' => $r->sumber_penulis,
                'sumber_tahun' => $r->sumber_tahun,
                'sumber_halaman' => $r->sumber_halaman,
                'status_validasi' => $r->status_validasi,
            ])->toArray(),
            'masalah_teridentifikasi' => $masalah,
            'rekomendasi_pupuk' => $pupukSanitized,
            'saran_tindakan_utama' => $saranUtama,
            'status_kebutuhan_dominan' => $statusDominan, // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
            'jumlah_rule_terpicu' => count($rules),
            // Kolom lama — kompatibilitas
            'dosis_urea' => $isApplicable ? ($dosisRef['dosis_urea'] ?? 0) : 0.0,
            'dosis_kcl' => $isApplicable ? ($dosisRef['dosis_kcl'] ?? 0) : 0.0,
            'total_urea' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'total_kcl' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            'catatan_dosis' => $catatanDosis,
            'jadwal_pemupukan' => $jadwal,
            'validitas_rekomendasi' => $validitas,
            'catatan_validitas' => $catatanValiditas,
            // Nilai kelengkapan data pendukung
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Kelengkapan data pendukung: '.$reliability['kategori'].'. '.implode(' ', array_slice($reliability['saran_peningkatan'], 0, 2)),
            'data_cukup' => $completeness['can_run_diagnosis'],
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.4 — fase historis via PlantContextService
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'],
            'dasar_perhitungan_json' => $dasarPerhitungan,
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => $statusKondisiTanaman,
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan' => $alasanKelayakan,
            // Annual snapshot fields
            'urea_total_min_tahunan' => $annualSnapshot['urea_total_min_tahunan'],
            'urea_total_max_tahunan' => $annualSnapshot['urea_total_max_tahunan'],
            'urea_total_estimasi_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_total_min_tahunan' => $annualSnapshot['kcl_total_min_tahunan'],
            'kcl_total_max_tahunan' => $annualSnapshot['kcl_total_max_tahunan'],
            'kcl_total_estimasi_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'urea_karung_estimasi_tahunan' => $annualSnapshot['urea_karung_estimasi_tahunan'],
            'kcl_karung_estimasi_tahunan' => $annualSnapshot['kcl_karung_estimasi_tahunan'],
            'urea_aplikasi_saat_ini' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'kcl_aplikasi_saat_ini' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            // Pahan v2.5: snapshot luas/SPH dan tahap aktif
            'luas_ha_snapshot' => $annualSnapshot['luas_ha_snapshot'] ?? $blok->luas_ha,
            'sph_snapshot' => $annualSnapshot['sph_snapshot'] ?? $blok->sph,
            'active_stage' => $currentApp['active_stage'],
            'status_stage' => $currentApp['status_stage'],
            'urea_sisa_tahunan' => $currentApp['urea_sisa_tahunan'],
            'kcl_sisa_tahunan' => $currentApp['kcl_sisa_tahunan'],
            'tanggal_minimum_tahap_berikutnya' => $currentApp['tanggal_minimum_tahap_berikutnya'],
            'alasan_tahap' => $currentApp['reason'],
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.9'),
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
    private function tentukanStatusKondisiTanaman(array $rules, ?KondisiLahan $kondisi = null): string
    {
        if (empty($rules)) {
            return $kondisi?->warna_daun === config('observation.normal_leaf_condition', 'Hijau Normal')
                ? PlantConditionStatus::NORMAL_VISUAL->value
                : PlantConditionStatus::PERLU_VERIFIKASI->value;
        }

        // Filter hanya rule DIAGNOSIS_VISUAL
        $diagnosisRules = collect($rules)->filter(function ($rule) {
            return $rule->jenis_rule === 'DIAGNOSIS_VISUAL';
        });

        if ($diagnosisRules->isEmpty()) {
            return $kondisi?->warna_daun === config('observation.normal_leaf_condition', 'Hijau Normal')
                ? PlantConditionStatus::NORMAL_VISUAL->value
                : PlantConditionStatus::PERLU_VERIFIKASI->value;
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
     * Cek apakah data kondisi cukup minimal untuk dianalisis (minimal 1 field terisi).
     * Ini adalah pengecekan paling awal sebelum diagnosis.
     */
    private function kondisiCukupMinimal(KondisiLahan $kondisi): bool
    {
        return $kondisi->warna_daun !== null
            || $kondisi->kelembaban_tanah !== null
            || $kondisi->musim_saat_ini !== null
            || $kondisi->kondisi_drainase !== null
            || $kondisi->ada_serangan_hama === true
            || $kondisi->curah_hujan_kategori !== null
            || $kondisi->curah_hujan_mm_bulanan !== null
            || $kondisi->ada_gulma_dominan === true;
    }

    /**
     * Return hasil ketika umur tepat 3 tahun dan fase belum diverifikasi.
     * PAHAN v2.4: Tidak menghasilkan dosis, tidak membuat jadwal.
     */
    private function hasilPerluVerifikasiFase(BlokLahan $blok, KondisiLahan $kondisi, array $plantContext): array
    {
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Fase tanaman perlu diverifikasi — umur tepat 3 tahun'],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Umur tanaman tepat 3 tahun. Verifikasi apakah tanaman sudah memasuki fase Tanaman Menghasilkan atau masih Tanaman Belum Menghasilkan sebelum rekomendasi dosis dapat dibuat.',
            'status_kebutuhan_dominan' => 'Normal', // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 0.0,
            'dosis_kcl' => 0.0,
            'total_urea' => 0.0,
            'total_kcl' => 0.0,
            'catatan_dosis' => 'Fase tanaman belum diverifikasi (umur tepat 3 tahun). Pilih fase Tanaman Belum Menghasilkan atau Tanaman Menghasilkan pada halaman edit blok lahan.',
            'jadwal_pemupukan' => [],
            'validitas_rekomendasi' => 'Estimasi Visual',
            'catatan_validitas' => 'Fase tanaman perlu diverifikasi sebelum analisis dapat dilanjutkan.',
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Kelengkapan data pendukung: '.$reliability['kategori'],
            'data_cukup' => false,
            'data_kurang' => ['Verifikasi fase tanaman'],
            'notifikasi_data' => 'Fase tanaman perlu diverifikasi.',
            'fase_tanaman_snapshot' => null,
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => null,
            'urea_max_kg_per_pokok_tahun' => null,
            'urea_estimasi_kg_per_pokok_tahun' => null,
            'kcl_min_kg_per_pokok_tahun' => null,
            'kcl_max_kg_per_pokok_tahun' => null,
            'kcl_estimasi_kg_per_pokok_tahun' => null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $blok->jumlah_pokok_aktual,
            'dasar_perhitungan_json' => ['catatan' => 'Fase belum diverifikasi, dosis tidak dapat ditentukan.'],
            'peringatan_json' => ['Umur tepat 3 tahun dan fase belum diverifikasi.'],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::PERLU_VERIFIKASI->value,
            'status_kelayakan_aplikasi' => ApplicationFeasibilityStatus::PERLU_VERIFIKASI_DATA->value,
            'alasan_kelayakan' => 'Fase tanaman perlu diperiksa sebelum waktu pemupukan dapat ditentukan.',
            'urea_total_min_tahunan' => null,
            'urea_total_max_tahunan' => null,
            'urea_total_estimasi_tahunan' => null,
            'kcl_total_min_tahunan' => null,
            'kcl_total_max_tahunan' => null,
            'kcl_total_estimasi_tahunan' => null,
            'urea_karung_estimasi_tahunan' => null,
            'kcl_karung_estimasi_tahunan' => null,
            'urea_aplikasi_saat_ini' => 0.0,
            'kcl_aplikasi_saat_ini' => 0.0,
            // Pahan v2.5: snapshot luas/SPH
            'luas_ha_snapshot' => $blok->luas_ha,
            'sph_snapshot' => $blok->sph,
            'active_stage' => 0,
            'status_stage' => null,
            'urea_sisa_tahunan' => null,
            'kcl_sisa_tahunan' => null,
            'tanggal_minimum_tahap_berikutnya' => null,
            'alasan_tahap' => 'Fase tanaman perlu diverifikasi sebelum tahap aktif dapat ditentukan.',
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.9'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Return hasil ketika data kondisi tidak cukup untuk analisis.
     */
    private function hasilDataTidakCukup(BlokLahan $blok, KondisiLahan $kondisi, array $plantContext): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $plantContext);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $doseReference = $dosisRef['dose_reference'] ?? null;
        $completeness = $this->completenessService->evaluate($kondisi);

        // Build annual snapshot (kebutuhan tahunan tetap tersimpan)
        $isApplicable = false; // Data tidak cukup → tidak layak diaplikasikan
        $annualSnapshot = $this->snapshotBuilder->build($blok, $doseReference ?? ['urea' => ['estimate' => null], 'kcl' => ['estimate' => null]], $isApplicable);

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Data kondisi lahan belum lengkap untuk analisis'],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Pupuk Standar Rutin', 'dosis' => 'Sesuai jadwal pemupukan reguler — lengkapi data kondisi untuk rekomendasi spesifik']],
            'saran_tindakan_utama' => 'Data observasi kondisi lahan belum cukup untuk memberikan rekomendasi spesifik. Silakan lengkapi kondisi daun dan data kesiapan pemupukan, lalu jalankan analisis ulang.',
            'status_kebutuhan_dominan' => 'Normal', // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 0.0,
            'dosis_kcl' => 0.0,
            'total_urea' => 0.0,
            'total_kcl' => 0.0,
            'catatan_dosis' => 'Data observasi belum cukup untuk menghasilkan jadwal operasional. Kebutuhan tahunan tetap tercatat. Lengkapi data kondisi lahan untuk rekomendasi lebih akurat.',
            'jadwal_pemupukan' => [],
            'validitas_rekomendasi' => 'Estimasi Visual',
            'catatan_validitas' => 'Data observasi tidak lengkap — rekomendasi bersifat estimasi.',
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Kelengkapan data pendukung: '.$reliability['kategori'],
            'data_cukup' => false,
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.4 — menggunakan plantContext
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'],
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Data tidak cukup untuk analisis detail. Kebutuhan tahunan tersimpan.'],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::BELUM_DIOBSERVASI->value,
            'status_kelayakan_aplikasi' => ApplicationFeasibilityStatus::PERLU_VERIFIKASI_DATA->value,
            'alasan_kelayakan' => 'Data kondisi belum lengkap untuk menentukan waktu pemupukan.',
            // Annual snapshot fields
            'urea_total_min_tahunan' => $annualSnapshot['urea_total_min_tahunan'],
            'urea_total_max_tahunan' => $annualSnapshot['urea_total_max_tahunan'],
            'urea_total_estimasi_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_total_min_tahunan' => $annualSnapshot['kcl_total_min_tahunan'],
            'kcl_total_max_tahunan' => $annualSnapshot['kcl_total_max_tahunan'],
            'kcl_total_estimasi_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'urea_karung_estimasi_tahunan' => $annualSnapshot['urea_karung_estimasi_tahunan'],
            'kcl_karung_estimasi_tahunan' => $annualSnapshot['kcl_karung_estimasi_tahunan'],
            'urea_aplikasi_saat_ini' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'kcl_aplikasi_saat_ini' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            // Pahan v2.5: snapshot luas/SPH
            'luas_ha_snapshot' => $annualSnapshot['luas_ha_snapshot'] ?? $blok->luas_ha,
            'sph_snapshot' => $annualSnapshot['sph_snapshot'] ?? $blok->sph,
            'active_stage' => 0,
            'status_stage' => null,
            'urea_sisa_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_sisa_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'tanggal_minimum_tahap_berikutnya' => null,
            'alasan_tahap' => 'Data kondisi belum lengkap untuk menentukan tahap aktif.',
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.9'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Return hasil ketika data cukup untuk dosis dasar tapi TIDAK cukup untuk diagnosis RBS.
     * Kebutuhan tahunan tetap dihitung, tapi tidak ada diagnosis spesifik.
     */
    private function hasilDosisDasarTanpaDiagnosis(BlokLahan $blok, KondisiLahan $kondisi, array $completeness, array $plantContext): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $plantContext);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);
        $doseReference = $dosisRef['dose_reference'] ?? null;

        // Build annual snapshot
        $isApplicable = false; // Data tidak cukup untuk diagnosis → tidak layak diaplikasikan
        $annualSnapshot = $this->snapshotBuilder->build($blok, $doseReference ?? ['urea' => ['estimate' => null], 'kcl' => ['estimate' => null]], $isApplicable);

        $isUnmatchedLeaf = (bool) ($completeness['unmatched_leaf'] ?? false);
        $missingFields = $completeness['missing_fields'] ?? [];
        $saranLengkapi = $completeness['reason'];
        if (! $isUnmatchedLeaf && $missingFields !== []) {
            $saranLengkapi .= ' Lengkapi: '.implode(', ', $missingFields).'.';
        }
        $reviewIssue = $isUnmatchedLeaf
            ? 'Gejala daun perlu pemeriksaan lapangan lanjutan'
            : 'Data belum cukup untuk pemeriksaan gejala';
        $reviewDoseNote = $isUnmatchedLeaf
            ? 'Kebutuhan tahunan tetap dihitung dari acuan Iyung Pahan (2013), tetapi pemupukan belum dijadwalkan sampai gejala dikonfirmasi.'
            : 'Kebutuhan tahunan tetap dihitung dari acuan Iyung Pahan (2013); lengkapi data untuk mendapatkan jadwal operasional.';

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [$reviewIssue],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Urea dan KCl berdasarkan umur/fase', 'dosis' => $reviewDoseNote]],
            'saran_tindakan_utama' => $saranLengkapi,
            'status_kebutuhan_dominan' => 'Normal', // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 0.0,
            'dosis_kcl' => 0.0,
            'total_urea' => 0.0,
            'total_kcl' => 0.0,
            'catatan_dosis' => $reviewDoseNote,
            'jadwal_pemupukan' => [],
            'validitas_rekomendasi' => 'Estimasi Visual',
            'catatan_validitas' => $completeness['reason'],
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Kelengkapan data pendukung: '.$reliability['kategori'],
            'data_cukup' => false,
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.4 — menggunakan plantContext
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'],
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => $reviewDoseNote],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::PERLU_VERIFIKASI->value,
            'status_kelayakan_aplikasi' => ApplicationFeasibilityStatus::PERLU_VERIFIKASI_DATA->value,
            'alasan_kelayakan' => $completeness['reason'],
            // Annual snapshot fields
            'urea_total_min_tahunan' => $annualSnapshot['urea_total_min_tahunan'],
            'urea_total_max_tahunan' => $annualSnapshot['urea_total_max_tahunan'],
            'urea_total_estimasi_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_total_min_tahunan' => $annualSnapshot['kcl_total_min_tahunan'],
            'kcl_total_max_tahunan' => $annualSnapshot['kcl_total_max_tahunan'],
            'kcl_total_estimasi_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'urea_karung_estimasi_tahunan' => $annualSnapshot['urea_karung_estimasi_tahunan'],
            'kcl_karung_estimasi_tahunan' => $annualSnapshot['kcl_karung_estimasi_tahunan'],
            'urea_aplikasi_saat_ini' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'kcl_aplikasi_saat_ini' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            // Pahan v2.5: snapshot luas/SPH
            'luas_ha_snapshot' => $annualSnapshot['luas_ha_snapshot'] ?? $blok->luas_ha,
            'sph_snapshot' => $annualSnapshot['sph_snapshot'] ?? $blok->sph,
            'active_stage' => 0,
            'status_stage' => null,
            'urea_sisa_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_sisa_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'tanggal_minimum_tahap_berikutnya' => null,
            'alasan_tahap' => $isUnmatchedLeaf
                ? 'Gejala daun perlu dikonfirmasi sebelum tahap pemupukan ditentukan.'
                : 'Data observasi belum cukup untuk pemeriksaan gejala; tahap belum ditentukan.',
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.9'),
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
    private function hasilNormal(BlokLahan $blok, KondisiLahan $kondisi, array $plantContext): array
    {
        $dosisRef = $this->hitungDosisStandar($blok, $kondisi, $plantContext);
        $window = $dosisRef['window'];
        $doseReference = $dosisRef['dose_reference'] ?? null;

        // Build annual snapshot
        $isApplicable = $window ? $window['layak'] : true;
        $annualSnapshot = $this->snapshotBuilder->build($blok, $doseReference ?? ['urea' => ['estimate' => null], 'kcl' => ['estimate' => null]], $isApplicable);

        // Pahan v2.5: Hitung aplikasi saat ini via CurrentApplicationCalculator
        // Pahan v2.8: Gunakan program-based realization summary
        $tahunProgram = now()->year;
        $programNormal = $this->programService->getActiveProgram($blok, $tahunProgram);
        $realizationSummary = $programNormal
            ? $this->realizationService->getRealizationSummaryForProgram($programNormal)
            : $this->realizationService->getRealizationSummary($blok);
        $currentApp = $this->currentAppCalculator->calculate([
            'annual_snapshot' => $annualSnapshot,
            'window_result' => $window ?? ['layak' => true],
            'realization_summary' => $realizationSummary,
            'analysis_date' => now(),
        ]);
        $annualSnapshot['urea_aplikasi_saat_ini'] = $currentApp['urea_aplikasi_saat_ini'];
        $annualSnapshot['kcl_aplikasi_saat_ini'] = $currentApp['kcl_aplikasi_saat_ini'];

        // Jadwal via FertilizationScheduleService (v2.5: menggunakan currentApp)
        $jadwal = $this->scheduleService->generate(
            ['dosis_urea' => $dosisRef['dosis_urea'] ?? 0, 'dosis_kcl' => $dosisRef['dosis_kcl'] ?? 0, 'total_urea' => $currentApp['urea_aplikasi_saat_ini'], 'total_kcl' => $currentApp['kcl_aplikasi_saat_ini'], 'active_stage' => $currentApp['active_stage'], 'status_stage' => $currentApp['status_stage'], 'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'] ?? null],
            $kondisi,
            $blok,
            $window ?? ['layak' => true, 'alasan' => []],
            $plantContext
        );

        $completeness = $this->completenessService->evaluate($kondisi);
        $reliability = $this->reliabilityService->calculate($blok, $kondisi, []);

        $statusKelayakan = $window ? $window['status'] : FertilizationWindowService::LAYAK;
        $alasanKelayakan = $window ? implode(' ', $window['alasan']) : '';

        // Catatan dosis (v2.4: berdasarkan kelayakan, bukan status legacy)
        $catatanDosis = $this->tentukanCatatanDosis(
            $statusKelayakan,
            $window ? $window['alasan'] : [],
            PlantConditionStatus::NORMAL_VISUAL->value,
            []
        );

        $hasil = $this->simpanDenganHistori($blok->id, [
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => Auth::guard('admin')->id(),
            'tanggal_analisis' => now()->toDateString(),
            'rules_terpicu' => [],
            // Kondisi normal bukan sebuah "temuan masalah".
            // Simpan array kosong agar jumlah temuan tetap bermakna.
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [['jenis_utama' => 'Urea dan KCl berdasarkan umur/fase', 'dosis' => 'Gunakan hasil perhitungan kebutuhan tahunan dari Iyung Pahan (2013)']],
            'saran_tindakan_utama' => 'Tidak ditemukan gejala daun yang sesuai dengan aturan aktif. Hasil ini bukan bukti bahwa seluruh kebutuhan hara sudah cukup. Gunakan kebutuhan Urea dan KCl berdasarkan umur serta fase tanaman; lakukan analisis daun atau tanah bila diperlukan.',
            'status_kebutuhan_dominan' => 'Normal', // LEGACY ONLY — kompatibilitas histori, bukan keputusan operasional
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => $isApplicable ? ($dosisRef['dosis_urea'] ?? 0) : 0.0,
            'dosis_kcl' => $isApplicable ? ($dosisRef['dosis_kcl'] ?? 0) : 0.0,
            'total_urea' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'total_kcl' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            'catatan_dosis' => $catatanDosis,
            'jadwal_pemupukan' => $jadwal,
            'validitas_rekomendasi' => $completeness['can_run_diagnosis'] ? 'Cukup Kuat' : 'Estimasi Visual',
            'catatan_validitas' => $completeness['reason'],
            'confidence_score' => $reliability['score'],
            'confidence_label' => $this->mapReliabilityToLabel($reliability['score']),
            'catatan_confidence' => 'Kelengkapan data pendukung: '.$reliability['kategori'],
            'data_cukup' => $completeness['can_run_diagnosis'],
            'data_kurang' => $completeness['missing_fields'],
            'notifikasi_data' => $completeness['reason'],
            // Kolom Pahan-v2.4
            'fase_tanaman_snapshot' => $plantContext['fase'],
            'umur_tanaman_snapshot' => $plantContext['umur'],
            'urea_min_kg_per_pokok_tahun' => $doseReference['urea']['min'] ?? null,
            'urea_max_kg_per_pokok_tahun' => $doseReference['urea']['max'] ?? null,
            'urea_estimasi_kg_per_pokok_tahun' => $doseReference['urea']['estimate'] ?? null,
            'kcl_min_kg_per_pokok_tahun' => $doseReference['kcl']['min'] ?? null,
            'kcl_max_kg_per_pokok_tahun' => $doseReference['kcl']['max'] ?? null,
            'kcl_estimasi_kg_per_pokok_tahun' => $doseReference['kcl']['estimate'] ?? null,
            'strategi_estimasi_dosis' => config('fertilization.reference_dose_strategy'),
            'jumlah_pokok_snapshot' => $annualSnapshot['jumlah_pokok'],
            'dasar_perhitungan_json' => ['strategy' => config('fertilization.reference_dose_strategy'), 'catatan' => 'Tidak ditemukan gejala daun yang sesuai dengan aturan aktif; dosis tetap mengacu pada Iyung Pahan (2013).'],
            'peringatan_json' => $dosisRef['peringatan'] ?? [],
            'kelengkapan_data_score' => $reliability['score'],
            'kategori_keandalan' => $reliability['kategori'],
            'rincian_skor_json' => $reliability['rincian'],
            'status_kondisi_tanaman' => PlantConditionStatus::NORMAL_VISUAL->value,
            'status_kelayakan_aplikasi' => $statusKelayakan,
            'alasan_kelayakan' => $alasanKelayakan,
            // Annual snapshot fields
            'urea_total_min_tahunan' => $annualSnapshot['urea_total_min_tahunan'],
            'urea_total_max_tahunan' => $annualSnapshot['urea_total_max_tahunan'],
            'urea_total_estimasi_tahunan' => $annualSnapshot['urea_total_estimasi_tahunan'],
            'kcl_total_min_tahunan' => $annualSnapshot['kcl_total_min_tahunan'],
            'kcl_total_max_tahunan' => $annualSnapshot['kcl_total_max_tahunan'],
            'kcl_total_estimasi_tahunan' => $annualSnapshot['kcl_total_estimasi_tahunan'],
            'urea_karung_estimasi_tahunan' => $annualSnapshot['urea_karung_estimasi_tahunan'],
            'kcl_karung_estimasi_tahunan' => $annualSnapshot['kcl_karung_estimasi_tahunan'],
            'urea_aplikasi_saat_ini' => $annualSnapshot['urea_aplikasi_saat_ini'],
            'kcl_aplikasi_saat_ini' => $annualSnapshot['kcl_aplikasi_saat_ini'],
            // Pahan v2.5: snapshot luas/SPH dan tahap aktif
            'luas_ha_snapshot' => $annualSnapshot['luas_ha_snapshot'] ?? $blok->luas_ha,
            'sph_snapshot' => $annualSnapshot['sph_snapshot'] ?? $blok->sph,
            'active_stage' => $currentApp['active_stage'],
            'status_stage' => $currentApp['status_stage'],
            'urea_sisa_tahunan' => $currentApp['urea_sisa_tahunan'],
            'kcl_sisa_tahunan' => $currentApp['kcl_sisa_tahunan'],
            'tanggal_minimum_tahap_berikutnya' => $currentApp['tanggal_minimum_tahap_berikutnya'],
            'alasan_tahap' => $currentApp['reason'],
            'metode_perhitungan_umur' => $plantContext['metode_perhitungan_umur'],
            'tanggal_referensi_umur' => $plantContext['tanggal_referensi'],
            'versi_mesin_rekomendasi' => config('fertilization.engine_version', 'pahan-v2.9'),
        ]);

        return ['sukses' => true, 'rekomendasi' => $hasil];
    }

    /**
     * Tentukan catatan operasional berdasarkan kelayakan dan kondisi.
     *
     * Pahan v2.4: Catatan hanya mengikuti status_kelayakan_aplikasi dan alasan_kelayakan.
     * BUKAN status_kebutuhan_dominan (legacy).
     *
     * @param  string  $statusKelayakan  Status kelayakan aplikasi (dari FertilizationWindowService)
     * @param  array  $alasanKelayakan  Alasan kelayakan
     * @param  string  $statusKondisi  Status kondisi tanaman
     * @param  array  $masalah  Masalah teridentifikasi
     */
    private function tentukanCatatanDosis(
        string $statusKelayakan,
        array $alasanKelayakan,
        string $statusKondisi,
        array $masalah
    ): string {
        $alasanStr = implode(' ', $alasanKelayakan);

        // Jika tidak layak: catatan mengikuti alasan kelayakan
        if ($statusKelayakan !== FertilizationWindowService::LAYAK && $statusKelayakan !== FertilizationWindowService::TERLAMBAT) {
            if ($statusKelayakan === FertilizationWindowService::PERLU_PERBAIKAN_DRAINASE) {
                return 'Blok belum siap dipupuk karena lahan tergenang. Perbaiki drainase terlebih dahulu. Kebutuhan tahunan tetap tercatat.';
            }
            if ($statusKelayakan === FertilizationWindowService::TUNDA_HUJAN_RENDAH) {
                return 'Blok belum siap dipupuk karena curah hujan rendah. Tunggu curah hujan dalam rentang 100-250 mm/bulan. Kebutuhan tahunan tetap tercatat.';
            }
            if ($statusKelayakan === FertilizationWindowService::TUNDA_TANAH_KERING) {
                return 'Blok belum siap dipupuk karena tanah sangat kering. Tunggu kelembapan tanah memadai sebelum aplikasi. Kebutuhan tahunan tetap tercatat.';
            }
            if ($statusKelayakan === FertilizationWindowService::TUNDA_HUJAN_TINGGI) {
                return 'Blok belum siap dipupuk karena curah hujan tinggi. Risiko pencucian hara. Kebutuhan tahunan tetap tercatat.';
            }
            if ($statusKelayakan === FertilizationWindowService::TUNDA_INTERVAL) {
                return 'Blok belum siap dipupuk karena jarak waktu terlalu pendek. Tunggu minimal '.config('fertilization.window.min_interval_days', 120).' hari antar aplikasi. Kebutuhan tahunan tetap tercatat.';
            }

            return 'Blok belum siap dipupuk sampai kondisi lapangan memenuhi syarat. Alasan: '.$alasanStr.'. Kebutuhan tahunan tetap tercatat.';
        }

        // Jika layak: catatan mengikuti kondisi
        if ($statusKondisi === PlantConditionStatus::GEJALA_BERAT->value) {
            return 'Gejala berat ditemukan, tetapi kondisi lapangan sudah memenuhi syarat pemupukan. Aplikasikan dosis estimasi kerja bersamaan dengan penanganan masalah utama.';
        }

        if ($statusKondisi === PlantConditionStatus::TERINDIKASI_DEFISIENSI->value) {
            return 'Terindikasi defisiensi. Segera aplikasikan dosis estimasi kerja dari acuan Iyung Pahan (2013).';
        }

        if ($statusKelayakan === FertilizationWindowService::TERLAMBAT) {
            return 'Pemupukan terlambat. Segera jadwalkan aplikasi dosis estimasi kerja.';
        }

        return 'Kondisi lahan normal. Estimasi dosis kerja dari acuan Iyung Pahan (2013) dapat diaplikasikan sesuai jadwal.';
    }

    /**
     * Hitung dosis standar Urea & KCl berdasarkan referensi Pahan 2013.
     *
     * PERUBAHAN PAHAN-V2.4:
     * - Dosis diambil berdasarkan umur dan fase DARI plantContext (historis)
     * - TIDAK memanggil PlantPhaseResolver::resolve($blok) untuk analisis historis
     * - Multiplier tanah/topografi/waktu DINONAKTIFKAN
     *
     * @param  array  $plantContext  Output dari PlantContextService::resolve()
     */
    private function hitungDosisStandar(BlokLahan $blok, ?KondisiLahan $kondisi, array $plantContext): array
    {
        $umur = $plantContext['umur'] ?? null;
        $fase = $plantContext['fase'] ?? null;

        // Jika umur tepat 3 dan fase belum diverifikasi → tidak bisa menentukan dosis
        if ($umur === 3 && $fase === null && ($plantContext['needs_phase_verification'] ?? false)) {
            return [
                'dosis_urea' => null,
                'dosis_kcl' => null,
                'total_urea' => null,
                'total_kcl' => null,
                'dose_reference' => $this->doseService->getDoseReferenceForContext($blok, $umur, 'TBM'),
                'calculation' => null,
                'window' => null,
                'peringatan' => ['Umur tepat 3 tahun dan fase belum diverifikasi — tidak dapat menentukan kelompok dosis.'],
                'status_verifikasi_fase' => 'PERLU_VERIFIKASI_FASE',
            ];
        }

        // Gunakan langsung plantContext untuk dosis
        if ($umur !== null && $fase !== null) {
            $doseRef = $this->doseService->getDoseReferenceForContext($blok, $umur, $fase);
        } else {
            // Fallback: gunakan blok saat ini (seharusnya tidak terjadi dalam alur normal)
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
