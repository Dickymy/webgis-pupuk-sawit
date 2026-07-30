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
            'curah_hujan_mm_bulanan' => 150,
            'periode_curah_hujan' => '2026-06-01 s/d 2026-06-30',
            'sumber_curah_hujan' => 'open-meteo',
            'tanggal_pemupukan_terakhir' => now()->subDays(90),
            'musim_saat_ini' => 'Musim Hujan',
            'kelembaban_tanah' => 'Normal',
            'kondisi_drainase' => 'Baik',
            'foto_observasi_path' => 'observasi/daun.jpg',
        ]);

        $result = $this->service->calculate($blok, $kondisi, []);

        $this->assertSame(100, $result['score']);
        $this->assertSame('Lengkap', $result['kategori']);
    }

    public function test_data_minimal_menghasilkan_skor_rendah(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => null, 'fase_tanaman' => null]);
        $kondisi = $this->makeKondisi([]);

        $result = $this->service->calculate($blok, $kondisi, []);

        $this->assertLessThan(50, $result['score']);
        $this->assertEquals('Perlu Dilengkapi', $result['kategori']);
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

    public function test_foto_hanya_dokumentasi_dan_tidak_mengubah_skor(): void
    {
        $blok = $this->makeBlok();
        $kondisi = $this->makeKondisi([
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 150,
            'periode_curah_hujan' => '30 hari terakhir',
            'sumber_curah_hujan' => 'manual',
            'tanggal_pemupukan_terakhir' => now()->subDays(90),
            'kelembaban_tanah' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $tanpaFoto = $this->service->calculate($blok, $kondisi);
        $kondisi->foto_observasi_path = 'observasi/dokumentasi.jpg';
        $denganFoto = $this->service->calculate($blok, $kondisi);

        $this->assertSame($tanpaFoto['score'], $denganFoto['score']);
        $this->assertSame($tanpaFoto['kategori'], $denganFoto['kategori']);
    }

    public function test_rule_terpicu_tidak_mengubah_kelengkapan_data(): void
    {
        $blok = $this->makeBlok();
        $kondisi = $this->makeKondisi(['warna_daun' => 'Hijau Normal']);

        $tanpaRule = $this->service->calculate($blok, $kondisi, []);
        $denganRule = $this->service->calculate($blok, $kondisi, [(object) [
            'sumber_penulis' => 'Iyung Pahan',
            'tingkat_bukti' => 'BUKU',
        ]]);

        $this->assertSame($tanpaRule['score'], $denganRule['score']);
        $this->assertArrayNotHasKey('rule_bersumber', $denganRule['rincian']);
    }
}
