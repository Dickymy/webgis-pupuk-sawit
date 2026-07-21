<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\ProgramPemupukan;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test integrasi RbsService dengan ProgramPemupukan (Pahan v2.8 — 4.1).
 */
class RbsProgramIntegrationTest extends TestCase
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
            'ph_tanah' => 5.5,
            'kelembaban_tanah' => 'Lembab',
            'curah_hujan_mm_bulanan' => 180,
            'curah_hujan_kategori' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_analisis_pertama_membuat_program_dan_menghubungkan_rekomendasi(): void
    {
        $service = app(RbsService::class);
        $result = $service->analisis($this->blok);

        $this->assertTrue($result['sukses']);

        $rekomendasi = $result['rekomendasi'];

        // Program harus dibuat dan terhubung
        $this->assertNotNull($rekomendasi->program_pemupukan_id);

        $program = ProgramPemupukan::find($rekomendasi->program_pemupukan_id);
        $this->assertNotNull($program);
        $this->assertEquals($this->blok->id, $program->blok_lahan_id);
        $this->assertEquals(now()->year, $program->tahun_program);
        $this->assertEquals(ProgramPemupukan::STATUS_AKTIF, $program->status_program);
        $this->assertNotNull($program->active_key);
    }

    public function test_analisis_kedua_pada_program_sama_tetap_membaca_realisasi(): void
    {
        $service = app(RbsService::class);

        // Analisis pertama
        $result1 = $service->analisis($this->blok);
        $rekomendasi1 = $result1['rekomendasi'];
        $programId = $rekomendasi1->program_pemupukan_id;

        // Analisis kedua — harus pakai program yang sama
        $result2 = $service->analisis($this->blok);
        $rekomendasi2 = $result2['rekomendasi'];

        $this->assertEquals($programId, $rekomendasi2->program_pemupukan_id);
    }

    public function test_versi_mesin_rekomendasi_adalah_v28(): void
    {
        $service = app(RbsService::class);
        $result = $service->analisis($this->blok);

        $this->assertEquals('pahan-v2.8', $result['rekomendasi']->versi_mesin_rekomendasi);
    }
}
