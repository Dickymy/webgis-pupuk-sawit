<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test tahap dikunci oleh server (Pahan v2.7 — 4.4).
 *
 * - Tahap 1 baru setelah Tahap 1 selesai → ditolak (eligibility = MENUNGGU_INTERVAL)
 * - Tahap 2 sebelum Tahap 1 selesai → ditolak
 * - Tahap 2 sebelum 60 hari → ditolak
 * - Realisasi saat program selesai → ditolak
 */
class RealisasiStageLockTest extends TestCase
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
            'nama_blok' => 'Blok Lock',
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
        ]);
    }

    /** Setelah Tahap 1 selesai (30 hari lalu), realisasi ditolak (menunggu interval) */
    public function test_form_rejected_during_interval_wait(): void
    {
        // Tahap 1 selesai 30 hari lalu (< 60 hari)
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
            'status_realisasi' => 'SELESAI',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $this->rekomendasi));

        // Harus ditolak karena MENUNGGU_INTERVAL
        $response->assertRedirect(route('rbs.detail', $this->blok));
        $response->assertSessionHas('error');
    }

    /** Realisasi ditolak saat sisa tahunan nol (SELESAI_TAHUNAN) */
    public function test_form_rejected_when_annual_complete(): void
    {
        // Realisasi penuh (kebutuhan tahunan terpenuhi)
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(70)->toDateString(),
            'urea_rencana_kg' => 544.0,
            'kcl_rencana_kg' => 680.0,
            'urea_realisasi_kg' => 544.0,
            'kcl_realisasi_kg' => 680.0,
            'status_realisasi' => 'SELESAI',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $this->rekomendasi));

        $response->assertRedirect(route('rbs.detail', $this->blok));
        $response->assertSessionHas('error');
    }
}
