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
 * Test CRUD Realisasi Pemupukan (Pahan v2.6).
 *
 * Skenario A: Tahap 1 Siap
 * Skenario B: Tahap 1 Sebagian
 * Skenario E: Pembatalan
 */
class RealisasiPemupukanCrudTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    private RekomendasiRbs $rekomendasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::create(['nama' => 'Petani Test', 'alamat' => 'Desa', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok A',
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
            'saran_tindakan_utama' => 'Lanjutkan pemupukan standar.',
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'urea_aplikasi_saat_ini' => 272.0,
            'kcl_aplikasi_saat_ini' => 340.0,
            'active_stage' => 1,
            'status_stage' => CurrentApplicationCalculator::TAHAP_1_SIAP,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'versi_mesin_rekomendasi' => 'pahan-v2.6',
        ]);
    }

    /** Skenario A: Admin dapat membuat realisasi Tahap 1 */
    public function test_admin_can_create_realisasi_tahap_1(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'tahap' => 1,
            'status_realisasi' => 'SELESAI',
            'urea_realisasi_kg' => 272.0,
        ]);
    }

    /** Skenario B: Realisasi sebagian tidak menghasilkan SELESAI */
    public function test_realisasi_sebagian_tahap_1(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 100.0,
                'kcl_realisasi_kg' => 150.0,
                'status_realisasi' => 'SEBAGIAN',
            ]);

        $this->assertDatabaseHas('realisasi_pemupukans', [
            'status_realisasi' => 'SEBAGIAN',
            'urea_realisasi_kg' => 100.0,
        ]);
    }

    /** Skenario E: Pembatalan — record tetap ada tapi status BATAL */
    public function test_cancel_realisasi_keeps_record(): void
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
            'status_realisasi' => 'SELESAI',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patch(route('realisasi-pemupukan.cancel', $realisasi));

        $response->assertRedirect();

        // Record tetap ada (tidak dihapus)
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'id' => $realisasi->id,
            'status_realisasi' => 'BATAL',
        ]);
    }

    /** Admin dapat melihat halaman index realisasi */
    public function test_admin_can_view_realisasi_index(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.index'));

        $response->assertStatus(200);
    }

    /** Admin dapat melihat form create realisasi */
    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $this->rekomendasi));

        $response->assertStatus(200);
        $response->assertSee('Catat Realisasi');
    }

    /** Admin dapat mengedit realisasi */
    public function test_admin_can_edit_realisasi(): void
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
            'urea_realisasi_kg' => 200.0,
            'kcl_realisasi_kg' => 250.0,
            'status_realisasi' => 'SEBAGIAN',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('realisasi-pemupukan.update', $realisasi), [
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'id' => $realisasi->id,
            'urea_realisasi_kg' => 272.0,
            'status_realisasi' => 'SELESAI',
        ]);
    }
}
