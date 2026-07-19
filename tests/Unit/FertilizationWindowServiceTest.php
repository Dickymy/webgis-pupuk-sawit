<?php

namespace Tests\Unit;

use App\Models\KondisiLahan;
use App\Services\FertilizationWindowService;
use Carbon\Carbon;
use Tests\TestCase;

class FertilizationWindowServiceTest extends TestCase
{
    private FertilizationWindowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FertilizationWindowService();
    }

    private function makeKondisi(array $attrs = []): KondisiLahan
    {
        $kondisi = new KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = $attrs['curah_hujan_mm_bulanan'] ?? null;
        $kondisi->curah_hujan_kategori = $attrs['curah_hujan_kategori'] ?? null;
        $kondisi->tanggal_pemupukan_terakhir = isset($attrs['tanggal_pemupukan_terakhir'])
            ? Carbon::parse($attrs['tanggal_pemupukan_terakhir'])
            : null;
        $kondisi->kondisi_drainase = $attrs['kondisi_drainase'] ?? null;
        return $kondisi;
    }

    // ═══════════════════════════════════════════════════════════════
    // Curah Hujan Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_hujan_99mm_ditunda(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 99]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_RENDAH, $result['status']);
        $this->assertFalse($result['layak']);
    }

    public function test_hujan_100mm_layak(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 100]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::LAYAK, $result['status']);
        $this->assertTrue($result['layak']);
    }

    public function test_hujan_250mm_layak(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 250]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::LAYAK, $result['status']);
        $this->assertTrue($result['layak']);
    }

    public function test_hujan_251mm_ditunda(): void
    {
        $kondisi = $this->makeKondisi(['curah_hujan_mm_bulanan' => 251]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::TUNDA_HUJAN_TINGGI, $result['status']);
        $this->assertFalse($result['layak']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Interval Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_interval_59_hari_ditunda(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'tanggal_pemupukan_terakhir' => now()->subDays(59)->toDateString(),
        ]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::TUNDA_INTERVAL, $result['status']);
        $this->assertFalse($result['layak']);
    }

    public function test_interval_60_hari_layak(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'tanggal_pemupukan_terakhir' => now()->subDays(60)->toDateString(),
        ]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::LAYAK, $result['status']);
        $this->assertTrue($result['layak']);
    }

    public function test_interval_130_hari_terlambat_tanpa_kenaikan_dosis(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'tanggal_pemupukan_terakhir' => now()->subDays(130)->toDateString(),
        ]);
        $result = $this->service->evaluate($kondisi);

        // Terlambat tapi tetap layak dijadwalkan
        $this->assertEquals(FertilizationWindowService::TERLAMBAT, $result['status']);
        $this->assertTrue($result['layak']);
        $this->assertTrue($result['terlambat']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Drainase Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_drainase_buruk_ditunda(): void
    {
        $kondisi = $this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'kondisi_drainase' => 'Buruk — Tergenang',
        ]);
        $result = $this->service->evaluate($kondisi);

        $this->assertEquals(FertilizationWindowService::PERLU_PERBAIKAN_DRAINASE, $result['status']);
        $this->assertFalse($result['layak']);
    }
}
