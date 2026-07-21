<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use App\Services\RealisasiEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test penolakan rekomendasi historis (Pahan v2.8 — 4.6).
 */
class HistoricalRecommendationRejectionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::factory()->create();
        $this->blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
            'tahun_tanam' => 2018,
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->subDays(5),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 180,
            'curah_hujan_kategori' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);
    }

    public function test_rekomendasi_historis_ditolak(): void
    {
        // Buat rekomendasi historis (is_latest = false)
        $rekomendasi = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $this->blok->id,
            'is_latest' => false,
            'urea_total_estimasi_tahunan' => 100.0,
            'kcl_total_estimasi_tahunan' => 80.0,
        ]);

        $service = app(RealisasiEligibilityService::class);
        $result = $service->evaluate($rekomendasi);

        $this->assertFalse($result['boleh_mencatat']);
        $this->assertStringContainsString('historis', $result['reason']);
    }

    public function test_rekomendasi_terbaru_diterima(): void
    {
        $rekomendasi = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $this->blok->id,
            'is_latest' => true,
            'urea_total_estimasi_tahunan' => 100.0,
            'kcl_total_estimasi_tahunan' => 80.0,
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
        ]);

        $service = app(RealisasiEligibilityService::class);
        $result = $service->evaluate($rekomendasi);

        // Might be allowed or not depending on window, but should NOT be rejected for being historical
        $this->assertStringNotContainsString('historis', $result['reason']);
    }

    public function test_create_realisasi_dari_historis_gagal_via_controller(): void
    {
        $rekomendasi = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $this->blok->id,
            'is_latest' => false,
            'urea_total_estimasi_tahunan' => 100.0,
            'kcl_total_estimasi_tahunan' => 80.0,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $rekomendasi));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
