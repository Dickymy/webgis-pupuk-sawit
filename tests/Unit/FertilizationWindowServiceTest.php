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
        $kondisi->kelembaban_tanah = $attrs['kelembaban_tanah'] ?? null;

        return $kondisi;
    }

    public function test_hujan_di_bawah_60_mm_ditunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(['curah_hujan_mm_bulanan' => 59]));

        $this->assertSame(FertilizationWindowService::TUNDA_HUJAN_RENDAH, $result['status']);
        $this->assertFalse($result['layak']);
    }

    public function test_hujan_60_sampai_99_mm_perlu_verifikasi(): void
    {
        foreach ([60, 99] as $rainfall) {
            $result = $this->service->evaluate($this->makeKondisi(['curah_hujan_mm_bulanan' => $rainfall]));
            $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
            $this->assertFalse($result['layak']);
        }
    }

    public function test_hujan_100_sampai_250_mm_layak(): void
    {
        foreach ([100, 250] as $rainfall) {
            $result = $this->service->evaluate($this->makeKondisi(['curah_hujan_mm_bulanan' => $rainfall]));
            $this->assertSame(FertilizationWindowService::LAYAK, $result['status']);
            $this->assertTrue($result['layak']);
        }
    }

    public function test_hujan_251_sampai_300_mm_perlu_verifikasi(): void
    {
        foreach ([251, 300] as $rainfall) {
            $result = $this->service->evaluate($this->makeKondisi(['curah_hujan_mm_bulanan' => $rainfall]));
            $this->assertSame(FertilizationWindowService::PERLU_VERIFIKASI_DATA, $result['status']);
            $this->assertFalse($result['layak']);
        }
    }

    public function test_hujan_di_atas_300_mm_ditunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi(['curah_hujan_mm_bulanan' => 301]));

        $this->assertSame(FertilizationWindowService::TUNDA_HUJAN_TINGGI, $result['status']);
        $this->assertFalse($result['layak']);
    }

    public function test_interval_119_hari_ditunda_dan_120_hari_layak(): void
    {
        $before = $this->service->evaluate($this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'tanggal_pemupukan_terakhir' => now()->subDays(119)->toDateString(),
        ]));
        $onBoundary = $this->service->evaluate($this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'tanggal_pemupukan_terakhir' => now()->subDays(120)->toDateString(),
        ]));

        $this->assertSame(FertilizationWindowService::TUNDA_INTERVAL, $before['status']);
        $this->assertFalse($before['layak']);
        $this->assertSame(FertilizationWindowService::LAYAK, $onBoundary['status']);
        $this->assertTrue($onBoundary['layak']);
    }

    public function test_tanah_sangat_kering_ditunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'kelembaban_tanah' => 'Sangat Kering',
        ]));

        $this->assertSame(FertilizationWindowService::TUNDA_TANAH_KERING, $result['status']);
        $this->assertFalse($result['layak']);
    }

    public function test_drainase_buruk_ditunda(): void
    {
        $result = $this->service->evaluate($this->makeKondisi([
            'curah_hujan_mm_bulanan' => 150,
            'kondisi_drainase' => 'Buruk — Tergenang',
        ]));

        $this->assertSame(FertilizationWindowService::PERLU_PERBAIKAN_DRAINASE, $result['status']);
        $this->assertFalse($result['layak']);
    }
}
