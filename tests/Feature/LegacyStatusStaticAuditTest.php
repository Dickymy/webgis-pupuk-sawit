<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Test static audit: status legacy (Darurat/Segera/Normal/Tunda) tidak digunakan
 * untuk keputusan utama pada views, controllers, dan services (Pahan v2.7 — 4.9).
 *
 * Penggunaan yang diizinkan:
 * - Mapping/label status (getLabelStatusAttribute, labelStatus, warna_badge)
 * - Filter dan statistik di index views
 * - Histori rule lama dan detail teknis
 * - Pengujian (test files)
 * - Seeder dan command migrasi
 */
class LegacyStatusStaticAuditTest extends TestCase
{
    /**
     * Controller RealisasiPemupukan tidak boleh menggunakan status_kebutuhan_dominan
     * untuk keputusan logika utama (bukan filter/display).
     */
    public function test_realisasi_controller_no_legacy_status_decision(): void
    {
        $file = app_path('Http/Controllers/RealisasiPemupukanController.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        // Controller realisasi tidak boleh mengandung status legacy untuk keputusan
        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content,
            'RealisasiPemupukanController tidak boleh menggunakan status_kebutuhan_dominan');
    }

    /**
     * RealisasiEligibilityService tidak boleh menggunakan status legacy.
     */
    public function test_eligibility_service_no_legacy_status(): void
    {
        $file = app_path('Services/RealisasiEligibilityService.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content);
        $this->assertStringNotContainsString("'Darurat'", $content);
        $this->assertStringNotContainsString("'Segera'", $content);
        $this->assertStringNotContainsString("'Tunda'", $content);
    }

    /**
     * FertilizationRealizationService tidak menggunakan status legacy.
     */
    public function test_realization_service_no_legacy_status(): void
    {
        $file = app_path('Services/FertilizationRealizationService.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content);
        $this->assertStringNotContainsString("'Darurat'", $content);
    }

    /**
     * CurrentApplicationCalculator tidak menggunakan status legacy.
     */
    public function test_current_app_calculator_no_legacy_status(): void
    {
        $file = app_path('Services/CurrentApplicationCalculator.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content);
        $this->assertStringNotContainsString("'Darurat'", $content);
    }

    /**
     * Form realisasi (create.blade.php) tidak menggunakan status legacy untuk tampilan.
     */
    public function test_realisasi_create_form_no_legacy_status(): void
    {
        $file = resource_path('views/realisasi_pemupukan/create.blade.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content);
    }

    /**
     * Controller RealisasiPemupukan tidak mengandung asumsi 'layak' => true.
     */
    public function test_no_fake_layak_assumption(): void
    {
        $file = app_path('Http/Controllers/RealisasiPemupukanController.php');
        $content = File::get($file);

        $this->assertStringNotContainsString("'layak' => true", $content,
            'Controller tidak boleh mengandung asumsi layak = true palsu');
    }

    /**
     * Laporan show view tidak menggunakan status_kebutuhan_dominan untuk warna utama.
     */
    public function test_laporan_show_no_legacy_for_main_decisions(): void
    {
        $file = resource_path('views/laporan/show.blade.php');
        $this->assertFileExists($file);

        $content = File::get($file);

        // Banner harus menggunakan status_kondisi_tanaman, bukan status_kebutuhan_dominan
        $this->assertStringContainsString('status_kondisi_tanaman', $content,
            'Laporan show harus menggunakan status_kondisi_tanaman untuk banner');
    }

    public function test_laporan_index_no_legacy_status_decision(): void
    {
        $file = resource_path('views/laporan/index.blade.php');
        $this->assertFileExists($file);

        $content = File::get($file);
        $this->assertStringNotContainsString('status_kebutuhan_dominan', $content);
        $this->assertStringContainsString('urea_aplikasi_saat_ini', $content);
        $this->assertStringContainsString('kcl_aplikasi_saat_ini', $content);
    }
}
