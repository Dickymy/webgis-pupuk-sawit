<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\PlantAgeService;
use App\Services\PlantContextService;
use Carbon\Carbon;
use Tests\TestCase;

class PlantContextServiceTest extends TestCase
{
    private PlantContextService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlantContextService(new PlantAgeService);
    }

    public function test_umur_2_returns_tbm(): void
    {
        $blok = new BlokLahan(['tahun_tanam' => 2024]);
        $result = $this->service->resolve($blok, Carbon::create(2026, 7, 20));

        $this->assertEquals(2, $result['umur']);
        $this->assertEquals('TBM', $result['fase']);
        $this->assertEquals('Tanaman Belum Menghasilkan', $result['fase_label']);
        $this->assertFalse($result['needs_phase_verification']);
    }

    public function test_umur_10_returns_tm(): void
    {
        $blok = new BlokLahan(['tahun_tanam' => 2016]);
        $result = $this->service->resolve($blok, Carbon::create(2026, 7, 20));

        $this->assertEquals(10, $result['umur']);
        $this->assertEquals('TM', $result['fase']);
        $this->assertEquals('Tanaman Menghasilkan', $result['fase_label']);
        $this->assertFalse($result['needs_phase_verification']);
    }

    public function test_umur_3_needs_verification(): void
    {
        $blok = new BlokLahan(['tahun_tanam' => 2023]);
        $result = $this->service->resolve($blok, Carbon::create(2026, 7, 20));

        $this->assertEquals(3, $result['umur']);
        $this->assertNull($result['fase']);
        $this->assertTrue($result['needs_phase_verification']);
    }

    public function test_umur_3_with_manual_fase_accepted(): void
    {
        $blok = new BlokLahan(['tahun_tanam' => 2023, 'fase_tanaman' => 'TM']);
        $result = $this->service->resolve($blok, Carbon::create(2026, 7, 20));

        $this->assertEquals(3, $result['umur']);
        $this->assertEquals('TM', $result['fase']);
        $this->assertFalse($result['needs_phase_verification']);
    }

    public function test_historical_phase_not_using_current_blok_phase(): void
    {
        // Blok saat ini TM, tapi observasi 2022 (umur 2) → harus TBM
        $blok = new BlokLahan(['tahun_tanam' => 2020, 'fase_tanaman' => 'TM']);
        $result = $this->service->resolve($blok, Carbon::create(2022, 7, 20));

        $this->assertEquals(2, $result['umur']);
        $this->assertEquals('TBM', $result['fase']);
        $this->assertTrue($result['phase_conflict']);
    }

    public function test_no_tahun_tanam_returns_null(): void
    {
        $blok = new BlokLahan(['tahun_tanam' => null]);
        $result = $this->service->resolve($blok, Carbon::create(2026, 7, 20));

        $this->assertNull($result['umur']);
        $this->assertNull($result['fase']);
        $this->assertTrue($result['needs_phase_verification']);
    }
}
