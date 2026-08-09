<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helper untuk membangun skema legacy dalam testing.
 *
 * Pahan v2.8: Digunakan oleh TrueLegacySchemaUpgradeV28Test.
 * Kompatibel dengan MySQL dan SQLite.
 */
class LegacySchemaBuilder
{
    /**
     * Insert data legacy minimal.
     */
    public static function insertLegacyData(): void
    {
        // Skip jika data sudah ada (idempotent)
        if (DB::table('admins')->where('username', 'admin_legacy')->exists()) {
            return;
        }

        // Admin
        $adminData = [
            'username' => 'admin_legacy',
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin Legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('admins', 'tema')) {
            $adminData['tema'] = 'system';
        }
        DB::table('admins')->insert($adminData);

        // Anggota
        $anggotaNama = 'Petani Legacy '.uniqid();
        DB::table('anggotas')->insert([
            'nama' => $anggotaNama,
            'no_hp' => '081234567890',
            'alamat' => 'Desa Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $anggotaId = DB::table('anggotas')->where('nama', $anggotaNama)->value('id');

        // Blok Lahan
        $blokData = [
            'nama_blok' => 'Blok Legacy A1',
            'luas_ha' => 2.0,
            'sph' => 143,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[101.0, 0.5], [101.1, 0.5], [101.1, 0.6], [101.0, 0.6], [101.0, 0.5]]]]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('blok_lahans', 'anggota_id')) {
            $blokData['anggota_id'] = $anggotaId;
        }
        if (Schema::hasColumn('blok_lahans', 'tahun_tanam')) {
            $blokData['tahun_tanam'] = 2015;
        }
        DB::table('blok_lahans')->insert($blokData);

        $blokId = DB::table('blok_lahans')->where('nama_blok', 'Blok Legacy A1')->value('id');
        $adminId = DB::table('admins')->where('username', 'admin_legacy')->value('id');

        // Kondisi Lahan
        $kondisiData = [
            'blok_lahan_id' => $blokId,
            'tanggal_observasi' => now()->subMonth()->toDateString(),
            'warna_daun' => 'Hijau Normal',
            'kelembaban_tanah' => 'Lembab',
            'kondisi_drainase' => 'Baik',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('kondisi_lahans')->insert($kondisiData);

        $kondisiId = DB::table('kondisi_lahans')->where('blok_lahan_id', $blokId)->value('id');

        // Rule pengguna
        if (Schema::hasTable('rule_bases_lanjutan')) {
            $ruleData = [
                'kondisi_warna_daun' => 'Kuning Merata',
                'indikasi_masalah' => 'Defisiensi N',
                'status_kebutuhan' => 'Segera',
                'jenis_pupuk_utama' => 'Urea',
                'saran_tindakan' => 'Tambah Urea segera',
                'prioritas' => 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('rule_bases_lanjutan')->insert($ruleData);
        }

        // Rekomendasi legacy
        $rekData = [
            'blok_lahan_id' => $blokId,
            'kondisi_lahan_id' => $kondisiId,
            'admin_id' => $adminId,
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
        ];
        DB::table('rekomendasi_rbs')->insert($rekData);

        $rekId = DB::table('rekomendasi_rbs')->where('blok_lahan_id', $blokId)->value('id');

        // Realisasi legacy
        if (Schema::hasTable('realisasi_pemupukans') && Schema::hasColumn('realisasi_pemupukans', 'urea_realisasi_kg')) {
            $realData = [
                'rekomendasi_rbs_id' => $rekId,
                'blok_lahan_id' => $blokId,
                'admin_id' => $adminId,
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
            DB::table('realisasi_pemupukans')->insert($realData);
        }
    }

    /**
     * Verifikasi bahwa data legacy masih utuh setelah migration.
     */
    public static function verifyDataIntegrity(): array
    {
        $issues = [];

        if (DB::table('admins')->where('username', 'admin_legacy')->doesntExist()) {
            $issues[] = 'Admin legacy hilang';
        }

        if (DB::table('anggotas')->count() === 0) {
            $issues[] = 'Anggota legacy hilang';
        }

        if (DB::table('blok_lahans')->count() === 0) {
            $issues[] = 'Blok lahan legacy hilang';
        }

        if (DB::table('kondisi_lahans')->count() === 0) {
            $issues[] = 'Kondisi lahan legacy hilang';
        }

        if (DB::table('rekomendasi_rbs')->count() === 0) {
            $issues[] = 'Rekomendasi legacy hilang';
        }

        if (Schema::hasTable('realisasi_pemupukans') && Schema::hasColumn('realisasi_pemupukans', 'urea_realisasi_kg')) {
            if (DB::table('realisasi_pemupukans')->count() === 0) {
                $issues[] = 'Realisasi legacy hilang';
            }
        }

        if (Schema::hasTable('rule_bases_lanjutan')) {
            if (DB::table('rule_bases_lanjutan')->count() === 0) {
                $issues[] = 'Rule legacy hilang';
            }
        }

        return $issues;
    }
}
