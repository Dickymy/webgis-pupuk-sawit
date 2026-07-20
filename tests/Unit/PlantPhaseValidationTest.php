<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\PlantPhaseResolver;
use Tests\TestCase;

/**
 * Test validasi fase dan umur tanaman.
 */
class PlantPhaseValidationTest extends TestCase
{
    private PlantPhaseResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PlantPhaseResolver;
    }

    private function makeBlok(array $attrs = []): BlokLahan
    {
        $blok = new BlokLahan;
        $blok->tahun_tanam = $attrs['tahun_tanam'] ?? null;
        $blok->fase_tanaman = $attrs['fase_tanaman'] ?? null;

        return $blok;
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. Umur 2 + TM DITOLAK (conflict)
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_2_dengan_tm_ditolak(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 2, // umur 2
            'fase_tanaman' => 'TM',
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertTrue($result['phase_conflict']);
        $this->assertNull($result['fase']);
        $this->assertTrue($result['needs_verification']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. Umur 10 + TBM DITOLAK (conflict)
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_10_dengan_tbm_ditolak(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 10, // umur 10
            'fase_tanaman' => 'TBM',
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertTrue($result['phase_conflict']);
        $this->assertNull($result['fase']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. Umur 3 + TBM DITERIMA
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_3_dengan_tbm_diterima(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 3, // umur 3
            'fase_tanaman' => 'TBM',
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertFalse($result['phase_conflict']);
        $this->assertEquals('TBM', $result['fase']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. Umur 3 + TM DITERIMA
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_3_dengan_tm_diterima(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 3, // umur 3
            'fase_tanaman' => 'TM',
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertFalse($result['phase_conflict']);
        $this->assertEquals('TM', $result['fase']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. Umur 3 TANPA fase → perlu verifikasi
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_3_tanpa_fase_perlu_verifikasi(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 3, // umur 3
            'fase_tanaman' => null,
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertTrue($result['needs_verification']);
        $this->assertNull($result['fase']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. Umur < 3 auto TBM
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_kurang_3_auto_tbm(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 1, // umur 1
            'fase_tanaman' => null,
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertEquals('TBM', $result['fase']);
        $this->assertFalse($result['needs_verification']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. Umur > 3 auto TM
    // ═══════════════════════════════════════════════════════════════

    public function test_umur_lebih_3_auto_tm(): void
    {
        $blok = $this->makeBlok([
            'tahun_tanam' => now()->year - 8, // umur 8
            'fase_tanaman' => null,
        ]);

        $result = $this->resolver->resolve($blok);
        $this->assertEquals('TM', $result['fase']);
        $this->assertFalse($result['needs_verification']);
    }

    // ═══════════════════════════════════════════════════════════════
    // 8. detectPhaseConflict standalone
    // ═══════════════════════════════════════════════════════════════

    public function test_detect_phase_conflict_umur_1_tm(): void
    {
        $result = $this->resolver->detectPhaseConflict('TM', 1);
        $this->assertNotNull($result); // ada konflik
    }

    public function test_detect_phase_conflict_umur_15_tbm(): void
    {
        $result = $this->resolver->detectPhaseConflict('TBM', 15);
        $this->assertNotNull($result); // ada konflik
    }

    public function test_detect_no_conflict_umur_3_tbm(): void
    {
        $result = $this->resolver->detectPhaseConflict('TBM', 3);
        $this->assertNull($result); // tidak ada konflik
    }

    public function test_detect_no_conflict_umur_3_tm(): void
    {
        $result = $this->resolver->detectPhaseConflict('TM', 3);
        $this->assertNull($result); // tidak ada konflik
    }
}
