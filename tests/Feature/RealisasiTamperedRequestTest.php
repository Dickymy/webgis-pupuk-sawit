<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test manipulasi request (Pahan v2.7 — 4.2).
 *
 * Server tidak mempercayai tahap, rencana, tahun, dan blok dari browser.
 * Request mengirim nilai palsu → server tetap menyimpan nilai resmi.
 */
class RealisasiTamperedRequestTest extends TestCase
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
            'nama_blok' => 'Blok Tamper',
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

    /** Rencana palsu 500 kg → server tetap menyimpan 272 kg (rencana resmi) */
    public function test_tampered_rencana_ignored(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                // Field palsu — server HARUS mengabaikan ini
                'urea_rencana_kg' => 500.0,
                'kcl_rencana_kg' => 600.0,
            ]);

        $response->assertRedirect();

        // Server menyimpan rencana resmi (272 = 50% * 544)
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
        ]);
    }

    /** Tahap palsu = 2 → server tetap menyimpan tahap = 1 (karena Tahap 1 Siap) */
    public function test_tampered_tahap_ignored(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                // Field palsu
                'tahap' => 2,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('realisasi_pemupukans', [
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'tahap' => 1, // Server tetap menyimpan tahap 1
        ]);
    }

    /** Tahun program palsu 2030 → server tetap menyimpan tahun berjalan */
    public function test_tampered_tahun_program_ignored(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                // Field palsu
                'tahun_program' => 2030,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('realisasi_pemupukans', [
            'rekomendasi_rbs_id' => $this->rekomendasi->id,
            'tahun_program' => now()->year, // Server tetap menyimpan tahun berjalan
        ]);
    }
}
