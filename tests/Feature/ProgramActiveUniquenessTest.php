<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Services\ProgramPemupukanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test: Hanya satu program aktif per blok dan tahun (Pahan v2.8 — 4.4).
 */
class ProgramActiveUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private BlokLahan $blok;

    protected function setUp(): void
    {
        parent::setUp();

        $anggota = Anggota::factory()->create();
        $this->blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'luas_ha' => 2.0,
            'sph' => 143,
        ]);
    }

    public function test_resolve_active_program_creates_one(): void
    {
        $service = app(ProgramPemupukanService::class);

        $program = $service->resolveActiveProgram($this->blok, 2026);

        $this->assertNotNull($program);
        $this->assertEquals(ProgramPemupukan::STATUS_AKTIF, $program->status_program);
        $this->assertEquals($this->blok->id.'-2026', $program->active_key);
    }

    public function test_resolve_active_program_returns_existing(): void
    {
        $service = app(ProgramPemupukanService::class);

        $program1 = $service->resolveActiveProgram($this->blok, 2026);
        $program2 = $service->resolveActiveProgram($this->blok, 2026);

        $this->assertEquals($program1->id, $program2->id);
    }

    public function test_database_constraint_prevents_duplicate_active_key(): void
    {
        $service = app(ProgramPemupukanService::class);
        $service->resolveActiveProgram($this->blok, 2026);

        // Attempt to manually insert duplicate
        $this->expectException(QueryException::class);

        DB::table('program_pemupukans')->insert([
            'uuid' => Str::uuid()->toString(),
            'blok_lahan_id' => $this->blok->id,
            'tahun_program' => 2026,
            'status_program' => 'AKTIF',
            'active_key' => $this->blok->id.'-2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_program_tahun_lama_tidak_masuk_tahun_baru(): void
    {
        $service = app(ProgramPemupukanService::class);

        $program2025 = $service->resolveActiveProgram($this->blok, 2025);
        $program2026 = $service->resolveActiveProgram($this->blok, 2026);

        $this->assertNotEquals($program2025->id, $program2026->id);
        $this->assertEquals(2025, $program2025->tahun_program);
        $this->assertEquals(2026, $program2026->tahun_program);
    }
}
