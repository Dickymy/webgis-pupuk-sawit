<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test isolasi program pemupukan (Pahan v2.7 — 4.5).
 *
 * - Satu blok hanya satu program aktif per tahun
 * - Realisasi antarprogram tidak tercampur
 * - Program otomatis dibuat saat realisasi pertama
 */
class ProgramPemupukanIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::create(['nama' => 'Petani', 'alamat' => 'Desa', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Prog',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2016,
            'topografi' => 'Datar 0-15°',
            'fase_tanaman' => 'TM',
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);
    }

    /** Program otomatis dibuat saat realisasi pertama */
    public function test_program_created_on_first_realization(): void
    {
        $kondisi = KondisiLahan::create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 150,
        ]);

        $rekomendasi = RekomendasiRbs::create([
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

        $this->assertDatabaseCount('program_pemupukans', 0);

        $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
            ]);

        $this->assertDatabaseCount('program_pemupukans', 1);
        $this->assertDatabaseHas('program_pemupukans', [
            'blok_lahan_id' => $this->blok->id,
            'tahun_program' => now()->year,
            'status_program' => ProgramPemupukan::STATUS_AKTIF,
        ]);

        // Realisasi terhubung ke program
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'blok_lahan_id' => $this->blok->id,
            'program_pemupukan_id' => ProgramPemupukan::first()->id,
        ]);
    }

    /** Dua realisasi satu blok satu tahun → satu program aktif */
    public function test_same_program_reused_within_year(): void
    {
        // Pre-create program
        $program = ProgramPemupukan::create([
            'uuid' => Str::uuid()->toString(),
            'blok_lahan_id' => $this->blok->id,
            'tahun_program' => now()->year,
            'status_program' => ProgramPemupukan::STATUS_AKTIF,
        ]);

        $kondisi = KondisiLahan::create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 150,
        ]);

        $rekomendasi = RekomendasiRbs::create([
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

        $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 100.0,
                'kcl_realisasi_kg' => 150.0,
                'status_realisasi' => 'SEBAGIAN',
            ]);

        // Masih satu program
        $this->assertDatabaseCount('program_pemupukans', 1);

        // Realisasi terhubung ke program yang sudah ada
        $this->assertDatabaseHas('realisasi_pemupukans', [
            'program_pemupukan_id' => $program->id,
        ]);
    }
}
