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
 *
 * Alur:
 * 1. Migrate fresh (semua migration)
 * 2. Insert data legacy
 * 3. Verify data utuh
 * 4. Rollback v2.8
 * 5. Migrate ulang v2.8
 * 6. Verify data tetap utuh
 */
class TrueLegacySchemaUpgradeV28Test extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_fresh_and_legacy_data_survives(): void
    {
        // Database sudah fresh via RefreshDatabase trait
        // Insert legacy data
        LegacySchemaBuilder::insertLegacyData();

        // Verify data utuh
        $issues = LegacySchemaBuilder::verifyDataIntegrity();
        $this->assertEmpty($issues, 'Data integrity issues: '.implode(', ', $issues));

        // Verify v2.8 schema ada
        $this->assertTrue(Schema::hasTable('program_pemupukans'));
        $this->assertTrue(Schema::hasColumn('program_pemupukans', 'active_key'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id'));
        $this->assertTrue(Schema::hasTable('rekomendasi_operasional_histories'));
    }

    public function test_rollback_v28_and_remigrate(): void
    {
        LegacySchemaBuilder::insertLegacyData();

        // Rollback migration v2.8 (active_key)
        Artisan::call('migrate:rollback', [
            '--step' => 1,
            '--force' => true,
        ]);

        // active_key should be gone
        $this->assertFalse(Schema::hasColumn('program_pemupukans', 'active_key'));

        // Data masih utuh
        $issues = LegacySchemaBuilder::verifyDataIntegrity();
        $this->assertEmpty($issues, 'Data lost after rollback: '.implode(', ', $issues));

        // Migrate ulang
        Artisan::call('migrate', ['--force' => true]);

        // Verify schema restored
        $this->assertTrue(Schema::hasColumn('program_pemupukans', 'active_key'));

        // Data masih utuh
        $issues = LegacySchemaBuilder::verifyDataIntegrity();
        $this->assertEmpty($issues, 'Data lost after remigrate: '.implode(', ', $issues));
    }

    public function test_legacy_rekomendasi_tanpa_program_tetap_aman(): void
    {
        LegacySchemaBuilder::insertLegacyData();

        // Rekomendasi legacy tanpa program_pemupukan_id harus tetap bisa diakses
        $rekomendasi = DB::table('rekomendasi_rbs')->where('id', 1)->first();

        $this->assertNotNull($rekomendasi);
        $this->assertNull($rekomendasi->program_pemupukan_id ?? null);
        $this->assertEquals('Normal', $rekomendasi->status_kebutuhan_dominan);
    }

    public function test_legacy_realisasi_tanpa_program_tetap_aman(): void
    {
        LegacySchemaBuilder::insertLegacyData();

        if (! Schema::hasTable('realisasi_pemupukans')) {
            $this->markTestSkipped('Tabel realisasi_pemupukans tidak ada');
        }

        $realisasi = DB::table('realisasi_pemupukans')->where('id', 1)->first();

        $this->assertNotNull($realisasi);
        $this->assertNull($realisasi->program_pemupukan_id ?? null);
        $this->assertEquals('SELESAI', $realisasi->status_realisasi);
    }
}
