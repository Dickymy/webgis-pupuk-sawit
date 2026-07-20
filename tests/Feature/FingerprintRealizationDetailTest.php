<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use App\Services\RecommendationOperationalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test fingerprint berubah saat rincian realisasi berubah (Pahan v2.7 — 4.7).
 */
class FingerprintRealizationDetailTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    private RekomendasiRbs $rekomendasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::create(['nama' => 'Petani', 'alamat' => 'Desa', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok FP',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2016,
            'jenis_tanah' => 'Tanah Lempung',
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
            'urea_aplikasi_saat_ini' => 272.0,
            'kcl_aplikasi_saat_ini' => 340.0,
            'active_stage' => 1,
            'status_stage' => CurrentApplicationCalculator::TAHAP_1_SIAP,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'versi_mesin_rekomendasi' => 'pahan-v2.7',
            'analysis_fingerprint' => 'initial-fingerprint',
        ]);
    }

    /** Tanggal realisasi berubah → fingerprint berubah */
    public function test_date_change_changes_fingerprint(): void
    {
        $realisasi = RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(5)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 150.0,
            'status_realisasi' => 'SEBAGIAN',
        ]);

        // Refresh to get fingerprint A
        $refreshService = app(RecommendationOperationalRefreshService::class);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintA = $this->rekomendasi->analysis_fingerprint;

        // Ubah tanggal
        $realisasi->update(['tanggal_realisasi' => now()->subDays(3)->toDateString()]);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintB = $this->rekomendasi->analysis_fingerprint;

        $this->assertNotEquals($fingerprintA, $fingerprintB);
    }

    /** Jumlah berubah → fingerprint berubah */
    public function test_amount_change_changes_fingerprint(): void
    {
        $realisasi = RealisasiPemupukan::create([
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
            'status_realisasi' => 'SEBAGIAN',
        ]);

        $refreshService = app(RecommendationOperationalRefreshService::class);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintA = $this->rekomendasi->analysis_fingerprint;

        // Ubah jumlah
        $realisasi->update(['urea_realisasi_kg' => 200.0]);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintB = $this->rekomendasi->analysis_fingerprint;

        $this->assertNotEquals($fingerprintA, $fingerprintB);
    }

    /** Status berubah → fingerprint berubah */
    public function test_status_change_changes_fingerprint(): void
    {
        $realisasi = RealisasiPemupukan::create([
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
            'status_realisasi' => 'SEBAGIAN',
        ]);

        $refreshService = app(RecommendationOperationalRefreshService::class);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintA = $this->rekomendasi->analysis_fingerprint;

        // Ubah status
        $realisasi->update(['status_realisasi' => 'SELESAI']);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintB = $this->rekomendasi->analysis_fingerprint;

        $this->assertNotEquals($fingerprintA, $fingerprintB);
    }

    /** Record dibatalkan → fingerprint berubah */
    public function test_cancellation_changes_fingerprint(): void
    {
        $realisasi = RealisasiPemupukan::create([
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
            'status_realisasi' => 'SEBAGIAN',
        ]);

        $refreshService = app(RecommendationOperationalRefreshService::class);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintA = $this->rekomendasi->analysis_fingerprint;

        // Batalkan
        $realisasi->update(['status_realisasi' => 'BATAL']);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintB = $this->rekomendasi->analysis_fingerprint;

        $this->assertNotEquals($fingerprintA, $fingerprintB);
    }

    /** Override berubah → fingerprint berubah */
    public function test_override_change_changes_fingerprint(): void
    {
        $realisasi = RealisasiPemupukan::create([
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
            'status_realisasi' => 'SEBAGIAN',
            'confirmed_over_plan' => false,
        ]);

        $refreshService = app(RecommendationOperationalRefreshService::class);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintA = $this->rekomendasi->analysis_fingerprint;

        // Ubah override
        $realisasi->update(['confirmed_over_plan' => true]);
        $refreshService->refreshAfterRealization($realisasi);
        $this->rekomendasi->refresh();
        $fingerprintB = $this->rekomendasi->analysis_fingerprint;

        $this->assertNotEquals($fingerprintA, $fingerprintB);
    }
}
