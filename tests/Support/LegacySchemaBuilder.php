<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helper untuk membangun skema legacy dalam testing.
 *
 * Pahan v2.8: Digunakan oleh TrueLegacySchemaUpgradeV28Test.
 */
class LegacySchemaBuilder
{
    /**
     * Insert data legacy minimal sebelum migration v2.5.
     */
    public static function insertLegacyData(): void
    {
        // Admin
        DB::table('admins')->insert([
            'id' => 1,
            'username' => 'admin_legacy',
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin Legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Anggota
        DB::table('anggotas')->insert([
            'id' => 1,
            'nama' => 'Petani Legacy',
            'no_hp' => '081234567890',
            'alamat' => 'Desa Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Blok Lahan
        DB::table('blok_lahans')->insert([
            'id' => 1,
            'anggota_id' => 1,
            'nama_blok' => 'Blok Legacy A1',
            'luas_ha' => 2.0,
            'sph' => 143,
            'tahun_tanam' => 2015,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[101.0, 0.5], [101.1, 0.5], [101.1, 0.6], [101.0, 0.6], [101.0, 0.5]]]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kondisi Lahan
        DB::table('kondisi_lahans')->insert([
            'id' => 1,
            'blok_lahan_id' => 1,
            'tanggal_observasi' => now()->subMonth()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'ph_tanah' => 5.5,
            'kelembaban_tanah' => 'Lembab',
            'kondisi_drainase' => 'Baik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rule pengguna
        if (Schema::hasTable('rule_bases_lanjutan')) {
            DB::table('rule_bases_lanjutan')->insert([
                'id' => 1,
                'kondisi_warna_daun' => 'Kuning Merata',
                'indikasi_masalah' => 'Defisiensi N',
                'status_kebutuhan' => 'Segera',
                'jenis_pupuk_utama' => 'Urea',
                'dosis_anjuran' => '1.0-1.5 kg/pokok/tahun',
                'saran_tindakan' => 'Tambah Urea segera',
                'prioritas' => 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Rekomendasi legacy (tanpa program_pemupukan_id, tanpa active_stage)
        DB::table('rekomendasi_rbs')->insert([
            'id' => 1,
            'blok_lahan_id' => 1,
            'kondisi_lahan_id' => 1,
            'admin_id' => 1,
            'tanggal_analisis' => now()->subWeek()->toDateString(),
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => json_encode([]),
            'masalah_teridentifikasi' => json_encode(['Tidak ada']),
            'rekomendasi_pupuk' => json_encode([]),
            'saran_tindakan_utama' => 'Standar',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 1.0,
            'dosis_kcl' => 1.5,
            'total_urea' => 50.0,
            'total_kcl' => 40.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Realisasi legacy (tanpa program_pemupukan_id)
        if (Schema::hasTable('realisasi_pemupukans')) {
            $columns = [
                'id' => 1,
                'rekomendasi_rbs_id' => 1,
                'blok_lahan_id' => 1,
                'admin_id' => 1,
                'tahap' => 1,
                'tanggal_realisasi' => now()->subDays(10)->toDateString(),
                'urea_rencana_kg' => 50.0,
                'kcl_rencana_kg' => 40.0,
                'urea_realisasi_kg' => 50.0,
                'kcl_realisasi_kg' => 40.0,
                'status_realisasi' => 'SELESAI',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('realisasi_pemupukans')->insert($columns);
        }
    }

    /**
     * Verifikasi bahwa data legacy masih utuh setelah migration.
     */
    public static function verifyDataIntegrity(): array
    {
        $issues = [];

        // Admin masih ada
        if (DB::table('admins')->where('id', 1)->doesntExist()) {
            $issues[] = 'Admin legacy hilang';
        }

        // Anggota masih ada
        if (DB::table('anggotas')->where('id', 1)->doesntExist()) {
            $issues[] = 'Anggota legacy hilang';
        }

        // Blok masih ada
        if (DB::table('blok_lahans')->where('id', 1)->doesntExist()) {
            $issues[] = 'Blok lahan legacy hilang';
        }

        // Kondisi masih ada
        if (DB::table('kondisi_lahans')->where('id', 1)->doesntExist()) {
            $issues[] = 'Kondisi lahan legacy hilang';
        }

        // Rekomendasi masih ada
        if (DB::table('rekomendasi_rbs')->where('id', 1)->doesntExist()) {
            $issues[] = 'Rekomendasi legacy hilang';
        }

        // Realisasi masih ada
        if (Schema::hasTable('realisasi_pemupukans')) {
            if (DB::table('realisasi_pemupukans')->where('id', 1)->doesntExist()) {
                $issues[] = 'Realisasi legacy hilang';
            }
        }

        // Rule masih ada
        if (Schema::hasTable('rule_bases_lanjutan')) {
            if (DB::table('rule_bases_lanjutan')->where('id', 1)->doesntExist()) {
                $issues[] = 'Rule legacy hilang';
            }
        }

        return $issues;
    }
}
