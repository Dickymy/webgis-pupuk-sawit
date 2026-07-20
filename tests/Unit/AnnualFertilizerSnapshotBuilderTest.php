<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\AnnualFertilizerSnapshotBuilder;
use Tests\TestCase;

class AnnualFertilizerSnapshotBuilderTest extends TestCase
{
    private AnnualFertilizerSnapshotBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AnnualFertilizerSnapshotBuilder;
    }

    public function test_build_with_valid_dose_and_applicable(): void
    {
        $blok = new BlokLahan(['luas_ha' => 2.0, 'sph' => 136]);
        $doseRef = [
            'urea' => ['min' => 1.0, 'max' => 3.0, 'estimate' => 2.0],
            'kcl' => ['min' => 1.5, 'max' => 3.5, 'estimate' => 2.5],
        ];

        $result = $this->builder->build($blok, $doseRef, true);

        $this->assertEquals(272, $result['jumlah_pokok']);
        $this->assertEquals(272.0, $result['urea_total_min_tahunan']);
        $this->assertEquals(816.0, $result['urea_total_max_tahunan']);
        $this->assertEquals(544.0, $result['urea_total_estimasi_tahunan']);
        $this->assertEquals(408.0, $result['kcl_total_min_tahunan']);
        $this->assertEquals(952.0, $result['kcl_total_max_tahunan']);
        $this->assertEquals(680.0, $result['kcl_total_estimasi_tahunan']);
        $this->assertEquals(11, $result['urea_karung_estimasi_tahunan']); // ceil(544/50)
        $this->assertEquals(14, $result['kcl_karung_estimasi_tahunan']); // ceil(680/50)
        // Aplikasi saat ini = 50% estimasi tahunan karena layak (Pahan v2.5)
        $this->assertEquals(272.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(340.0, $result['kcl_aplikasi_saat_ini']);
        // Snapshot luas dan SPH (Pahan v2.5)
        $this->assertEquals(2.0, $result['luas_ha_snapshot']);
        $this->assertEquals(136, $result['sph_snapshot']);
    }

    public function test_build_with_valid_dose_but_not_applicable(): void
    {
        $blok = new BlokLahan(['luas_ha' => 2.0, 'sph' => 136]);
        $doseRef = [
            'urea' => ['min' => 1.0, 'max' => 3.0, 'estimate' => 2.0],
            'kcl' => ['min' => 1.5, 'max' => 3.5, 'estimate' => 2.5],
        ];

        $result = $this->builder->build($blok, $doseRef, false);

        // Kebutuhan tahunan tetap ada
        $this->assertEquals(544.0, $result['urea_total_estimasi_tahunan']);
        $this->assertEquals(680.0, $result['kcl_total_estimasi_tahunan']);
        $this->assertEquals(11, $result['urea_karung_estimasi_tahunan']);
        $this->assertEquals(14, $result['kcl_karung_estimasi_tahunan']);
        // Aplikasi saat ini = 0 karena ditunda
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_build_with_null_dose_returns_null_annual(): void
    {
        $blok = new BlokLahan(['luas_ha' => 2.0, 'sph' => 136]);
        $doseRef = [
            'urea' => ['min' => null, 'max' => null, 'estimate' => null],
            'kcl' => ['min' => null, 'max' => null, 'estimate' => null],
        ];

        $result = $this->builder->build($blok, $doseRef, true);

        $this->assertNull($result['urea_total_min_tahunan']);
        $this->assertNull($result['urea_total_estimasi_tahunan']);
        $this->assertNull($result['urea_karung_estimasi_tahunan']);
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
        $this->assertEquals(272, $result['jumlah_pokok']);
    }
}
