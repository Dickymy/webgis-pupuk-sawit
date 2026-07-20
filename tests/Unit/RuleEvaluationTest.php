<?php

namespace Tests\Unit;

use App\Models\KondisiLahan;
use App\Models\RuleBaseLanjutan;
use App\Services\RbsService;
use Tests\TestCase;

/**
 * Test evaluasi rule RBS — memastikan logika AND ketat pada kondisi.
 */
class RuleEvaluationTest extends TestCase
{
    private RbsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RbsService::class);
    }

    /**
     * Helper: buat rule dengan atribut tertentu.
     */
    private function makeRule(array $attrs = []): RuleBaseLanjutan
    {
        $rule = new RuleBaseLanjutan;
        $rule->kondisi_warna_daun = $attrs['kondisi_warna_daun'] ?? null;
        $rule->kondisi_defisiensi = $attrs['kondisi_defisiensi'] ?? null;
        $rule->kondisi_ph_min = $attrs['kondisi_ph_min'] ?? null;
        $rule->kondisi_ph_max = $attrs['kondisi_ph_max'] ?? null;
        $rule->kondisi_kelembaban = $attrs['kondisi_kelembaban'] ?? null;
        $rule->kondisi_curah_hujan_kategori = $attrs['kondisi_curah_hujan_kategori'] ?? null;
        $rule->kondisi_musim = $attrs['kondisi_musim'] ?? null;
        $rule->kondisi_drainase = $attrs['kondisi_drainase'] ?? null;
        $rule->kondisi_kategori_umur = $attrs['kondisi_kategori_umur'] ?? null;
        $rule->kondisi_pelepah = $attrs['kondisi_pelepah'] ?? null;
        $rule->kondisi_tandan = $attrs['kondisi_tandan'] ?? null;
        $rule->ada_serangan_hama = $attrs['ada_serangan_hama'] ?? null;
        $rule->ada_gulma_dominan = $attrs['ada_gulma_dominan'] ?? null;
        $rule->kondisi_intermediate = $attrs['kondisi_intermediate'] ?? null;
        $rule->prasyarat_intermediate = $attrs['prasyarat_intermediate'] ?? null;

        return $rule;
    }

    /**
     * Helper: buat kondisi lahan.
     */
    private function makeKondisi(array $attrs = []): KondisiLahan
    {
        $kondisi = new KondisiLahan;
        $kondisi->warna_daun = $attrs['warna_daun'] ?? null;
        $kondisi->gejala_defisiensi = $attrs['gejala_defisiensi'] ?? null;
        $kondisi->ph_tanah = $attrs['ph_tanah'] ?? null;
        $kondisi->kelembaban_tanah = $attrs['kelembaban_tanah'] ?? null;
        $kondisi->curah_hujan_kategori = $attrs['curah_hujan_kategori'] ?? null;
        $kondisi->musim_saat_ini = $attrs['musim_saat_ini'] ?? null;
        $kondisi->kondisi_drainase = $attrs['kondisi_drainase'] ?? null;
        $kondisi->kondisi_pelepah = $attrs['kondisi_pelepah'] ?? null;
        $kondisi->kondisi_tandan = $attrs['kondisi_tandan'] ?? null;
        $kondisi->ada_serangan_hama = $attrs['ada_serangan_hama'] ?? false;
        $kondisi->ada_gulma_dominan = $attrs['ada_gulma_dominan'] ?? false;

        return $kondisi;
    }

    /**
     * Gunakan reflection untuk memanggil private method evaluasiRule.
     */
    private function callEvaluasiRule(RuleBaseLanjutan $rule, KondisiLahan $kondisi, ?string $kategoriUmur = null): bool
    {
        $reflection = new \ReflectionMethod($this->service, 'evaluasiRule');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->service, $rule, $kondisi, $kategoriUmur);
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. Rule warna + defisiensi TIDAK terpicu jika defisiensi kosong
    // ═══════════════════════════════════════════════════════════════

    public function test_rule_warna_dan_defisiensi_tidak_terpicu_jika_defisiensi_kosong(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => [], // kosong!
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result, 'Rule seharusnya TIDAK terpicu ketika defisiensi kosong');
    }

    public function test_rule_warna_dan_defisiensi_tidak_terpicu_jika_defisiensi_null(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => null, // null!
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result, 'Rule seharusnya TIDAK terpicu ketika defisiensi null');
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. Rule TIDAK terpicu jika salah satu syarat berbeda
    // ═══════════════════════════════════════════════════════════════

    public function test_rule_tidak_terpicu_jika_warna_daun_berbeda(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Hijau Normal', // berbeda!
            'gejala_defisiensi' => ['N'],
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result);
    }

    public function test_rule_tidak_terpicu_jika_defisiensi_berbeda(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => ['K'], // N bukan K
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result);
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. NULL pada rule berfungsi sebagai wildcard
    // ═══════════════════════════════════════════════════════════════

    public function test_null_pada_rule_berfungsi_sebagai_wildcard(): void
    {
        // Rule hanya mensyaratkan drainase buruk, tanpa syarat lain
        $rule = $this->makeRule([
            'kondisi_drainase' => 'Buruk — Tergenang',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Hijau Normal',
            'kondisi_drainase' => 'Buruk — Tergenang',
            'ph_tanah' => 5.5,
            'gejala_defisiensi' => ['K'],
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertTrue($result, 'Rule hanya drainase seharusnya terpicu apapun warna daun');
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. Rule dengan SEMUA kondisi null TIDAK boleh terpicu
    // ═══════════════════════════════════════════════════════════════

    public function test_rule_semua_kondisi_null_tidak_terpicu(): void
    {
        $rule = $this->makeRule([]); // semua null

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Hijau Normal',
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result, 'Rule tanpa kondisi sama sekali TIDAK boleh terpicu');
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. Array defisiensi menggunakan strict comparison
    // ═══════════════════════════════════════════════════════════════

    public function test_defisiensi_strict_comparison(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => ['N', 'K'],
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertTrue($result, 'N harus cocok strict di array');
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. Rule warna + defisiensi TERPICU jika SEMUA syarat cocok
    // ═══════════════════════════════════════════════════════════════

    public function test_rule_terpicu_jika_semua_syarat_cocok(): void
    {
        $rule = $this->makeRule([
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
        ]);

        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => ['N'],
        ]);

        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertTrue($result, 'Rule seharusnya terpicu ketika semua syarat cocok');
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. Rule pH range
    // ═══════════════════════════════════════════════════════════════

    public function test_rule_ph_range_cocok(): void
    {
        $rule = $this->makeRule([
            'kondisi_ph_min' => 3.0,
            'kondisi_ph_max' => 4.5,
        ]);

        $kondisi = $this->makeKondisi(['ph_tanah' => 4.0]);
        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertTrue($result);
    }

    public function test_rule_ph_range_di_luar(): void
    {
        $rule = $this->makeRule([
            'kondisi_ph_min' => 3.0,
            'kondisi_ph_max' => 4.5,
        ]);

        $kondisi = $this->makeKondisi(['ph_tanah' => 5.0]);
        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result);
    }

    public function test_rule_ph_null_gagal(): void
    {
        $rule = $this->makeRule([
            'kondisi_ph_min' => 3.0,
            'kondisi_ph_max' => 4.5,
        ]);

        $kondisi = $this->makeKondisi(['ph_tanah' => null]);
        $result = $this->callEvaluasiRule($rule, $kondisi);
        $this->assertFalse($result, 'Rule pH harus gagal jika pH input null');
    }
}
