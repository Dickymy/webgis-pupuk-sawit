<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapInvalidGeoJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_invalid_geojson(): void
    {
        $admin = Admin::factory()->create();

        BlokLahan::factory()->create([
            'koordinat_geojson' => '{invalid json!!!}',
        ]);

        BlokLahan::factory()->create([
            'koordinat_geojson' => '',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_popup_api_handles_missing_geojson(): void
    {
        $admin = Admin::factory()->create();

        $blok = BlokLahan::factory()->create([
            'koordinat_geojson' => '',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('api.rbs.popup', $blok));

        $response->assertOk();
    }

    public function test_blok_without_polygon_does_not_crash_map(): void
    {
        $admin = Admin::factory()->create();

        // Create multiple bloks: some with GeoJSON, some with empty
        BlokLahan::factory()->create([
            'koordinat_geojson' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[[107.6, -6.9], [107.7, -6.9], [107.7, -7.0], [107.6, -7.0], [107.6, -6.9]]],
            ]),
        ]);
        BlokLahan::factory()->create(['koordinat_geojson' => '']);
        BlokLahan::factory()->create(['koordinat_geojson' => '{}']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('dashboard'));

        $response->assertOk();
        // Page loads without server-side error
        $response->assertDontSee('Terjadi kesalahan');
    }
}
