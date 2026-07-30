<?php

namespace Tests\Unit;

use App\Models\KondisiLahan;
use App\Services\FertilizationWindowService;
use Tests\TestCase;

class RainfallFallbackTest extends TestCase
{
    private FertilizationWindowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FertilizationWindowService;
    }

    private function makeKondisi(?float $millimeters = null, ?string $category = null): KondisiLahan
    {
        $kondisi = new KondisiLahan;
        $kondisi->curah_hujan_mm_bulanan = $millimeters;
        $kondisi->curah_hujan_kategori = $category;

        return $kondisi;
    }

    public function test_99mm_perlu_verifikasi(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(99));
        $this->assertFalse($result['layak']);
        $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }

    public function test_100mm_dan_250mm_layak(): void
    {
        foreach ([100, 250] as $rainfall) {
            $result = $this->service->evaluate($this->makeKondisi($rainfall));
            $this->assertTrue($result['layak']);
        }
    }

    public function test_251mm_perlu_verifikasi(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(251));
        $this->assertFalse($result['layak']);
        $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }

    public function test_kategori_rendah_dan_normal_tanpa_angka_perlu_verifikasi(): void
    {
        foreach (['Rendah', 'Normal'] as $category) {
            $result = $this->service->evaluate($this->makeKondisi(null, $category));
            $this->assertFalse($result['layak']);
            $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
        }
    }

    public function test_kategori_sangat_rendah_tunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(null, 'Sangat Rendah'));
        $this->assertSame(FertilizationWindowService::TUNDA_HUJAN_RENDAH, $result['status']);
    }

    public function test_kategori_sangat_tinggi_tunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(null, 'Sangat Tinggi'));
        $this->assertSame(FertilizationWindowService::TUNDA_HUJAN_TINGGI, $result['status']);
    }

    public function test_data_hujan_kosong_perlu_verifikasi(): void
    {
        $result = $this->service->evaluate($this->makeKondisi());
        $this->assertFalse($result['layak']);
        $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
    }
}
