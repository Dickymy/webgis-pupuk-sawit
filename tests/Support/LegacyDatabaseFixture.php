<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LegacyDatabaseFixture — Helper untuk mensimulasikan data lama
 * sebelum migrasi v2.5 diterapkan.
 *
 * Digunakan oleh MigrationUpgradePathTest dan MigrationDataPreservationTest.
 */
class LegacyDatabaseFixture
{
    /**
     * Seed data legacy yang representatif.
     *
     * @return array IDs dari entitas yang dibuat
     */
    public static function seed(): array
    {
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'legacy_admin',
            'password' => bcrypt('password'),
            'nama_lengkap' => 'Admin Legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $anggotaId = DB::table('anggotas')->insertGetId([
            'nama' => 'Pemilik Legacy',
            'alamat' => 'Jl. Legacy No. 1',
            'no_hp' => '08123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blokId = DB::table('blok_lahans')->insertGetId([
            'anggota_id' => $anggotaId,
            'nama_blok' => 'Blok Legacy',
            'luas_ha' => 2.5,
            'sph' => 136,
            'tahun_tanam' => 2018,
            'koordinat_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kondisiId = DB::table('kondisi_lahans')->insertGetId([
            'blok_lahan_id' => $blokId,
            'tanggal_observasi' => '2026-05-15',
            'curah_hujan_mm_bulanan' => 160.0,
            'warna_daun' => 'Hijau Normal',
            'kondisi_pelepah' => 'Normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rekomendasi lama (v2.4 format — tanpa field v2.5)
        $rbsId = DB::table('rekomendasi_rbs')->insertGetId([
            'blok_lahan_id' => $blokId,
            'kondisi_lahan_id' => $kondisiId,
            'admin_id' => $adminId,
            'tanggal_analisis' => '2026-06-01',
            'is_latest' => true,
            'nomor_analisis' => 1,
            'rules_terpicu' => '[]',
            'masalah_teridentifikasi' => '["Tidak ada masalah"]',
            'rekomendasi_pupuk' => '[{"jenis_utama":"Pupuk Standar Rutin","dosis":"Sesuai jadwal"}]',
            'saran_tindakan_utama' => 'Lanjutkan pemupukan standar.',
            'status_kebutuhan_dominan' => 'Normal',
            'jumlah_rule_terpicu' => 0,
            'dosis_urea' => 1.325,
            'dosis_kcl' => 1.85,
            'total_urea' => 450.5,
            'total_kcl' => 629.0,
            'status_kondisi_tanaman' => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'urea_total_estimasi_tahunan' => 450.5,
            'kcl_total_estimasi_tahunan' => 629.0,
            'urea_aplikasi_saat_ini' => 450.5,
            'kcl_aplikasi_saat_ini' => 629.0,
            'versi_mesin_rekomendasi' => 'pahan-v2.4',
            'fase_tanaman_snapshot' => 'TM',
            'umur_tanaman_snapshot' => 8,
            'jumlah_pokok_snapshot' => 340,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rule pengguna (custom rule yang TIDAK boleh hilang)
        $ruleId = null;
        if (Schema::hasTable('rule_bases_lanjutan')) {
            $ruleId = DB::table('rule_bases_lanjutan')->insertGetId([
                'indikasi_masalah' => 'Rule Custom Pengguna',
                'jenis_pupuk_utama' => 'Urea',
                'dosis_anjuran' => '1.5 kg/pokok/tahun',
                'saran_tindakan' => 'Saran custom dari pengguna',
                'status_kebutuhan' => 'Normal',
                'prioritas' => 50,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'admin_id' => $adminId,
            'anggota_id' => $anggotaId,
            'blok_id' => $blokId,
            'kondisi_id' => $kondisiId,
            'rbs_id' => $rbsId,
            'rule_id' => $ruleId,
        ];
    }

    /**
     * Verifikasi bahwa data legacy masih utuh setelah migrasi.
     */
    public static function verify(array $ids): array
    {
        $issues = [];

        // Admin masih ada
        if (! DB::table('admins')->where('id', $ids['admin_id'])->exists()) {
            $issues[] = 'Admin hilang';
        }

        // Blok masih ada
        if (! DB::table('blok_lahans')->where('id', $ids['blok_id'])->exists()) {
            $issues[] = 'Blok lahan hilang';
        }

        // Kondisi masih ada
        if (! DB::table('kondisi_lahans')->where('id', $ids['kondisi_id'])->exists()) {
            $issues[] = 'Kondisi lahan hilang';
        }

        // Rekomendasi masih ada
        $rbs = DB::table('rekomendasi_rbs')->where('id', $ids['rbs_id'])->first();
        if (! $rbs) {
            $issues[] = 'Rekomendasi RBS hilang';
        } else {
            if ($rbs->status_kebutuhan_dominan !== 'Normal') {
                $issues[] = 'Status kebutuhan dominan berubah';
            }
            if ((float) $rbs->urea_total_estimasi_tahunan !== 450.5) {
                $issues[] = 'Total tahunan Urea berubah';
            }
        }

        // Rule pengguna masih ada
        if ($ids['rule_id'] && ! DB::table('rule_bases_lanjutan')->where('id', $ids['rule_id'])->exists()) {
            $issues[] = 'Rule pengguna hilang';
        }

        return $issues;
    }
}
