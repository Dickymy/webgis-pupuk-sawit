<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\CurrentApplicationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test kelayakan pencatatan realisasi (Pahan v2.7 — 4.1).
 *
 * Form hanya dibuka jika eligible. URL create langsung tetap ditolak.
 */
class RealisasiEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private BlokLahan $blok;

    private RekomendasiRbs $rekomendasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $anggota = Anggota::create(['nama' => 'Petani', 'alamat' => 'Desa', 'no_hp' => '08123']);
        $this->blok = BlokLahan::create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Elig',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2016,
            'topografi' => 'Datar 0-15°',
            'fase_tanaman' => 'TM',
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
        ]);
    }

    private function createKondisi(float $curahHujan = 150): KondisiLahan
    {
        return KondisiLahan::create([
            'blok_lahan_id' => $this->blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => $curahHujan,
        ]);
    }

    private function createRekomendasi(KondisiLahan $kondisi, string $statusStage = CurrentApplicationCalculator::TAHAP_1_SIAP): RekomendasiRbs
    {
        return RekomendasiRbs::create([
            'blok_lahan_id' => $this->blok->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'rules_terpicu' => [],
            'masalah_teridentifikasi' => [],
            'rekomendasi_pupuk' => [],
            'saran_tindakan_utama' => 'Test.',
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'urea_aplikasi_saat_ini' => 272.0,
            'kcl_aplikasi_saat_ini' => 340.0,
            'active_stage' => 1,
            'status_stage' => $statusStage,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'versi_mesin_rekomendasi' => 'pahan-v2.7',
        ]);
    }

    /** Form ditolak saat status MENUNGGU_KELAYAKAN (curah hujan rendah) */
    public function test_form_rejected_when_not_eligible_low_rainfall(): void
    {
        $kondisi = $this->createKondisi(50); // Curah hujan < 100 = TUNDA
        $rekomendasi = $this->createRekomendasi($kondisi);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $rekomendasi));

        $response->assertRedirect(route('rbs.detail', $this->blok));
        $response->assertSessionHas('error');
    }

    /** Form diizinkan saat Tahap 1 Siap dan curah hujan layak */
    public function test_form_allowed_when_eligible(): void
    {
        $kondisi = $this->createKondisi(150);
        $rekomendasi = $this->createRekomendasi($kondisi);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $rekomendasi));

        $response->assertStatus(200);
        $response->assertSee('Tahap 1');
    }

    /** Store ditolak saat tidak layak (akses URL langsung) */
    public function test_store_rejected_when_not_eligible(): void
    {
        $kondisi = $this->createKondisi(50);
        $rekomendasi = $this->createRekomendasi($kondisi);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('realisasi-pemupukan.store'), [
                'rekomendasi_rbs_id' => $rekomendasi->id,
                'tanggal_realisasi' => now()->toDateString(),
                'urea_realisasi_kg' => 272.0,
                'kcl_realisasi_kg' => 340.0,
                'status_realisasi' => 'SELESAI',
            ]);

        $response->assertRedirect(route('rbs.detail', $this->blok));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('realisasi_pemupukans', 0);
    }

    /** Form ditolak saat program selesai */
    public function test_form_rejected_when_program_selesai(): void
    {
        $kondisi = $this->createKondisi(150);
        $rekomendasi = $this->createRekomendasi($kondisi);

        // Buat realisasi yang menyelesaikan kebutuhan tahunan
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $rekomendasi->id,
            'blok_lahan_id' => $this->blok->id,
            'admin_id' => $this->admin->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(70)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 544.0,
            'kcl_realisasi_kg' => 680.0,
            'status_realisasi' => 'SELESAI',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $rekomendasi));

        $response->assertRedirect(route('rbs.detail', $this->blok));
        $response->assertSessionHas('error');
    }
}
