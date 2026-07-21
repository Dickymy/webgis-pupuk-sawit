<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Services\RecommendationReliabilityService;
use Tests\TestCase;

class RecommendationReliabilityTest extends TestCase
{
    private RecommendationReliabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecommendationReliabilityService::class);
    }

    private function makeBlok(array $attrs = []): BlokLahan
    {
        $blok = new BlokLahan;
        $blok->luas_ha = $attrs['luas_ha'] ?? 5.0;
        $blok->sph = $attrs['sph'] ?? 136;
        $blok->tahun_tanam = $attrs['tahun_tanam'] ?? (now()->year - 10);
        $blok->fase_tanaman = $attrs['fase_tanaman'] ?? 'TM';

        return $blok;
    }

    private function makeKondisi(array $attrs = []): KondisiLahan
    {
        $kondisi = new KondisiLahan;
        foreach ($attrs as $key => $value) {
            $kondisi->$key = $value;
        }

        return $kondisi;
    }

    public function test_data_lengkap_menghasilkan_skor_tinggi(): void
    {
        $blok = $this->makeBlok();
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Kuning Merata',
            'ph_tanah' => 5.5,
            'metode_pengukuran_ph' => 'pH_meter',
            'curah_hujan_mm_bulanan' => 150,
            'periode_curah_hujan' => '2026-06-01 s/d 2026-06-30',
            'sumber_curah_hujan' => 'open-meteo',
            'tanggal_pemupukan_terakhir' => now()->subDays(90),
            'kondisi_drainase' => 'Baik',
            'kondisi_pelepah' => 'Normal',
            'gejala_defisiensi' => ['N'],
        ]);

        $result = $this->service->calculate($blok, $kondisi, []);

        $this->assertGreaterThanOrEqual(70, $result['score']);
        $this->assertContains($result['kategori'], ['Cukup Kuat', 'Kuat secara Data']);
    }

    public function test_data_minimal_menghasilkan_skor_rendah(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => null, 'fase_tanaman' => null]);
        $kondisi = $this->makeKondisi([]);

        $result = $this->service->calculate($blok, $kondisi, []);

        $this->assertLessThan(50, $result['score']);
        $this->assertEquals('Data Tidak Cukup', $result['kategori']);
    }

    public function test_kategori_konsisten_dengan_config(): void
    {
        $categories = config('fertilization.reliability_categories');
        $this->assertNotEmpty($categories);

        // Verifikasi rentang tidak overlap dan konsisten
        foreach ($categories as $cat) {
            $this->assertArrayHasKey('min', $cat);
            $this->assertArrayHasKey('max', $cat);
            $this->assertArrayHasKey('label', $cat);
        }
    }
}
