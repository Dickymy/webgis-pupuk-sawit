<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlokLahanFaseTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Anggota $anggota;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->anggota = Anggota::create(['nama' => 'Test Anggota', 'no_hp' => '081234567890', 'alamat' => 'Test']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'anggota_id' => $this->anggota->id,
            'nama_blok' => 'Blok Test',
            'luas_ha' => 2.5,
            'sph' => 136,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]]]),
            'tahun_tanam' => now()->year - 5,
            'jenis_tanah' => 'Tanah Lempung',
            'topografi' => 'Datar 0-15°',
        ], $overrides);
    }

    public function test_umur_2_auto_saves_tbm(): void
    {
        $payload = $this->validPayload(['tahun_tanam' => now()->year - 2]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('blok-lahan.store'), $payload);

        $blok = BlokLahan::latest()->first();
        $this->assertEquals('TBM', $blok->fase_tanaman);
    }

    public function test_umur_10_auto_saves_tm(): void
    {
        $payload = $this->validPayload(['tahun_tanam' => now()->year - 10]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('blok-lahan.store'), $payload);

        $blok = BlokLahan::latest()->first();
        $this->assertEquals('TM', $blok->fase_tanaman);
    }

    public function test_umur_3_without_fase_rejected(): void
    {
        $payload = $this->validPayload(['tahun_tanam' => now()->year - 3]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('blok-lahan.store'), $payload);

        $response->assertSessionHasErrors('fase_tanaman');
    }

    public function test_umur_3_with_fase_accepted(): void
    {
        $payload = $this->validPayload([
            'tahun_tanam' => now()->year - 3,
            'fase_tanaman' => 'TM',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('blok-lahan.store'), $payload);

        $blok = BlokLahan::latest()->first();
        $this->assertEquals('TM', $blok->fase_tanaman);
    }

    public function test_invalid_geojson_rejected(): void
    {
        $payload = $this->validPayload(['koordinat_geojson' => 'not valid json']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('blok-lahan.store'), $payload);

        $response->assertSessionHasErrors('koordinat_geojson');
    }

    public function test_unauthenticated_redirects_to_login(): void
    {
        $response = $this->post(route('blok-lahan.store'), $this->validPayload());
        $response->assertRedirect(route('login'));
    }
}
