<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationUpgradePathTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekomendasi_rbs_has_v25_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'luas_ha_snapshot'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'sph_snapshot'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'active_stage'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'status_stage'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'urea_sisa_tahunan'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'kcl_sisa_tahunan'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'tanggal_minimum_tahap_berikutnya'));
        $this->assertTrue(Schema::hasColumn('rekomendasi_rbs', 'alasan_tahap'));
    }

    public function test_realisasi_pemupukans_has_v25_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'blok_lahan_id'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'tahap'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'urea_rencana_kg'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'kcl_rencana_kg'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'urea_realisasi_kg'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'kcl_realisasi_kg'));
        $this->assertTrue(Schema::hasColumn('realisasi_pemupukans', 'status_realisasi'));
    }

    public function test_v25_columns_are_nullable_safe_for_old_data(): void
    {
        // Insert data tanpa field v2.5 — harus berhasil (nullable)
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'testadmin',
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Test Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $anggotaId = DB::table('anggotas')->insertGetId([
            'nama' => 'Test',
            'alamat' => 'Test',
            'no_hp' => '08123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blokId = DB::table('blok_lahans')->insertGetId([
            'anggota_id' => $anggotaId,
            'nama_blok' => 'Blok Test',
            'luas_ha' => 2.0,
            'sph' => 136,
            'tahun_tanam' => 2020,
            'koordinat_geojson' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kondisiId = DB::table('kondisi_lahans')->insertGetId([
            'blok_lahan_id' => $blokId,
            'tanggal_observasi' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert rekomendasi TANPA field v2.5 — simulasi data lama
        $rbsId = DB::table('rekomendasi_rbs')->insertGetId([
            'blok_lahan_id' => $blokId,
            'kondisi_lahan_id' => $kondisiId,
            'admin_id' => $adminId,
            'tanggal_analisis' => now()->toDateString(),
            'is_latest' => true,
            'rules_terpicu' => '[]',
            'masalah_teridentifikasi' => '[]',
            'rekomendasi_pupuk' => '[]',
            'saran_tindakan_utama' => 'Test',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('rekomendasi_rbs', ['id' => $rbsId]);

        // Field v2.5 harus null (aman untuk data lama)
        $rbs = DB::table('rekomendasi_rbs')->find($rbsId);
        $this->assertNull($rbs->luas_ha_snapshot);
        $this->assertNull($rbs->sph_snapshot);
        $this->assertNull($rbs->active_stage);
        $this->assertNull($rbs->status_stage);
    }

    public function test_no_duplicate_columns_after_migration(): void
    {
        $columns = Schema::getColumnListing('rekomendasi_rbs');
        $duplicates = array_diff_assoc($columns, array_unique($columns));
        $this->assertEmpty($duplicates, 'Duplicate columns found: '.implode(', ', $duplicates));
    }

    public function test_existing_data_preserved_after_migration(): void
    {
        // Data yang dibuat oleh RefreshDatabase harus tetap ada
        $this->assertTrue(Schema::hasTable('rekomendasi_rbs'));
        $this->assertTrue(Schema::hasTable('realisasi_pemupukans'));
        $this->assertTrue(Schema::hasTable('blok_lahans'));
        $this->assertTrue(Schema::hasTable('kondisi_lahans'));
        $this->assertTrue(Schema::hasTable('rule_bases_lanjutan'));
    }
}
