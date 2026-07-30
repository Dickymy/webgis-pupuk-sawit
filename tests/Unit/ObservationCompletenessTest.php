<?php

namespace Tests\Unit;

use App\Models\KondisiLahan;
use App\Services\ObservationCompletenessService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Test ObservationCompletenessService — syarat minimum diagnosis.
 */
class ObservationCompletenessTest extends TestCase
{
    private ObservationCompletenessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ObservationCompletenessService;
    }

    private function makeKondisi(array $attrs = []): KondisiLahan
    {
        $kondisi = new KondisiLahan;
        $kondisi->warna_daun = $attrs['warna_daun'] ?? null;
        $kondisi->kondisi_drainase = $attrs['kondisi_drainase'] ?? null;
        $kondisi->curah_hujan_mm_bulanan = $attrs['curah_hujan_mm_bulanan'] ?? null;
        $kondisi->curah_hujan_kategori = $attrs['curah_hujan_kategori'] ?? null;
        $kondisi->kelembaban_tanah = $attrs['kelembaban_tanah'] ?? null;
        $kondisi->musim_saat_ini = $attrs['musim_saat_ini'] ?? null;
        $kondisi->tanggal_pemupukan_terakhir = isset($attrs['tanggal_pemupukan_terakhir'])
            ? Carbon::parse($attrs['tanggal_pemupukan_terakhir'])
            : null;

        return $kondisi;
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. Satu field terisi → diagnosis TIDAK dijalankan
    // ═══════════════════════════════════════════════════════════════

    public function test_warna_daun_saja_cukup_untuk_rule_visual(): void
    {
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Hijau Normal',
        ]);

        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['can_run_diagnosis']);
        $this->assertTrue($result['can_calculate_base_dose']);
        $this->assertSame([], $result['blocking_missing_fields']);
        $this->assertEqualsCanonicalizing([
            'Kondisi drainase',
            'Data curah hujan',
            'Kelembaban tanah',
            'Tanggal pemupukan terakhir',
        ], $result['missing_fields']);
        $this->assertNotContains('Musim saat ini', $result['missing_fields']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. Data minimum terpenuhi → diagnosis dijalankan
    // ═══════════════════════════════════════════════════════════════

    public function test_data_minimum_terpenuhi_diagnosis_berjalan(): void
    {
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'kondisi_drainase' => 'Baik',
            'curah_hujan_mm_bulanan' => 150,
            'kelembaban_tanah' => 'Normal',
            'musim_saat_ini' => 'Musim Hujan',
        ]);

        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['can_run_diagnosis']);
        $this->assertEquals(5, $result['filled_count']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. Tanpa warna daun → diagnosis TIDAK bisa
    // ═══════════════════════════════════════════════════════════════

    public function test_tanpa_warna_daun_tidak_bisa_diagnosis(): void
    {
        $kondisi = $this->makeKondisi([
            'kondisi_drainase' => 'Baik',
            'curah_hujan_mm_bulanan' => 150,
            'kelembaban_tanah' => 'Normal',
            'musim_saat_ini' => 'Musim Hujan',
            'tanggal_pemupukan_terakhir' => '2026-01-01',
        ]);

        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['can_run_diagnosis']);
        $this->assertSame(['Warna daun'], $result['blocking_missing_fields']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. Drainase tidak menjadi syarat untuk menjalankan rule visual
    // ═══════════════════════════════════════════════════════════════

    public function test_tanpa_drainase_tetap_bisa_menjalankan_rule_visual(): void
    {
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'curah_hujan_mm_bulanan' => 150,
            'kelembaban_tanah' => 'Normal',
            'musim_saat_ini' => 'Musim Hujan',
            'tanggal_pemupukan_terakhir' => '2026-01-01',
        ]);

        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['can_run_diagnosis']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. Kondisi daun tetap menjadi fakta utama rule visual
    // ═══════════════════════════════════════════════════════════════

    public function test_kondisi_daun_tetap_cukup_tanpa_drainase(): void
    {
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'curah_hujan_mm_bulanan' => 150,
            'kelembaban_tanah' => 'Normal',
            'musim_saat_ini' => 'Musim Hujan',
        ]);

        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['can_run_diagnosis']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. Dosis dasar SELALU bisa dihitung (can_calculate_base_dose)
    // ═══════════════════════════════════════════════════════════════

    public function test_dosis_dasar_selalu_bisa_dihitung(): void
    {
        $kondisi = $this->makeKondisi([]); // kosong semua
        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['can_calculate_base_dose']);
    }
}
