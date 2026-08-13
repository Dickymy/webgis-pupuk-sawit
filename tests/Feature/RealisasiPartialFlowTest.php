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

/**
 * Test alur realisasi sebagian dan tahap (Pahan v2.6).
 *
 * Skenario B: Tahap 1 Sebagian — tidak pindah ke Tahap 2
 * Skenario C: Tahap 2 — interval 120 hari
 */
class RealisasiPartialFlowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    private RekomendasiRbs $rekomendasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::create(['nama' => 'Test', 'alamat' => 'Desa', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok B',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2016,
            'topografi' => 'Datar 0-15°',
            'fase_tanaman' => 'TM',
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);

        $kondisi = KondisiLahan::create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 150,
        ]);

        $this->rekomendasi = RekomendasiRbs::create([
            'blok_lahan_id' => $this->blok->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Test.',
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'active_stage' => 1,
            'status_stage' => CurrentApplicationCalculator::TAHAP_1_SIAP,
            'versi_mesin_rekomendasi' => 'pahan-v2.6',
        ]);
    }

    /** Realisasi sebagian menghasilkan status TAHAP_1_SEBAGIAN, bukan pindah ke Tahap 2 */
    public function test_partial_realization_stays_stage_1(): void
    {
        // Buat realisasi sebagian: 100 dari 272 Urea
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 150.0,
            'status_realisasi' => RealisasiPemupukan::STATUS_SEBAGIAN,
        ]);

        $service = app(FertilizationRealizationService::class);
        $summary = $service->getRealizationSummary($this->blok, $this->rekomendasi->id);

        // Tahap 1 ada tapi belum selesai
        $this->assertTrue($summary['tahap_1_ada']);
        $this->assertTrue($summary['tahap_1_sebagian']);
        $this->assertFalse($summary['tahap_1_selesai']);

        // CurrentApplicationCalculator: status harus TAHAP_1_SEBAGIAN
        $calculator = app(CurrentApplicationCalculator::class);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $summary,
            'analysis_date' => now(),
        ]);

        $this->assertEquals(1, $result['active_stage']);
        $this->assertEquals(CurrentApplicationCalculator::TAHAP_1_SEBAGIAN, $result['status_stage']);
        // Sisa rencana Tahap 1 = 272 - 100 = 172
        $this->assertEquals(172.0, $result['urea_aplikasi_saat_ini']);
    }

    /** Tahap 2 tidak siap sebelum 120 hari */
    public function test_stage_2_not_ready_before_120_days(): void
    {
        // Buat realisasi Tahap 1 selesai (30 hari lalu)
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(30)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => RealisasiPemupukan::STATUS_SELESAI,
        ]);

        $service = app(FertilizationRealizationService::class);
        $summary = $service->getRealizationSummary($this->blok, $this->rekomendasi->id);

        $calculator = app(CurrentApplicationCalculator::class);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $summary,
            'analysis_date' => now(),
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals(CurrentApplicationCalculator::MENUNGGU_INTERVAL, $result['status_stage']);
        $this->assertEquals(0, $result['urea_aplikasi_saat_ini']);
    }

    /** Tahap 2 siap setelah 120 hari */
    public function test_stage_2_ready_after_120_days(): void
    {
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(125)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => RealisasiPemupukan::STATUS_SELESAI,
        ]);

        $service = app(FertilizationRealizationService::class);
        $summary = $service->getRealizationSummary($this->blok, $this->rekomendasi->id);

        $calculator = app(CurrentApplicationCalculator::class);
        $result = $calculator->calculate([
            'annual_snapshot' => [
                'urea_total_estimasi_tahunan' => 544.0,
                'kcl_total_estimasi_tahunan' => 680.0,
            ],
            'window_result' => ['layak' => true],
            'realization_summary' => $summary,
            'analysis_date' => now(),
        ]);

        $this->assertEquals(2, $result['active_stage']);
        $this->assertEquals(CurrentApplicationCalculator::TAHAP_2_SIAP, $result['status_stage']);
        // Sisa tahunan: 544 - 272 = 272
        $this->assertEquals(272.0, $result['urea_aplikasi_saat_ini']);
    }

    /** Realisasi batal tidak dihitung dalam ringkasan */
    public function test_cancelled_realization_not_counted(): void
    {
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => RealisasiPemupukan::STATUS_BATAL,
        ]);

        $service = app(FertilizationRealizationService::class);
        $summary = $service->getRealizationSummary($this->blok, $this->rekomendasi->id);

        // Batal = tidak dihitung
        $this->assertFalse($summary['tahap_1_ada']);
        $this->assertFalse($summary['tahap_1_selesai']);
        $this->assertEquals(0, $summary['total_urea_realisasi']);
    }
}
