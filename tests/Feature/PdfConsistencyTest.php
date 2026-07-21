<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_route_returns_pdf(): void
    {
        $admin = Admin::factory()->create();
        $blok = BlokLahan::factory()->create([
            'luas_ha' => 4.0,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'fase_tanaman' => 'TM',
        ]);

        $rbs = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'admin_id' => $admin->id,
            'tanggal_analisis' => now(),
            'is_latest' => true,
            'jumlah_rule_terpicu' => 2,
            'versi_mesin_rekomendasi' => 'pahan-v2.9',
            'urea_total_estimasi_tahunan' => 200.0,
            'kcl_total_estimasi_tahunan' => 250.0,
            'urea_aplikasi_saat_ini' => 100.0,
            'kcl_aplikasi_saat_ini' => 125.0,
            'luas_ha_snapshot' => 4.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 544,
            'fase_tanaman_snapshot' => 'TM',
            'umur_tanaman_snapshot' => 11,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'status_kondisi_tanaman' => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('laporan.pdf', $rbs));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_does_not_contain_technical_codes(): void
    {
        $pdfView = resource_path('views/laporan/pdf.blade.php');
        if (! file_exists($pdfView)) {
            $this->markTestSkipped('PDF view not found');
        }

        $content = file_get_contents($pdfView);

        $technicalCodes = [
            'TAHAP_1_SIAP',
            'MENUNGGU_INTERVAL',
            'SELESAI_TAHUNAN',
            'LAYAK_DIJADWALKAN',
        ];

        foreach ($technicalCodes as $code) {
            // Check it's not displayed directly (allowed in conditionals)
            $this->assertDoesNotMatchRegularExpression(
                '/>\s*'.preg_quote($code, '/').'\s*</',
                $content,
                "PDF view should not display {$code} directly"
            );
        }
    }

    public function test_pdf_uses_snapshot_fields(): void
    {
        $pdfView = resource_path('views/laporan/pdf.blade.php');
        if (! file_exists($pdfView)) {
            $this->markTestSkipped('PDF view not found');
        }

        $content = file_get_contents($pdfView);

        // Should use snapshot rather than live data
        $this->assertStringContainsString('luas_ha_snapshot', $content);
        $this->assertStringContainsString('sph_snapshot', $content);
    }
}
