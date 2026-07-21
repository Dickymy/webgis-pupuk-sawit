<?php

namespace Tests\Feature;

use App\Models\Anggota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_not_called_from_database_seeder(): void
    {
        $content = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertStringNotContainsString(
            'DemoSawitGisSeeder',
            $content,
            'DemoSawitGisSeeder TIDAK boleh dipanggil dari DatabaseSeeder'
        );
    }

    public function test_demo_seeder_runs_explicitly(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);

        $this->assertDatabaseHas('anggotas', ['nama' => 'DEMO - Pak Hadi Sutrisno']);
        $this->assertDatabaseHas('blok_lahans', ['nama_blok' => 'DEMO - Blok A1 Normal']);
    }

    public function test_demo_data_uses_prefix(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);

        $anggotaTanpaPrefix = Anggota::where('nama', 'not like', 'DEMO -%')->count();
        $this->assertEquals(0, $anggotaTanpaPrefix, 'Semua anggota demo harus menggunakan prefix DEMO -');
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);
        $count1 = Anggota::count();

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSawitGisSeeder']);
        $count2 = Anggota::count();

        $this->assertEquals($count1, $count2, 'Seeder harus idempoten (firstOrCreate)');
    }
}
