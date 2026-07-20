<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\PlantAgeService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Test PlantAgeService — umur berdasarkan tanggal observasi.
 */
class PlantAgeServiceTest extends TestCase
{
    private PlantAgeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlantAgeService;
    }

    private function makeBlok(array $attrs = []): BlokLahan
    {
        $blok = new BlokLahan;
        $blok->tahun_tanam = $attrs['tahun_tanam'] ?? null;

        return $blok;
    }

    public function test_umur_pada_tanggal_observasi(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => 2020]);
        $tanggal = Carbon::parse('2026-06-15');

        $result = $this->service->calculateAgeAt($blok, $tanggal);

        $this->assertEquals(6, $result['umur']);
        $this->assertEquals('2026-06-15', $result['tanggal_referensi']);
        $this->assertEquals('tahun_tanam', $result['metode_perhitungan']);
        $this->assertTrue($result['is_estimate']);
    }

    public function test_umur_pada_tanggal_berbeda(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => 2023]);
        $tanggal = Carbon::parse('2025-03-01'); // 2 tahun

        $result = $this->service->calculateAgeAt($blok, $tanggal);
        $this->assertEquals(2, $result['umur']);
    }

    public function test_umur_null_jika_tahun_tanam_kosong(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => null]);
        $tanggal = Carbon::parse('2026-07-01');

        $result = $this->service->calculateAgeAt($blok, $tanggal);

        $this->assertNull($result['umur']);
        $this->assertEquals('tidak_tersedia', $result['metode_perhitungan']);
    }

    public function test_umur_tidak_negatif(): void
    {
        // Tanggal observasi sebelum tahun tanam (edge case)
        $blok = $this->makeBlok(['tahun_tanam' => 2030]);
        $tanggal = Carbon::parse('2026-07-01');

        $result = $this->service->calculateAgeAt($blok, $tanggal);
        $this->assertEquals(0, $result['umur']);
    }

    public function test_current_age(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => 2020]);
        $age = $this->service->calculateCurrentAge($blok);
        $this->assertEquals(now()->year - 2020, $age);
    }
}
