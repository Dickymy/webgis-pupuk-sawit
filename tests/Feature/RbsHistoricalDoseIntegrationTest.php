<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbsHistoricalDoseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_analisis_tbm_tahun_ke_2_produces_correct_dose(): void
    {
        $anggota = Anggota::create(['nama' => 'Test', 'alamat' => 'Test', 'no_hp' => '08123']);
        $blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Historis',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2020,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);

        // Observasi tahun 2022 → umur = 2022 - 2020 = 2
        KondisiLahan::create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => '2022-06-15',
            'curah_hujan_mm_bulanan' => 150.0,
            'warna_daun' => 'Hijau Normal',
            'kondisi_pelepah' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $rbsService = app(RbsService::class);
        $result = $rbsService->analisis($blok);

        $this->assertTrue($result['sukses']);
        $rbs = $result['rekomendasi'];

        // Umur snapshot = 2 (berdasarkan tanggal observasi)
        $this->assertEquals(2, $rbs->umur_tanaman_snapshot);
        // Fase = TBM (umur < 3)
        $this->assertEquals('TBM', $rbs->fase_tanaman_snapshot);
        // Dosis harus dari kelompok TBM tahun ke-2
        // Urea: 0.70-0.85, midpoint = 0.775
        $this->assertNotNull($rbs->urea_estimasi_kg_per_pokok_tahun);
        $this->assertGreaterThanOrEqual(0.70, $rbs->urea_estimasi_kg_per_pokok_tahun);
        $this->assertLessThanOrEqual(0.85, $rbs->urea_estimasi_kg_per_pokok_tahun);
        // Total tahunan = dosis × jumlah_pokok
        $expectedPokok = (int) (2.0 * 136);
        $this->assertEquals($expectedPokok, $rbs->jumlah_pokok_snapshot);
        // Luas dan SPH snapshot tersimpan
        $this->assertEquals(2.0, $rbs->luas_ha_snapshot);
        $this->assertEquals(136, $rbs->sph_snapshot);
        // Versi mesin
        $this->assertEquals(config('fertilization.engine_version'), $rbs->versi_mesin_rekomendasi);
        // Aplikasi saat ini = 50% dari tahunan (bukan 100%)
        if ($rbs->urea_total_estimasi_tahunan > 0) {
            $this->assertLessThanOrEqual(
                $rbs->urea_total_estimasi_tahunan * 0.51,
                $rbs->urea_aplikasi_saat_ini
            );
        }
    }
}
