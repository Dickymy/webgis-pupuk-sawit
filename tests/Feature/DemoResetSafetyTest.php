<?php

namespace Tests\Feature;

use App\Models\Anggota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoResetSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_demo_only_deletes_demo_data(): void
    {
        // Create non-demo data
        Anggota::create(['nama' => 'Petani Asli']);

        // Create demo data
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);

        // Run reset with --force and auto-confirm
        $this->artisan('sawit:reset-demo-data', ['--force' => true])
            ->expectsConfirmation('Yakin ingin menghapus semua data demo di atas?', 'yes')
            ->assertSuccessful();

        // Demo data should be gone
        $this->assertDatabaseMissing('anggotas', ['nama' => 'DEMO - Pak Hadi Sutrisno']);

        // Non-demo data should remain
        $this->assertDatabaseHas('anggotas', ['nama' => 'Petani Asli']);
    }

    public function test_reset_demo_dry_run_does_not_delete(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);

        $this->artisan('sawit:reset-demo-data', ['--dry-run' => true, '--force' => true])
            ->assertSuccessful();

        // Data should still exist
        $this->assertDatabaseHas('anggotas', ['nama' => 'DEMO - Pak Hadi Sutrisno']);
    }

    public function test_reset_demo_blocked_in_production_without_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('sawit:reset-demo-data')
            ->assertFailed();
    }
}
