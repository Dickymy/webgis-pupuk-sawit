<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNewStatusFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_dashboard_uses_status_kondisi_not_legacy(): void
    {
        $anggota = Anggota::create(['nama' => 'Test Pemilik', 'alamat' => 'Test', 'no_hp' => '08123']);
        $blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok A',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2020,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);

        $kondisi = KondisiLahan::create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Kuning Merata',
        ]);

        RekomendasiRbs::create([
            'blok_lahan_id' => $blok->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Test',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'status_kondisi_tanaman' => 'GEJALA_BERAT',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'versi_mesin_rekomendasi' => 'pahan-v2.5',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);

        // Pastikan view data mengandung status_kondisi, bukan status_rbs
        $response->assertViewHas('mapData');
        $mapData = $response->viewData('mapData');
        $firstBlok = $mapData->first();

        $this->assertEquals('GEJALA_BERAT', $firstBlok['status_kondisi']);
        $this->assertArrayHasKey('status_kondisi_label', $firstBlok);
        $this->assertArrayHasKey('status_kelayakan', $firstBlok);
        $this->assertSame('ADA_GEJALA', $firstBlok['status_peta']);
        $this->assertSame('Ditemukan Gejala', $firstBlok['status_peta_label']);
        $this->assertArrayNotHasKey('status_rbs', $firstBlok);
    }

    public function test_dashboard_stats_use_new_status(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('gejala_berat', $stats);
        $this->assertArrayHasKey('terindikasi_defisiensi', $stats);
        $this->assertArrayHasKey('terindikasi_defisiensi_ringan', $stats);
        $this->assertArrayHasKey('kondisi_normal', $stats);
        $this->assertArrayHasKey('siap_dipupuk', $stats);
        $this->assertArrayHasKey('menunggu_interval', $stats);
        $this->assertArrayHasKey('program_selesai', $stats);
        $this->assertArrayHasKey('layak_dijadwalkan', $stats);
    }

    public function test_dashboard_view_has_four_action_filters(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);

        foreach (['BELUM_DIPERIKSA', 'ADA_GEJALA', 'SIAP_DIPUPUK', 'DITUNDA'] as $status) {
            $response->assertSee('data-status="'.$status.'"', false);
        }

        foreach (['Darurat', 'Segera', 'Normal', 'Tunda', 'GEJALA_BERAT', 'TERINDIKASI_DEFISIENSI', 'NORMAL_VISUAL'] as $legacy) {
            $response->assertDontSee('data-status="'.$legacy.'"', false);
        }
    }

    public function test_dashboard_legend_uses_public_action_language(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Belum Diperiksa', false);
        $response->assertSee('Ditemukan Gejala', false);
        $response->assertSee('Siap Dipupuk', false);
        $response->assertSee('Belum Saatnya Dipupuk', false);
        $response->assertDontSee('Indikasi Visual N/K', false);
    }

    public function test_dashboard_javascript_filters_by_action_status(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $content = $response->getContent();

        $this->assertStringNotContainsString("b.status_rbs||'Belum Dianalisis'", $content);
        $this->assertStringContainsString("b.status_peta||'BELUM_DIPERIKSA'", $content);
        $this->assertStringContainsString("getColorStatusPeta(blok.status_peta||'BELUM_DIPERIKSA')", $content);
        $this->assertStringContainsString("var activeStatuses = ['BELUM_DIPERIKSA', 'ADA_GEJALA', 'SIAP_DIPUPUK', 'DITUNDA'];", $content);
    }
}
