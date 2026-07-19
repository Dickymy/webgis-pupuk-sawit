<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\PahanDoseReferenceService;
use App\Services\PlantPhaseResolver;
use Tests\TestCase;

class PahanDoseReferenceTest extends TestCase
{
    private PahanDoseReferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PahanDoseReferenceService::class);
    }

    private function makeBlok(array $attrs = []): BlokLahan
    {
        $blok = new BlokLahan();
        $blok->luas_ha = $attrs['luas_ha'] ?? 5.0;
        $blok->sph = $attrs['sph'] ?? 136;
        $blok->tahun_tanam = $attrs['tahun_tanam'] ?? (now()->year - 10);
        $blok->fase_tanaman = $attrs['fase_tanaman'] ?? null;
        return $blok;
    }

    // ═══════════════════════════════════════════════════════════════
    // 15.1 Referensi Dosis
    // ═══════════════════════════════════════════════════════════════

    public function test_tbm_tahun_ke_1(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 1, 'fase_tanaman' => 'TBM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('TBM', $result['phase']);
        $this->assertEquals(0.50, $result['urea']['min']);
        $this->assertEquals(0.70, $result['urea']['max']);
        $this->assertEquals(0.75, $result['kcl']['min']);
        $this->assertEquals(1.25, $result['kcl']['max']);
    }

    public function test_tbm_tahun_ke_2(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 2, 'fase_tanaman' => 'TBM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('TBM', $result['phase']);
        $this->assertEquals(0.70, $result['urea']['min']);
        $this->assertEquals(0.85, $result['urea']['max']);
        $this->assertEquals(1.00, $result['kcl']['min']);
        $this->assertEquals(1.75, $result['kcl']['max']);
    }

    public function test_tbm_tahun_ke_3(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 3, 'fase_tanaman' => 'TBM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('TBM', $result['phase']);
        $this->assertEquals(0.90, $result['urea']['min']);
        $this->assertEquals(1.25, $result['urea']['max']);
        $this->assertEquals(1.20, $result['kcl']['min']);
        $this->assertEquals(2.25, $result['kcl']['max']);
    }

    public function test_tm_umur_3_tahun(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 3, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('TM', $result['phase']);
        $this->assertEquals('3-5', $result['age_group']);
        $this->assertEquals(0.90, $result['urea']['min']);
        $this->assertEquals(1.75, $result['urea']['max']);
    }

    public function test_tm_umur_5_tahun(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 5, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('TM', $result['phase']);
        $this->assertEquals('3-5', $result['age_group']);
        $this->assertEquals(1.20, $result['kcl']['min']);
        $this->assertEquals(2.50, $result['kcl']['max']);
    }

    public function test_tm_umur_6_tahun(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 6, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('6-15', $result['age_group']);
        $this->assertEquals(1.00, $result['urea']['min']);
        $this->assertEquals(3.00, $result['urea']['max']);
        $this->assertEquals(1.50, $result['kcl']['min']);
        $this->assertEquals(3.50, $result['kcl']['max']);
    }

    public function test_tm_umur_15_tahun(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 15, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('6-15', $result['age_group']);
        $this->assertEquals(1.00, $result['urea']['min']);
        $this->assertEquals(3.00, $result['urea']['max']);
    }

    public function test_tm_umur_16_tahun(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 16, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        $this->assertEquals('16+', $result['age_group']);
        $this->assertEquals(1.50, $result['urea']['min']);
        $this->assertEquals(2.50, $result['urea']['max']);
        $this->assertEquals(1.50, $result['kcl']['min']);
        $this->assertEquals(2.25, $result['kcl']['max']);
    }

    public function test_umur_3_tahun_tanpa_fase_perlu_verifikasi(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 3, 'fase_tanaman' => null]);
        $result = $this->service->getDoseReference($blok);

        $this->assertTrue($result['needs_phase_verification']);
        $this->assertNull($result['phase']);
    }

    public function test_estimasi_midpoint_benar(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 10, 'fase_tanaman' => 'TM']);
        $result = $this->service->getDoseReference($blok);

        // 6-15 range: urea 1.00-3.00, midpoint = 2.00
        $this->assertEquals(2.00, $result['urea']['estimate']);
        // kcl 1.50-3.50, midpoint = 2.50
        $this->assertEquals(2.50, $result['kcl']['estimate']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 15.2 Curah Hujan
    // ═══════════════════════════════════════════════════════════════

    public function test_curah_hujan_99_mm_tunda(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 99;
        $kondisi->tanggal_pemupukan_terakhir = null;
        $kondisi->kondisi_drainase = 'Baik';
        $kondisi->curah_hujan_kategori = null;

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertFalse($result['layak']);
        $this->assertEquals('TUNDA_HUJAN_RENDAH', $result['status']);
    }

    public function test_curah_hujan_100_mm_layak(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 100;
        $kondisi->tanggal_pemupukan_terakhir = null;
        $kondisi->kondisi_drainase = 'Baik';
        $kondisi->curah_hujan_kategori = null;

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertTrue($result['layak']);
    }

    public function test_curah_hujan_250_mm_layak(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 250;
        $kondisi->tanggal_pemupukan_terakhir = null;
        $kondisi->kondisi_drainase = 'Baik';
        $kondisi->curah_hujan_kategori = null;

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertTrue($result['layak']);
    }

    public function test_curah_hujan_251_mm_tunda(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 251;
        $kondisi->tanggal_pemupukan_terakhir = null;
        $kondisi->kondisi_drainase = 'Baik';
        $kondisi->curah_hujan_kategori = null;

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertFalse($result['layak']);
        $this->assertEquals('TUNDA_HUJAN_TINGGI', $result['status']);
    }

    public function test_curah_hujan_kosong_perlu_verifikasi(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = null;
        $kondisi->curah_hujan_kategori = null;
        $kondisi->tanggal_pemupukan_terakhir = null;
        $kondisi->kondisi_drainase = 'Baik';

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertEquals('PERLU_VERIFIKASI_DATA', $result['status']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 15.3 Interval
    // ═══════════════════════════════════════════════════════════════

    public function test_interval_59_hari_tunda(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 150;
        $kondisi->curah_hujan_kategori = null;
        $kondisi->tanggal_pemupukan_terakhir = now()->subDays(59);
        $kondisi->kondisi_drainase = 'Baik';

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertFalse($result['layak']);
        $this->assertEquals('TUNDA_INTERVAL', $result['status']);
    }

    public function test_interval_60_hari_layak(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 150;
        $kondisi->curah_hujan_kategori = null;
        $kondisi->tanggal_pemupukan_terakhir = now()->subDays(60);
        $kondisi->kondisi_drainase = 'Baik';

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        $this->assertTrue($result['layak']);
    }

    public function test_interval_lebih_120_hari_terlambat_tanpa_multiplier(): void
    {
        $kondisi = new \App\Models\KondisiLahan();
        $kondisi->curah_hujan_mm_bulanan = 150;
        $kondisi->curah_hujan_kategori = null;
        $kondisi->tanggal_pemupukan_terakhir = now()->subDays(130);
        $kondisi->kondisi_drainase = 'Baik';

        $windowService = app(\App\Services\FertilizationWindowService::class);
        $result = $windowService->evaluate($kondisi);

        // Masih layak (terlambat tapi layak dijadwalkan)
        $this->assertTrue($result['layak']);
        $this->assertTrue($result['terlambat']);
        // Status = TERLAMBAT_PERLU_DIJADWALKAN, bukan peningkatan dosis
        $this->assertEquals('TERLAMBAT_PERLU_DIJADWALKAN', $result['status']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 15.4 Perhitungan
    // ═══════════════════════════════════════════════════════════════

    public function test_jumlah_pokok_luas_kali_sph(): void
    {
        $blok = $this->makeBlok(['luas_ha' => 5.0, 'sph' => 136, 'tahun_tanam' => now()->year - 10, 'fase_tanaman' => 'TM']);
        $doseRef = $this->service->getDoseReference($blok);
        $calcService = app(\App\Services\FertilizationCalculationService::class);
        $result = $calcService->calculate($blok, $doseRef);

        $this->assertEquals(680, $result['jumlah_pokok']); // 5 * 136
    }

    public function test_total_min_max_estimasi_benar(): void
    {
        $blok = $this->makeBlok(['luas_ha' => 5.0, 'sph' => 136, 'tahun_tanam' => now()->year - 10, 'fase_tanaman' => 'TM']);
        $doseRef = $this->service->getDoseReference($blok);
        $calcService = app(\App\Services\FertilizationCalculationService::class);
        $result = $calcService->calculate($blok, $doseRef);

        // 6-15 range: urea 1.00-3.00, midpoint 2.00
        // 680 pokok
        $this->assertEquals(680.0, $result['urea']['min_total']); // 1.00 * 680
        $this->assertEquals(2040.0, $result['urea']['max_total']); // 3.00 * 680
        $this->assertEquals(1360.0, $result['urea']['est_total']); // 2.00 * 680
    }

    public function test_karung_50_kg_benar(): void
    {
        $blok = $this->makeBlok(['luas_ha' => 5.0, 'sph' => 136, 'tahun_tanam' => now()->year - 10, 'fase_tanaman' => 'TM']);
        $doseRef = $this->service->getDoseReference($blok);
        $calcService = app(\App\Services\FertilizationCalculationService::class);
        $result = $calcService->calculate($blok, $doseRef);

        // 1360 kg / 50 = 27.2 → bulat 28
        $this->assertEquals(27.2, $result['urea']['karung_est']);
        $this->assertEquals(28, $result['urea']['karung_bulat']);
    }

    public function test_tidak_ada_multiplier_tanah_topografi_waktu(): void
    {
        // Dua blok dengan tanah/topografi berbeda harus menghasilkan dosis SAMA
        $blok1 = $this->makeBlok([
            'luas_ha' => 5.0, 'sph' => 136,
            'tahun_tanam' => now()->year - 10,
            'fase_tanaman' => 'TM',
        ]);
        $blok1->jenis_tanah = 'Tanah Berpasir';
        $blok1->topografi = 'Curam >30°';

        $blok2 = $this->makeBlok([
            'luas_ha' => 5.0, 'sph' => 136,
            'tahun_tanam' => now()->year - 10,
            'fase_tanaman' => 'TM',
        ]);
        $blok2->jenis_tanah = 'Tanah Liat';
        $blok2->topografi = 'Datar 0-15°';

        $ref1 = $this->service->getDoseReference($blok1);
        $ref2 = $this->service->getDoseReference($blok2);

        // Dosis harus sama karena multiplier dinonaktifkan
        $this->assertEquals($ref1['urea']['estimate'], $ref2['urea']['estimate']);
        $this->assertEquals($ref1['kcl']['estimate'], $ref2['kcl']['estimate']);
    }
}
