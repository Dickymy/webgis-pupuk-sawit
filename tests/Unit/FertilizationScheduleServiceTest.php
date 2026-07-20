<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Services\FertilizationScheduleService;
use Tests\TestCase;

class FertilizationScheduleServiceTest extends TestCase
{
    private FertilizationScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FertilizationScheduleService;
    }

    public function test_default_split_is_50_50(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => 150,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2016, 'luas_ha' => 2, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 2.0, 'dosis_kcl' => 2.5, 'total_urea' => 544, 'total_kcl' => 680],
            $kondisi,
            $blok,
            ['layak' => true, 'alasan' => []],
            ['umur' => 10, 'fase' => 'TM', 'fase_label' => 'Tanaman Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $this->assertCount(2, $jadwal);
        $this->assertEquals(50, $jadwal[0]['persentase_urea']);
        $this->assertEquals(50, $jadwal[1]['persentase_urea']);
    }

    public function test_no_march_september_automatic(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => 150,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2016, 'luas_ha' => 2, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 2.0, 'dosis_kcl' => 2.5, 'total_urea' => 544, 'total_kcl' => 680],
            $kondisi,
            $blok,
            ['layak' => true, 'alasan' => []],
            ['umur' => 10, 'fase' => 'TM', 'fase_label' => 'Tanaman Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $jadwalStr = json_encode($jadwal);
        $this->assertStringNotContainsString('Maret', $jadwalStr);
        $this->assertStringNotContainsString('September', $jadwalStr);
    }

    public function test_stage_2_waits_for_stage_1(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => 150,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2016, 'luas_ha' => 2, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 2.0, 'dosis_kcl' => 2.5, 'total_urea' => 544, 'total_kcl' => 680],
            $kondisi,
            $blok,
            ['layak' => true, 'alasan' => []],
            ['umur' => 10, 'fase' => 'TM', 'fase_label' => 'Tanaman Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $this->assertEquals('Menunggu Realisasi Tahap 1', $jadwal[1]['status_tahap']);
        $this->assertStringContainsString('60 hari', $jadwal[1]['estimasi_waktu']);
    }

    public function test_not_feasible_returns_empty_array(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => 300,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2016, 'luas_ha' => 2, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 2.0, 'dosis_kcl' => 2.5, 'total_urea' => 544, 'total_kcl' => 680],
            $kondisi,
            $blok,
            ['layak' => false, 'alasan' => ['Curah hujan terlalu tinggi']],
            ['umur' => 10, 'fase' => 'TM', 'fase_label' => 'Tanaman Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $this->assertEmpty($jadwal);
    }

    public function test_no_numeric_rainfall_returns_empty_array(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => null,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2016, 'luas_ha' => 2, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 2.0, 'dosis_kcl' => 2.5, 'total_urea' => 544, 'total_kcl' => 680],
            $kondisi,
            $blok,
            ['layak' => true, 'alasan' => []],
            ['umur' => 10, 'fase' => 'TM', 'fase_label' => 'Tanaman Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $this->assertEmpty($jadwal);
    }

    public function test_no_fase_abbreviation_in_schedule(): void
    {
        $kondisi = new KondisiLahan([
            'curah_hujan_mm_bulanan' => 150,
            'ada_gulma_dominan' => false,
            'ada_serangan_hama' => false,
        ]);
        $blok = new BlokLahan(['tahun_tanam' => 2024, 'luas_ha' => 1, 'sph' => 136]);

        $jadwal = $this->service->generate(
            ['dosis_urea' => 0.6, 'dosis_kcl' => 1.0, 'total_urea' => 81.6, 'total_kcl' => 136],
            $kondisi,
            $blok,
            ['layak' => true, 'alasan' => []],
            ['umur' => 2, 'fase' => 'TBM', 'fase_label' => 'Tanaman Belum Menghasilkan', 'tanggal_referensi' => '2026-07-20', 'metode_perhitungan_umur' => 'tahun_tanam', 'needs_phase_verification' => false, 'phase_conflict' => false]
        );

        $jadwalStr = json_encode($jadwal);
        // Ensure TBM/TM abbreviations don't appear in user-facing text
        $this->assertStringNotContainsString('"TBM"', $jadwalStr);
        $this->assertStringNotContainsString('"TM"', $jadwalStr);
        $this->assertStringContainsString('Tanaman Belum Menghasilkan', $jadwalStr);
    }
}
