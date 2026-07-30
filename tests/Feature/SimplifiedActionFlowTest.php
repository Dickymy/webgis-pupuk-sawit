<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RekomendasiRbs;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SimplifiedActionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_dashboard_cards_open_contextual_queues(): void
    {
        BlokLahan::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('kondisi-lahan.index', ['status' => 'belum']), false);
        $response->assertSee(route('rbs.index', ['status' => 'menunggu-interval']), false);
        $response->assertSee(route('realisasi-pemupukan.index', ['tab' => 'siap']), false);
        $response->assertSee(route('laporan.index', ['status_program' => 'SELESAI', 'tahun_program' => now()->year]));
        $response->assertSee(route('rbs.index', ['status' => 'perlu-tindakan']), false);
    }

    public function test_observation_filter_shows_the_blocks_that_still_need_observation(): void
    {
        $anggota = Anggota::factory()->create();
        $belum = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Belum Observasi',
        ]);
        $sudah = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Sudah Observasi',
        ]);
        KondisiLahan::factory()->create(['blok_lahan_id' => $sudah->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('kondisi-lahan.index', ['status' => 'belum']));

        $response->assertOk();
        $response->assertSee($belum->nama_blok);
        $response->assertDontSee($sudah->nama_blok);
        $response->assertViewHas('stats', fn ($stats) => $stats['belum'] === 1 && $stats['sudah'] === 1);
    }

    public function test_priority_filter_keeps_only_blocks_with_a_next_action(): void
    {
        $anggota = Anggota::factory()->create();
        $perluTindakan = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Perlu Tindakan',
        ]);
        $selesai = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'nama_blok' => 'Blok Program Selesai',
        ]);
        $kondisi = KondisiLahan::factory()->create(['blok_lahan_id' => $selesai->id]);
        RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $selesai->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'status_stage' => 'SELESAI_TAHUNAN',
            'urea_aplikasi_saat_ini' => 0,
            'kcl_aplikasi_saat_ini' => 0,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('rbs.index', ['status' => 'perlu-tindakan']));

        $response->assertOk();
        $response->assertSee($perluTindakan->nama_blok);
        $response->assertDontSee($selesai->nama_blok);
    }

    public function test_saving_an_observation_runs_rbs_and_redirects_to_the_block_result(): void
    {
        $blok = BlokLahan::factory()->create();
        $rbs = Mockery::mock(RbsService::class);
        $rbs->shouldReceive('analisis')
            ->once()
            ->with(Mockery::on(fn ($argument) => $argument->is($blok)))
            ->andReturn(['sukses' => true]);
        $this->app->instance(RbsService::class, $rbs);

        $response = $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $response->assertRedirect(route('rbs.detail', $blok));
        $this->assertDatabaseHas('kondisi_lahans', [
            'blok_lahan_id' => $blok->id,
            'warna_daun' => 'Hijau Normal',
        ]);
    }

    public function test_incomplete_recommendation_returns_to_observation_instead_of_realization(): void
    {
        $blok = BlokLahan::factory()->create(['nama_blok' => 'Blok Data Belum Lengkap']);
        $kondisi = KondisiLahan::factory()->create(['blok_lahan_id' => $blok->id]);
        $rekomendasi = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'kondisi_lahan_id' => $kondisi->id,
            'admin_id' => $this->admin->id,
            'data_cukup' => false,
            'status_kondisi_tanaman' => 'PERLU_VERIFIKASI',
            'status_kelayakan_aplikasi' => 'PERLU_VERIFIKASI_DATA',
        ]);

        $createResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('realisasi-pemupukan.create', $rekomendasi));

        $createResponse->assertRedirect(route('rbs.detail', $blok));
        $createResponse->assertSessionHas('error');

        $queueResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('rbs.index', ['status' => 'perlu-rekomendasi']));

        $queueResponse->assertOk();
        $queueResponse->assertSee($blok->nama_blok);
        $queueResponse->assertSee('Lengkapi Observasi');
    }

    public function test_realisasi_status_is_automatic_in_the_form(): void
    {
        $view = file_get_contents(resource_path('views/realisasi_pemupukan/create.blade.php'));

        $this->assertStringContainsString('type="hidden" name="status_realisasi"', $view);
        $this->assertStringContainsString('Ditentukan otomatis dari jumlah aktual', $view);
        $this->assertStringNotContainsString('<select name="status_realisasi"', $view);
    }
}
