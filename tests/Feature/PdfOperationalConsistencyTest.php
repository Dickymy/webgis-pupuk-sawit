<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfOperationalConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private RekomendasiRbs $rbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();

        $anggota = Anggota::create(['nama' => 'Test', 'alamat' => 'Test', 'no_hp' => '08123']);
        $blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok PDF',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2020,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);
        $kondisi = KondisiLahan::create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'curah_hujan_mm_bulanan' => 150.0,
        ]);

        $this->rbs = RekomendasiRbs::create([
            'blok_lahan_id' => $blok->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => ['Tidak ada masalah'],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Lanjutkan pemupukan standar',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'status_kondisi_tanaman' => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'urea_aplikasi_saat_ini' => 272.0,
            'kcl_aplikasi_saat_ini' => 340.0,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'urea_sisa_tahunan' => 544.0,
            'kcl_sisa_tahunan' => 680.0,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'versi_mesin_rekomendasi' => 'pahan-v2.5',
        ]);
    }

    public function test_aplikasi_saat_ini_is_not_total_tahunan(): void
    {
        // Aplikasi saat ini (272) TIDAK boleh sama dengan total tahunan (544)
        $this->assertNotEquals(
            $this->rbs->urea_total_estimasi_tahunan,
            $this->rbs->urea_aplikasi_saat_ini
        );
        // Harus 50% atau kurang
        $this->assertLessThanOrEqual(
            $this->rbs->urea_total_estimasi_tahunan * 0.51,
            $this->rbs->urea_aplikasi_saat_ini
        );
    }

    public function test_active_stage_has_correct_value(): void
    {
        $this->assertEquals(1, $this->rbs->active_stage);
        $this->assertEquals('TAHAP_1_SIAP', $this->rbs->status_stage);
    }

    public function test_luas_sph_snapshot_stored(): void
    {
        $this->assertEquals(2.0, $this->rbs->luas_ha_snapshot);
        $this->assertEquals(136, $this->rbs->sph_snapshot);
        $this->assertEquals(272, $this->rbs->jumlah_pokok_snapshot);
    }

    public function test_laporan_show_page_loads(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('laporan.show', $this->rbs));

        $response->assertStatus(200);
    }
}
