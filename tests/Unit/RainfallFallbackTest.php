<?php

namespace Tests\Unit;

use App\Models\KondisiLahan;
use App\Services\FertilizationWindowService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Test fallback curah hujan — kategori tanpa numerik tidak langsung layak.
 */
class RainfallFallbackTest extends TestCase
{
    private FertilizationWindowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FertilizationWindowService;
    }

    private function makeKondisi(array $attrs = []): KondisiLahan
    {
        $kondisi = new KondisiLahan;
        $kondisi->curah_hujan_mm_bulanan = $attrs['curah_hujan_mm_bulanan'] ?? null;
        $kondisi->curah_hujan_kategori = $attrs['curah_hujan_kategori'] ?? null;
        $kondisi->tanggal_pemupukan_terakhir = isset($attrs['tanggal_pemupukan_terakhir'])
            ? Carbon::parse($attrs['tanggal_pemupukan_terakhir'])
            : null;
        $kondisi->kondisi_drainase = $attrs['kondisi_drainase'] ?? null;

        return $kondisi;
    }

    // ═══════════════════════════════════════════════════════════════
    // Numerik: batas ketat
    // ═══════════════════════════════════════════════════════════════

    public function test_99mm_tunda(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 99]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_RENDAH, $result['status']);
    }

    public function test_100mm_layak(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 100]);
        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['layak']);
    }

    public function test_250mm_layak(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 250]);
        $result = $this->service->evaluate($kondisi);
        $this->assertTrue($result['layak']);
    }

    public function test_251mm_tunda(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 251]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_TINGGI, $result['status']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Kategori tanpa angka → PERLU_VERIFIKASI_DATA
    // ═══════════════════════════════════════════════════════════════

    public function test_kategori_rendah_tanpa_angka_perlu_verifikasi(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_kategori' => 'Rendah',
            'curah_hujan_mm_bulanan' => null,
        ]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }

    public function test_kategori_normal_tanpa_angka_perlu_verifikasi(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_kategori' => 'Normal',
            'curah_hujan_mm_bulanan' => null,
        ]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }

    public function test_kategori_sangat_rendah_tunda(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_kategori' => 'Sangat Rendah',
            'curah_hujan_mm_bulanan' => null,
        ]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_RENDAH, $result['status']);
    }

    public function test_kategori_sangat_tinggi_tunda(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_kategori' => 'Sangat Tinggi',
            'curah_hujan_mm_bulanan' => null,
        ]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_TINGGI, $result['status']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Data kosong → PERLU_VERIFIKASI_DATA
    // ═══════════════════════════════════════════════════════════════

    public function test_data_hujan_kosong_perlu_verifikasi(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_kategori' => null,
            'curah_hujan_mm_bulanan' => null,
        ]);
        $result = $this->service->evaluate($kondisi);
        $this->assertFalse($result['layak']);
        $this->assertEquals(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }
}
