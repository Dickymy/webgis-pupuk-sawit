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
 * Skenario D — Realisasi Berlebih.
 *
 * 1. Input melebihi rencana tanpa konfirmasi → gagal
 * 2. Input melebihi rencana dengan alasan → dapat disimpan jika di bawah tahunan
 * 3. Input melebihi kebutuhan tahunan tanpa override → gagal
 * 4. Override tanpa alasan → gagal
 * 5. Override lengkap → tersimpan
 */
class RealisasiOverLimitTest extends TestCase
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
            'nama_blok' => 'Blok C',
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
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Test.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'active_stage' => 1,
            'status_stage' => CurrentApplicationCalculator::TAHAP_1_SIAP,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'versi_mesin_rekomendasi' => 'pahan-v2.6',
        ]);
    }

    /** 1. Melebihi rencana tanpa konfirmasi → gagal */
    public function test_over_plan_without_confirmation_fails(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 300.0, // > rencana 272
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                // confirmed_over_plan NOT set
            ]);

        $response->assertSessionHasErrors('confirmed_over_plan');
    }

    /** 2. Melebihi rencana dengan konfirmasi + catatan → berhasil (masih di bawah tahunan) */
    public function test_over_plan_with_confirmation_succeeds(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 300.0, // > rencana but < tahunan 544
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                'confirmed_over_plan' => true,
                'catatan_pelaksana' => 'Persediaan Urea berlebih, diaplikasikan sekaligus.',
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'urea_realisasi_kg' => 300.0,
            'confirmed_over_plan' => true,
        ]);
    }

    /** 3. Melebihi kebutuhan tahunan tanpa override → gagal */
    public function test_over_annual_without_override_fails(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 600.0, // > tahunan 544
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                'confirmed_over_plan' => true,
                'catatan_pelaksana' => 'Alasan over plan.',
            ]);

        $response->assertSessionHasErrors('override_annual_limit');
    }

    /** 4. Override tanpa alasan → gagal */
    public function test_override_without_reason_fails(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 600.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                'confirmed_over_plan' => true,
                'catatan_pelaksana' => 'Alasan.',
                'override_annual_limit' => true,
                // override_reason NOT set
            ]);

        $response->assertSessionHasErrors('override_reason');
    }

    /** 5. Override lengkap → tersimpan */
    public function test_full_override_succeeds(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tahap' => 1,
                'tahun_program' => now()->year,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_rencana_kg' => 272.0,
                'kcl_rencana_kg' => 340.0,
                'urea_realisasi_kg' => 600.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
                'confirmed_over_plan' => true,
                'catatan_pelaksana' => 'Persediaan banyak karena pengiriman terlambat.',
                'override_annual_limit' => true,
                'override_reason' => 'Stok Urea menumpuk dari pengiriman sebelumnya. Diotorisasi oleh ketua kelompok tani.',
            ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'urea_realisasi_kg' => 600.0,
            'override_annual_limit' => true,
            'override_reason' => 'Stok Urea menumpuk dari pengiriman sebelumnya. Diotorisasi oleh ketua kelompok tani.',
        ]);
    }
}
