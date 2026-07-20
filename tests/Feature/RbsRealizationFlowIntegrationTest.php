<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\FertilizationRealizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbsRealizationFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private BlokLahan $blok;

    private Admin $admin;

    private KondisiLahan $kondisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();

        $anggota = Anggota::create(['nama' => 'Test', 'alamat' => 'Test', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Realisasi',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2020,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);
        $this->kondisi = KondisiLahan::create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'curah_hujan_mm_bulanan' => 150.0,
        ]);
    }

    private function createRbs(): RekomendasiRbs
    {
        return RekomendasiRbs::create([
            'blok_lahan_id' => $this->blok->id,
            'kondisi_lahan_id' => $this->kondisi->id,
            'admin_id' => $this->admin->id,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Test',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'versi_mesin_rekomendasi' => 'pahan-v2.5',
        ]);
    }

    public function test_aplikasi_saat_ini_is_50_percent_before_realization(): void
    {
        $calculator = app(CurrentApplicationCalculator::class);
        $realizationService = app(FertilizationRealizationService::class);

        $annualSnapshot = [
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
        ];

        $realizationSummary = $realizationService->getRealizationSummary($this->blok);
        $result = $calculator->calculate([
            'annual_snapshot' => $annualSnapshot,
            'window_result' => ['layak' => true],
            'realization_summary' => $realizationSummary,
        ]);

        $this->assertEquals(1, $result['active_stage']);
        $this->assertEquals('TAHAP_1_SIAP', $result['status_stage']);
        $this->assertEquals(272.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(340.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_tahap_2_not_ready_before_60_days(): void
    {
        $calculator = app(CurrentApplicationCalculator::class);
        $realizationService = app(FertilizationRealizationService::class);

        // Simulasi realisasi Tahap 1, 30 hari lalu
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->createRbs()->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(30)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 250.0,
            'kcl_realisasi_kg' => 300.0,
            'status_realisasi' => 'SELESAI',
        ]);

        $realizationSummary = $realizationService->getRealizationSummary($this->blok);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $realizationSummary,
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals('MENUNGGU_INTERVAL', $result['status_stage']);
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_tahap_2_ready_after_60_days_with_actual_remaining(): void
    {
        $calculator = app(CurrentApplicationCalculator::class);
        $realizationService = app(FertilizationRealizationService::class);

        // Simulasi realisasi Tahap 1, 70 hari lalu (kurang dari rencana)
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->createRbs()->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(70)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 250.0,
            'kcl_realisasi_kg' => 300.0,
            'status_realisasi' => 'SELESAI',
        ]);

        $realizationSummary = $realizationService->getRealizationSummary($this->blok);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $realizationSummary,
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals('TAHAP_2_SIAP', $result['status_stage']);
        // Sisa aktual: 544 - 250 = 294
        $this->assertEquals(294.0, $result['urea_aplikasi_saat_ini']);
        // Sisa aktual: 680 - 300 = 380
        $this->assertEquals(380.0, $result['kcl_aplikasi_saat_ini']);
    }

    public function test_selesai_tahunan_after_full_realization(): void
    {
        $calculator = app(CurrentApplicationCalculator::class);
        $realizationService = app(FertilizationRealizationService::class);

        // Realisasi Tahap 1 + Tahap 2 = total tahunan
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->createRbs()->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(120)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => 'SELESAI',
        ]);
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->createRbs()->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahap' => 2,
            'tanggal_realisasi' => now()->subDays(30)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => 'SELESAI',
        ]);

        $realizationSummary = $realizationService->getRealizationSummary($this->blok);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $realizationSummary,
        ]);

        $this->assertEquals(0, $result['active_stage']);
        $this->assertEquals('SELESAI_TAHUNAN', $result['status_stage']);
        $this->assertEquals(0.0, $result['urea_aplikasi_saat_ini']);
        $this->assertEquals(0.0, $result['kcl_aplikasi_saat_ini']);
    }
}
