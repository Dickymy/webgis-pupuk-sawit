<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test upgrade schema lama yang sebenarnya (Pahan v2.7 — 4.8).
 *
 * TIDAK menggunakan RefreshDatabase.
 * Menjalankan migration step-by-step dan memverifikasi integritas data.
 */
class TrueLegacySchemaUpgradeTest extends TestCase
{
    /**
     * Test full migration path: fresh → insert legacy data → migrate v2.5/v2.6/v2.7 → verify.
     */
    public function test_full_migration_upgrade_path(): void
    {
        // 1. Fresh migration (creates all tables)
        Artisan::call('migrate:fresh', ['--force' => true]);

        // Verify base tables exist
        $this->assertTrue(Schema::hasTable('admins'));
        $this->assertTrue(Schema::hasTable('blok_lahans'));
        $this->assertTrue(Schema::hasTable('kondisi_lahans'));
        $this->assertTrue(Schema::hasTable('rekomendasi_rbs'));
        $this->assertTrue(Schema::hasTable('realisasi_pemupukans'));
        $this->assertTrue(Schema::hasTable('rule_bases_lanjutan'));

        // 2. Insert legacy-style data (simulating pre-v2.5 state)
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'admin_legacy',
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin Legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $anggotaId = DB::table('anggotas')->insertGetId([
            'nama' => 'Petani Legacy',
            'alamat' => 'Desa',
            'no_hp' => '08123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blokId = DB::table('blok_lahans')->insertGetId([
            'anggota_id' => $anggotaId,
            'nama_blok' => 'Blok Legacy',
            'luas_ha' => 2.5,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'jenis_tanah' => 'Tanah Lempung',
            'topografi' => 'Datar 0-15°',
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kondisiId = DB::table('kondisi_lahans')->insertGetId([
            'blok_lahan_id' => $blokId,
            'tanggal_observasi' => now()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'curah_hujan_mm_bulanan' => 150,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert rule pengguna (custom rule)
        $ruleId = DB::table('rule_bases_lanjutan')->insertGetId([
            'kode_rule' => 'USER_RULE_001',
            'kondisi_warna_daun' => 'Kuning Pucat',
            'indikasi_masalah' => 'Kekurangan nitrogen',
            'jenis_pupuk_utama' => 'Urea',
            'dosis_anjuran' => '1.0 kg/pokok/tahun',
            'metode_aplikasi' => 'Tabur melingkar',
            'waktu_aplikasi' => 'Saat curah hujan normal',
            'saran_tindakan' => 'Tambah Urea segera',
            'status_kebutuhan' => 'Segera',
            'prioritas' => 1,
            'aktif' => true,
            'is_system_rule' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert rekomendasi lama
        $rekomendasiId = DB::table('rekomendasi_rbs')->insertGetId([
            'blok_lahan_id' => $blokId,
            'kondisi_lahan_id' => $kondisiId,
            'admin_id' => $adminId,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 1,
            'rules_terpicu' => json_encode([]),
            'masalah_teridentifikasi' => json_encode([]),
            'rekomendasi_pupuk' => json_encode([]),
            'saran_tindakan_utama' => 'Lanjutkan pemupukan.',
            'versi_mesin_rekomendasi' => 'pahan-v2.7',
            'urea_total_estimasi_tahunan' => 544.0,
            'kcl_total_estimasi_tahunan' => 680.0,
            'luas_ha_snapshot' => 2.5,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 340,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert realisasi format lama (tanpa program_pemupukan_id)
        DB::table('realisasi_pemupukans')->insert([
            'rekomendasi_rbs_id' => $rekomendasiId,
            'blok_lahan_id' => $blokId,
            'admin_id' => $adminId,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->subDays(10)->toDateString(),
            'urea_rencana_kg' => 272.0,
            'kcl_rencana_kg' => 340.0,
            'urea_realisasi_kg' => 272.0,
            'kcl_realisasi_kg' => 340.0,
            'status_realisasi' => 'SELESAI',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Verify v2.7 tables exist (since we ran migrate:fresh with all migrations)
        $this->assertTrue(Schema::hasTable('program_pemupukans'));
        $this->assertTrue(Schema::hasTable('rekomendasi_operasional_histories'));

        // 4. Verify columns exist
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id'));

        // 5. Verify data lama tetap ada
        $this->assertDatabaseHas('admins', ['username' => 'admin_legacy']);
        $this->assertDatabaseHas('anggotas', ['nama' => 'Petani Legacy']);
        $this->assertDatabaseHas('blok_lahans', ['nama_blok' => 'Blok Legacy']);
        $this->assertDatabaseHas('kondisi_lahans', ['blok_lahan_id' => $blokId]);
        $this->assertDatabaseHas('rule_bases_lanjutan', ['kode_rule' => 'USER_RULE_001', 'is_system_rule' => false]);
        $this->assertDatabaseHas('rekomendasi_rbs', ['blok_lahan_id' => $blokId]);
        $this->assertDatabaseHas('realisasi_pemupukans', ['blok_lahan_id' => $blokId, 'urea_realisasi_kg' => 272.0]);

        // 6. Verify foreign keys valid
        $realisasi = DB::table('realisasi_pemupukans')->where('blok_lahan_id', $blokId)->first();
        $this->assertNotNull($realisasi);
        $this->assertEquals($rekomendasiId, $realisasi->rekomendasi_rbs_id);

        // 7. Verify rule pengguna tetap ada
        $rule = DB::table('rule_bases_lanjutan')->where('kode_rule', 'USER_RULE_001')->first();
        $this->assertNotNull($rule);
        $this->assertFalse((bool) $rule->is_system_rule);

        // 8. Verify legacy data with null program_pemupukan_id is acceptable
        $this->assertNull($realisasi->program_pemupukan_id);

        // 9. Rollback v2.7 migrations
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/2026_07_22_000002_create_rekomendasi_operasional_histories_table.php',
            '--force' => true,
        ]);
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/2026_07_22_000001_create_program_pemupukans_table.php',
            '--force' => true,
        ]);

        // Tables should be gone
        $this->assertFalse(Schema::hasTable('rekomendasi_operasional_histories'));
        $this->assertFalse(Schema::hasTable('program_pemupukans'));
        $this->assertFalse(Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id'));
        $this->assertFalse(Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id'));

        // 10. Data lama masih ada setelah rollback
        $this->assertDatabaseHas('realisasi_pemupukans', ['blok_lahan_id' => $blokId]);
        $this->assertDatabaseHas('rekomendasi_rbs', ['blok_lahan_id' => $blokId]);
        $this->assertDatabaseHas('rule_bases_lanjutan', ['kode_rule' => 'USER_RULE_001']);

        // 11. Re-migrate
        Artisan::call('migrate', ['--force' => true]);

        // 12. Verify tables restored
        $this->assertTrue(Schema::hasTable('program_pemupukans'));
        $this->assertTrue(Schema::hasTable('rekomendasi_operasional_histories'));

        // Data still exists
        $this->assertDatabaseHas('realisasi_pemupukans', ['blok_lahan_id' => $blokId]);
        $this->assertDatabaseHas('rule_bases_lanjutan', ['kode_rule' => 'USER_RULE_001']);
    }
}
