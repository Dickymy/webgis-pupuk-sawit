<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test laporan tidak memakai status legacy untuk keputusan (Pahan v2.8 — 4.9).
 */
class LaporanNonLegacyDecisionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_subtotal_memakai_aplikasi_saat_ini_bukan_total_legacy(): void
    {
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
        ]);

        // Buat rekomendasi dengan status legacy Tunda tapi status_stage siap
        RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'is_latest' => true,
            'status_kebutuhan_dominan' => 'Tunda', // legacy — seharusnya diabaikan
            'status_stage' => CurrentApplicationCalculator::TAHAP_1_SIAP,
            'urea_aplikasi_saat_ini' => 50.0,
            'kcl_aplikasi_saat_ini' => 40.0,
            'total_urea' => 50.0,
            'total_kcl' => 40.0,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('laporan.index'));

        $response->assertOk();

        // Laporan harus menghitung blok ini karena status_stage siap
        // Meskipun legacy status = Tunda
        $response->assertViewHas('totalUrea', 50.0);
        $response->assertViewHas('totalKcl', 40.0);
    }

    public function test_blok_selesai_tahunan_tidak_ikut_subtotal(): void
    {
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
        ]);

        RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'is_latest' => true,
            'status_kebutuhan_dominan' => 'Normal', // legacy Normal
            'status_stage' => CurrentApplicationCalculator::SELESAI_TAHUNAN,
            'urea_aplikasi_saat_ini' => 0.0,
            'kcl_aplikasi_saat_ini' => 0.0,
            'total_urea' => 100.0, // Legacy field — NOT used
            'total_kcl' => 80.0,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('laporan.index'));

        $response->assertOk();
        $response->assertViewHas('totalUrea', 0.0);
        $response->assertViewHas('totalKcl', 0.0);
    }
}
