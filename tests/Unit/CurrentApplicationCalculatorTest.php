<?php

namespace Tests\Unit;

use App\Services\CurrentApplicationCalculator;
use Tests\TestCase;

class CurrentApplicationCalculatorTest extends TestCase
{
    private CurrentApplicationCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CurrentApplicationCalculator;
    }

    public function test_tahap_1_siap_when_no_realization_and_applicable(): void
    {
        $result = $this->calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => [
                'tahap_1_selesai' => false,
                'total_urea_realisasi' => 0,
                'total_kcl_realisasi' => 0,
                'interval_terpenuhi' => false,
                'tanggal_minimum_tahap_2' => null,
            ],
        ]);

        $this->assertEquals(1, $result['active_stage']);
        $this->assertEquals('TAHAP_1_SIAP', $result['status_stage']);
        $this->assertEquals(272.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(340.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_aplikasi_nol_when_not_applicable(): void
    {
        $result = $this->calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => false],
            'realization_summary' => [
                'tahap_1_selesai' => false,
                'total_urea_realisasi' => 0,
                'total_kcl_realisasi' => 0,
                'interval_terpenuhi' => false,
                'tanggal_minimum_tahap_2' => null,
            ],
        ]);

        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
        $this->assertEquals('MENUNGGU_KELAYAKAN', $result['status_stage']);
    }

    public function test_menunggu_interval_when_tahap_1_done_but_less_than_120_days(): void
    {
        $result = $this->calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => [
                'tahap_1_selesai' => true,
                'total_urea_realisasi' => 250.0,
                'total_kcl_realisasi' => 300.0,
                'interval_terpenuhi' => false,
                'tanggal_minimum_tahap_2' => '2026-09-01',
            ],
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals('MENUNGGU_INTERVAL', $result['status_stage']);
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
        $this->assertEquals(294.0, $result['urea_sisa_tahunan']);
    }

    public function test_tahap_2_siap_with_actual_remaining(): void
    {
        $result = $this->calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => [
                'tahap_1_selesai' => true,
                'total_urea_realisasi' => 250.0,
                'total_kcl_realisasi' => 300.0,
                'interval_terpenuhi' => true,
                'tanggal_minimum_tahap_2' => '2026-07-01',
            ],
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals('TAHAP_2_SIAP', $result['status_stage']);
        // Sisa aktual: 544 - 250 = 294
        $this->assertEquals(294.0, $result['urea_aplikasi_saat_ini']);
        // Sisa aktual: 680 - 300 = 380
        $this->assertEquals(380.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_selesai_tahunan_when_all_realized(): void
    {
        $result = $this->calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => [
                'tahap_1_selesai' => true,
                'total_urea_realisasi' => 544.0,
                'total_kcl_realisasi' => 680.0,
                'interval_terpenuhi' => true,
                'tanggal_minimum_tahap_2' => '2026-06-01',
            ],
        ]);

        $this->assertEquals(0, $result['active_stage']);
        $this->assertEquals('SELESAI_TAHUNAN', $result['status_stage']);
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
    }
}
