<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use Database\Seeders\RuleBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleSubmitAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_click_analysis_does_not_create_duplicate_recommendation(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $admin = Admin::factory()->create();
        $blok = BlokLahan::factory()->create([
            'luas_ha' => 4.0,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'fase_tanaman' => 'TM',
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->subDays(5),
            'warna_daun' => 'Hijau Normal',
            'kondisi_drainase' => 'Baik',
            'curah_hujan_mm_bulanan' => 180,
            'ph_tanah' => 5.5,
            'tanggal_pemupukan_terakhir' => now()->subDays(150),
        ]);

        // Simulate double-click: two rapid requests
        $response1 = $this->actingAs($admin, 'admin')
            ->post(route('rbs.analisis', $blok));

        $response2 = $this->actingAs($admin, 'admin')
            ->post(route('rbs.analisis', $blok));

        // Both should succeed (redirect)
        $response1->assertRedirect();
        $response2->assertRedirect();

        // But only ONE should be marked as latest
        $latestCount = $blok->rekomendasiRbs()
            ->where('is_latest', true)
            ->count();

        $this->assertEquals(1, $latestCount, 'Hanya satu rekomendasi boleh is_latest per blok');
    }

    public function test_double_click_analysis_does_not_create_duplicate_program(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $admin = Admin::factory()->create();
        $blok = BlokLahan::factory()->create([
            'luas_ha' => 4.0,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'fase_tanaman' => 'TM',
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->subDays(5),
            'warna_daun' => 'Hijau Normal',
            'kondisi_drainase' => 'Baik',
            'curah_hujan_mm_bulanan' => 180,
            'ph_tanah' => 5.5,
            'tanggal_pemupukan_terakhir' => now()->subDays(150),
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('rbs.analisis', $blok));
        $this->actingAs($admin, 'admin')
            ->post(route('rbs.analisis', $blok));

        $programCount = $blok->programPemupukans()
            ->where('status_program', 'AKTIF')
            ->where('tahun_program', now()->year)
            ->count();

        $this->assertEquals(1, $programCount, 'Hanya satu program aktif per blok/tahun');
    }
}
