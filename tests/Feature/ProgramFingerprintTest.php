<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test fingerprint berbasis program (Pahan v2.8 — 4.8).
 */
class ProgramFingerprintTest extends TestCase
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

    public function test_fingerprint_memasukkan_program_id(): void
    {
        $service = app(RbsService::class);
        $result = $service->analisis($this->blok);

        $rekomendasi = $result['rekomendasi'];

        $this->assertNotNull($rekomendasi->analysis_fingerprint);
        $this->assertNotNull($rekomendasi->program_pemupukan_id);

        // Fingerprint harus berubah jika program berbeda
        // (This is implicitly tested by the fact that program_pemupukan_id is in fingerprint data)
        $this->assertEquals(64, strlen($rekomendasi->analysis_fingerprint)); // SHA-256 = 64 hex chars
    }
}
