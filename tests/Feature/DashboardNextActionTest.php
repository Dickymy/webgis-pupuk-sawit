<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test dashboard menampilkan tindakan berikutnya dengan jelas (Pahan v2.8 — 5.7).
 */
class DashboardNextActionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_dashboard_loads_with_stats(): void
    {
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats');
        $response->assertViewHas('mapData');
    }

    public function test_dashboard_shows_blok_perlu_tindakan(): void
    {
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
        ]);

        // Blok dengan kondisi tapi tanpa analisis → perlu tindakan
        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('blokPerluTindakan');
    }

    public function test_dashboard_no_alur_kerja_stepper(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Alur Kerja Pemupukan');
    }

    public function test_dashboard_no_verifikasi_button(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Verifikasi');
    }
}
