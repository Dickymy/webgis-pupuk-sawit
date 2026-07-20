<?php

namespace Tests\Unit;

use App\Models\BlokLahan;
use App\Services\PlantPhaseResolver;
use Tests\TestCase;

class PlantPhaseResolverTest extends TestCase
{
    private PlantPhaseResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PlantPhaseResolver();
    }

    private function makeBlok(array $attrs = []): BlokLahan
    {
        $blok = new BlokLahan();
        $blok->tahun_tanam = $attrs['tahun_tanam'] ?? (now()->year - 10);
        $blok->fase_tanaman = $attrs['fase_tanaman'] ?? null;
        return $blok;
    }

    public function test_manual_fase_is_used_when_set(): void
    {
        // Umur 3 + TBM = valid (tidak ada konflik)
        $blok = $this->makeBlok(['fase_tanaman' => 'TBM', 'tahun_tanam' => now()->year - 3]);
        $result = $this->resolver->resolve($blok);

        $this->assertEquals('TBM', $result['fase']);
        $this->assertTrue($result['verified']);
        $this->assertFalse($result['needs_verification']);
        $this->assertFalse($result['phase_conflict']);
    }

    public function test_manual_fase_rejected_when_conflict(): void
    {
        // Umur 10 + TBM = KONFLIK — fase ditolak
        $blok = $this->makeBlok(['fase_tanaman' => 'TBM', 'tahun_tanam' => now()->year - 10]);
        $result = $this->resolver->resolve($blok);

        $this->assertNull($result['fase']);
        $this->assertTrue($result['phase_conflict']);
        $this->assertTrue($result['needs_verification']);
    }

    public function test_umur_kurang_3_auto_tbm(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 2]);
        $result = $this->resolver->resolve($blok);

        $this->assertEquals('TBM', $result['fase']);
        $this->assertFalse($result['needs_verification']);
    }

    public function test_umur_tepat_3_needs_verification(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 3]);
        $result = $this->resolver->resolve($blok);

        $this->assertNull($result['fase']);
        $this->assertTrue($result['needs_verification']);
    }

    public function test_umur_lebih_3_auto_tm(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 5]);
        $result = $this->resolver->resolve($blok);

        $this->assertEquals('TM', $result['fase']);
        $this->assertFalse($result['needs_verification']);
    }

    public function test_tahun_tanam_null_needs_verification(): void
    {
        $blok = new BlokLahan();
        $blok->tahun_tanam = null;
        $blok->fase_tanaman = null;

        $result = $this->resolver->resolve($blok);

        $this->assertNull($result['fase']);
        $this->assertTrue($result['needs_verification']);
    }

    public function test_effective_phase_returns_fase_for_verified(): void
    {
        $blok = $this->makeBlok(['fase_tanaman' => 'TM']);
        $result = $this->resolver->getEffectivePhase($blok);

        $this->assertEquals('TM', $result);
    }

    public function test_effective_phase_null_for_umur_3(): void
    {
        $blok = $this->makeBlok(['tahun_tanam' => now()->year - 3]);
        $result = $this->resolver->getEffectivePhase($blok);

        $this->assertNull($result);
    }
}
