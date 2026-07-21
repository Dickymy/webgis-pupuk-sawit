<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test realisasi memakai program yang sama dengan rekomendasi (Pahan v2.8 — 4.3).
 */
class RealisasiProgramConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    private ProgramPemupukan $program;

    private RekomendasiRbs $rekomendasi;

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

        $this->program = ProgramPemupukan::create([
            'uuid' => Str::uuid()->toString(),
            'blok_lahan_id' => $this->blok->id,
            'tahun_program' => now()->year,
            'status_program' => ProgramPemupukan::STATUS_AKTIF,
            'active_key' => $this->blok->id.'-'.now()->year,
        ]);

        $this->rekomendasi = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $this->blok->id,
            'program_pemupukan_id' => $this->program->id,
            'is_latest' => true,
            'urea_total_estimasi_tahunan' => 100.0,
            'kcl_total_estimasi_tahunan' => 80.0,
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'urea_aplikasi_saat_ini' => 50.0,
            'kcl_aplikasi_saat_ini' => 40.0,
        ]);
    }

    public function test_realisasi_memakai_program_yang_sama_dengan_rekomendasi(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $this->rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 50.0,
                'kcl_realisasi_kg' => 40.0,
                'status_realisasi' => RealisasiPemupukan::STATUS_SELESAI,
            ]);

        $response->assertRedirect();

        $realisasi = RealisasiPemupukan::latest()->first();
        $this->assertNotNull($realisasi);
        $this->assertEquals($this->program->id, $realisasi->program_pemupukan_id);
        $this->assertEquals($this->rekomendasi->program_pemupukan_id, $realisasi->program_pemupukan_id);
    }
}
