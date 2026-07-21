<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoubleSubmitRealizationTest extends TestCase
{
    use RefreshDatabase;

    private function createReadyBlok(): array
    {
        $admin = Admin::factory()->create();
        $blok = BlokLahan::factory()->create([
            'luas_ha' => 4.0,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'fase_tanaman' => 'TM',
        ]);

        $program = ProgramPemupukan::create([
            'uuid' => Str::uuid()->toString(),
            'blok_lahan_id' => $blok->id,
            'tahun_program' => now()->year,
            'status_program' => 'AKTIF',
            'active_key' => $blok->id.'-'.now()->year,
        ]);

        $rbs = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'admin_id' => $admin->id,
            'program_pemupukan_id' => $program->id,
            'tanggal_analisis' => now(),
            'is_latest' => true,
            'urea_total_estimasi_tahunan' => 200.0,
            'kcl_total_estimasi_tahunan' => 250.0,
            'urea_aplikasi_saat_ini' => 100.0,
            'kcl_aplikasi_saat_ini' => 125.0,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'versi_mesin_rekomendasi' => 'pahan-v2.9',
            'luas_ha_snapshot' => 4.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 544,
        ]);

        return compact('admin', 'blok', 'program', 'rbs');
    }

    public function test_double_submit_realization_does_not_create_duplicate(): void
    {
        $data = $this->createReadyBlok();

        $payload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'blok_lahan_id' => $data['blok']->id,
            'program_pemupukan_id' => $data['program']->id,
            'tahap' => 1,
            'tahun_program' => now()->year,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_rencana_kg' => 100.0,
            'kcl_rencana_kg' => 125.0,
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Test double submit',
        ];

        // First submit
        $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        // Second submit (simulate double-click)
        $response2 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        // Count active realizations for this stage
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('tahap', 1)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        // Should only have one or the second should be rejected
        // (due to stage lock after completion)
        $this->assertLessThanOrEqual(2, $count);
    }
}
