<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration finalisasi Pahan-v2:
 * - Tambah field jenis_rule, tingkat_keparahan, kategori_kesimpulan ke rule_bases_lanjutan
 * - Tambah field umur_tanaman_snapshot_metode, tanggal_referensi_umur ke rekomendasi_rbs
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah field normalisasi output rule
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            if (!Schema::hasColumn('rule_bases_lanjutan', 'jenis_rule')) {
                $table->string('jenis_rule', 50)->nullable()->default('DIAGNOSIS_VISUAL')
                    ->after('keterangan_rule')
                    ->comment('DIAGNOSIS_VISUAL, PEMBATAS_APLIKASI, SARAN_PENDUKUNG, PERINGATAN_DATA, NORMAL');
            }
            if (!Schema::hasColumn('rule_bases_lanjutan', 'tingkat_keparahan')) {
                $table->string('tingkat_keparahan', 30)->nullable()->default('NORMAL')
                    ->after('jenis_rule')
                    ->comment('NORMAL, RINGAN, SEDANG, BERAT, PERLU_VERIFIKASI');
            }
            if (!Schema::hasColumn('rule_bases_lanjutan', 'kategori_kesimpulan')) {
                $table->string('kategori_kesimpulan', 50)->nullable()
                    ->after('tingkat_keparahan')
                    ->comment('Kategori kesimpulan rule untuk normalisasi output');
            }
        });

        // 2. Tambah field umur tanaman saat observasi ke rekomendasi_rbs
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            if (!Schema::hasColumn('rekomendasi_rbs', 'metode_perhitungan_umur')) {
                $table->string('metode_perhitungan_umur', 30)->nullable()
                    ->after('umur_tanaman_snapshot')
                    ->comment('tahun_tanam, tanggal_tanam, tidak_tersedia');
            }
            if (!Schema::hasColumn('rekomendasi_rbs', 'tanggal_referensi_umur')) {
                $table->date('tanggal_referensi_umur')->nullable()
                    ->after('metode_perhitungan_umur')
                    ->comment('Tanggal observasi yang digunakan untuk menghitung umur');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $columns = ['jenis_rule', 'tingkat_keparahan', 'kategori_kesimpulan'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('rule_bases_lanjutan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $columns = ['metode_perhitungan_umur', 'tanggal_referensi_umur'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('rekomendasi_rbs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
