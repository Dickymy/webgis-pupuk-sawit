<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\ProgramStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test siklus hidup program pemupukan (Pahan v2.8 — 4.5).
 */
class ProgramLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private BlokLahan $blok;

    private ProgramPemupukan $program;

    private RekomendasiRbs $rekomendasi;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->create();
        $anggota = Anggota::factory()->create();
        $this->blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
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
        ]);
    }

    public function test_kebutuhan_tahunan_terpenuhi_mengubah_program_selesai(): void
    {
        $service = app(ProgramStatusService::class);

        $currentApp = [
            'active_stage' => 0,
            'status_stage' => 'SELESAI_TAHUNAN',
            'urea_sisa_tahunan' => 0,
            'kcl_sisa_tahunan' => 0,
        ];

        $service->synchronizeStatus($this->program, $currentApp);

        $this->program->refresh();
        $this->assertEquals(ProgramPemupukan::STATUS_SELESAI, $this->program->status_program);
        $this->assertNull($this->program->active_key);
    }

    public function test_sisa_masih_ada_program_tetap_aktif(): void
    {
        $service = app(ProgramStatusService::class);

        $currentApp = [
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'urea_sisa_tahunan' => 50.0,
            'kcl_sisa_tahunan' => 40.0,
        ];

        $service->synchronizeStatus($this->program, $currentApp);

        $this->program->refresh();
        $this->assertEquals(ProgramPemupukan::STATUS_AKTIF, $this->program->status_program);
    }

    public function test_program_selesai_tidak_dibuka_otomatis(): void
    {
        $this->program->update([
            'status_program' => ProgramPemupukan::STATUS_SELESAI,
            'active_key' => null,
        ]);

        $service = app(ProgramStatusService::class);

        // Even if sisa > 0 (edge case), should NOT reopen
        $currentApp = [
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'urea_sisa_tahunan' => 50.0,
            'kcl_sisa_tahunan' => 40.0,
        ];

        $service->synchronizeStatus($this->program, $currentApp);

        $this->program->refresh();
        $this->assertEquals(ProgramPemupukan::STATUS_SELESAI, $this->program->status_program);
    }
}
