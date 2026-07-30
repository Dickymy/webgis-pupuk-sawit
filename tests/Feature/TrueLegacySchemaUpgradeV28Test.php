<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LegacySchemaBuilder;
use Tests\TestCase;

/**
 * True Legacy Schema Upgrade Test (Pahan v2.8 — 4.10).
 */
class TrueLegacySchemaUpgradeV28Test extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_fresh_and_legacy_data_survives(): void
    {
        // Database sudah fresh via RefreshDatabase trait
        LegacySchemaBuilder::insertLegacyData();

        // Verify data utuh
        $issues = LegacySchemaBuilder::verifyDataIntegrity();
        $this->assertEmpty($issues, 'Data integrity issues: '.implode(', ', $issues));

        // Verify v2.7+ schema ada (tabel utama)
        $this->assertTrue(Schema::hasTable('program_pemupukans'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id'));
        $this->assertTrue(Schema::hasTable('rekomendasi_operasional_histories'));
    }

    public function test_rollback_latest_academic_enum_migration_is_safe(): void
    {
        // Migrasi terbaru hanya memperluas enum warna daun pada MySQL.
        // Rollback satu step tidak boleh menghapus skema v2.8 yang lebih lama.
        // Test ini memastikan rollback tidak crash
        LegacySchemaBuilder::insertLegacyData();

        Artisan::call('migrate:rollback', [
            '--step' => 1,
            '--force' => true,
        ]);

        // Kolom v2.8 tetap ada karena yang di-rollback hanya migrasi enum terbaru.
        $this->assertTrue(Schema::hasColumn('program_pemupukans', 'active_key'));

        // Tabel utama masih ada
        $this->assertTrue(Schema::hasTable('program_pemupukans'));
        $this->assertTrue(Schema::hasTable('rekomendasi_rbs'));
        $this->assertTrue(Schema::hasTable('blok_lahans'));
    }

    public function test_legacy_rekomendasi_tanpa_program_tetap_aman(): void
    {
        LegacySchemaBuilder::insertLegacyData();

        $rekomendasi = DB::table('rekomendasi_rbs')->first();

        $this->assertNotNull($rekomendasi);
        $this->assertNull($rekomendasi->program_pemupukan_id ?? null);
        $this->assertEquals('Normal', $rekomendasi->status_kebutuhan_dominan);
    }

    public function test_legacy_realisasi_tanpa_program_tetap_aman(): void
    {
        LegacySchemaBuilder::insertLegacyData();

        if (! Schema::hasTable('realisasi_pemupukans') || ! Schema::hasColumn('realisasi_pemupukans', 'urea_realisasi_kg')) {
            $this->markTestSkipped('Tabel realisasi_pemupukans belum upgrade');
        }

        $realisasi = DB::table('realisasi_pemupukans')->first();

        $this->assertNotNull($realisasi);
        $this->assertNull($realisasi->program_pemupukan_id ?? null);
        $this->assertEquals('SELESAI', $realisasi->status_realisasi);
    }
}
