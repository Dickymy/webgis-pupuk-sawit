<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_passes_on_clean_database(): void
    {
        $this->artisan('sawit:health-check', ['--dry-run' => true])
            ->assertSuccessful();
    }

    public function test_health_check_detects_duplicate_latest_recommendation(): void
    {
        $blok = BlokLahan::factory()->create();
        $admin = Admin::factory()->create();

        // Create two "latest" recommendations for same blok using factory
        RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'admin_id' => $admin->id,
            'tanggal_analisis' => now(),
            'is_latest' => true,
        ]);

        RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'admin_id' => $admin->id,
            'tanggal_analisis' => now()->subDay(),
            'is_latest' => true,
        ]);

        $this->artisan('sawit:health-check', ['--dry-run' => true])
            ->assertFailed();
    }
}
