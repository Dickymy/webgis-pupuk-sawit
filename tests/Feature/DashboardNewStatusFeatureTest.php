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

    public function test_dashboard_view_has_no_legacy_filter_buttons(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);

        // Legacy status TIDAK boleh ada sebagai data-status pada filter button
        $response->assertDontSee('data-status="Darurat"', false);
        $response->assertDontSee('data-status="Segera"', false);
        $response->assertDontSee('data-status="Normal"', false);
        $response->assertDontSee('data-status="Tunda"', false);

        // Status baru HARUS ada
        $response->assertSee('data-status="GEJALA_BERAT"', false);
        $response->assertSee('data-status="TERINDIKASI_DEFISIENSI"', false);
        $response->assertSee('data-status="TERINDIKASI_DEFISIENSI_RINGAN"', false);
        $response->assertSee('data-status="NORMAL_VISUAL"', false);
        $response->assertSee('data-status="BELUM_DIOBSERVASI"', false);

        // Verifikasi TIDAK boleh ada sebagai filter button
        $response->assertDontSee('data-status="PERLU_VERIFIKASI"', false);
    }

    public function test_dashboard_legend_has_all_new_statuses(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Gejala Berat', false);
        $response->assertSee('Terindikasi Defisiensi', false);
        $response->assertSee('Defisiensi Ringan', false);
        $response->assertSee('Normal Visual', false);
        $response->assertSee('Belum Diobservasi', false);

        // Verifikasi TIDAK boleh muncul di legend
        $response->assertDontSee('Perlu Verifikasi', false);
    }

    public function test_dashboard_javascript_uses_status_kondisi_not_status_rbs(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));
        $content = $response->getContent();

        // JavaScript TIDAK boleh menggunakan status_rbs sebagai filter
        $this->assertStringNotContainsString("b.status_rbs||'Belum Dianalisis'", $content);
        $this->assertStringNotContainsString("activeStatuses = ['Darurat'", $content);

        // JavaScript HARUS menggunakan status_kondisi
        $this->assertStringContainsString("b.status_kondisi||'BELUM_DIOBSERVASI'", $content);
        $this->assertStringContainsString("'GEJALA_BERAT'", $content);
    }
}
