<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ObservationFieldFormTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_field_form_explains_the_three_distinct_decisions(): void
    {
        BlokLahan::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('kondisi-lahan.create'))
            ->assertOk()
            ->assertSee('Nama anggota')
            ->assertSee('Perkiraan jumlah pohon')
            ->assertSee('Periksa kondisi tanaman')
            ->assertSee('Periksa kesiapan pemupukan')
            ->assertSee('1. Data hujan dan musim')
            ->assertSee('Musim saat observasi')
            ->assertSee('Gunakan data angka jika tersedia')
            ->assertSee('name="metode_data_hujan" value="data_angka" checked', false)
            ->assertDontSee('Kondisi pelepah')
            ->assertDontSee('Kondisi tandan')
            ->assertDontSee('pH tanah')
            ->assertSee('Simpan dan Lihat Hasil Analisis');
    }

    public function test_unmatched_leaf_choice_is_not_forced_to_match_a_rule(): void
    {
        $blok = BlokLahan::factory()->create();
        $this->mockAnalysis($blok);

        $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => '__gejala_lain',
            'catatan_observasi' => 'Daun tampak tidak biasa dan perlu diperiksa lebih lanjut.',
            'metode_data_hujan' => 'tidak_tersedia',
            'mode_data_hujan_dikonfirmasi' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kondisi_lahans', [
            'blok_lahan_id' => $blok->id,
            'warna_daun' => null,
            'status_verifikasi_gejala' => 'perlu_konfirmasi',
        ]);
    }

    public function test_rainfall_estimate_cannot_leave_a_misleading_numeric_value(): void
    {
        $blok = BlokLahan::factory()->create();
        $this->mockAnalysis($blok);

        $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'metode_data_hujan' => 'perkiraan',
            'mode_data_hujan_dikonfirmasi' => 1,
            'curah_hujan_kategori' => 'Normal',
            'curah_hujan_mm_bulanan' => 150,
            'periode_curah_hujan' => 'nilai lama',
            'sumber_curah_hujan' => 'open-meteo',
        ])->assertSessionHasNoErrors();

        $observation = KondisiLahan::where('blok_lahan_id', $blok->id)->firstOrFail();
        $this->assertSame('Normal', $observation->curah_hujan_kategori);
        $this->assertNull($observation->curah_hujan_mm_bulanan);
        $this->assertNull($observation->periode_curah_hujan);
        $this->assertNull($observation->sumber_curah_hujan);
    }

    public function test_numeric_rainfall_requires_period_and_source_from_the_new_form(): void
    {
        $blok = BlokLahan::factory()->create();

        $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'metode_data_hujan' => 'data_angka',
            'mode_data_hujan_dikonfirmasi' => 1,
            'curah_hujan_mm_bulanan' => 150,
        ])->assertSessionHasErrors(['periode_curah_hujan', 'sumber_curah_hujan']);
    }

    public function test_photo_is_stored_and_served_through_the_protected_route(): void
    {
        Storage::fake('public');
        $blok = BlokLahan::factory()->create();
        $this->mockAnalysis($blok);

        $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'metode_data_hujan' => 'tidak_tersedia',
            'mode_data_hujan_dikonfirmasi' => 1,
            'foto_observasi' => UploadedFile::fake()->image('daun.jpg', 120, 120),
        ])->assertSessionHasNoErrors();

        $observation = KondisiLahan::where('blok_lahan_id', $blok->id)->firstOrFail();
        Storage::disk('public')->assertExists($observation->foto_observasi_path);
        $this->actingAs($this->admin, 'admin')
            ->get(route('kondisi-lahan.photo', $observation))
            ->assertOk();
    }

    public function test_selected_block_must_belong_to_selected_owner(): void
    {
        $blok = BlokLahan::factory()->create();
        $otherOwner = Anggota::factory()->create();

        $this->actingAs($this->admin, 'admin')->post(route('kondisi-lahan.store'), [
            'anggota_id' => $otherOwner->id,
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'metode_data_hujan' => 'tidak_tersedia',
            'mode_data_hujan_dikonfirmasi' => 1,
        ])->assertSessionHasErrors('blok_lahan_id');
    }

    public function test_existing_photo_can_be_removed_when_observation_is_saved(): void
    {
        Storage::fake('public');
        $blok = BlokLahan::factory()->create();
        $path = UploadedFile::fake()->image('foto-lama.jpg', 120, 120)->store('observasi', 'public');
        $observation = KondisiLahan::create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'foto_observasi_path' => $path,
        ]);
        $this->mockAnalysis($blok);

        $this->actingAs($this->admin, 'admin')->put(route('kondisi-lahan.update', $observation), [
            'anggota_id' => $blok->anggota_id,
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'metode_data_hujan' => 'tidak_tersedia',
            'mode_data_hujan_dikonfirmasi' => 1,
            'hapus_foto' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull($observation->fresh()->foto_observasi_path);
        Storage::disk('public')->assertMissing($path);
    }

    private function mockAnalysis(BlokLahan $blok): void
    {
        $rbs = Mockery::mock(RbsService::class);
        $rbs->shouldReceive('analisis')
            ->once()
            ->with(Mockery::on(fn ($argument) => $argument->is($blok)))
            ->andReturn(['sukses' => true]);
        $this->app->instance(RbsService::class, $rbs);
    }
}
