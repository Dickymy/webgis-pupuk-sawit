<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Pahan v2.2 — Menambahkan kolom metode_perhitungan_umur dan tanggal_referensi_umur.
 * Aman untuk data lama: hanya menambah kolom nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            // Kolom ini mungkin sudah ada dari migration sebelumnya
            if (!Schema::hasColumn('rekomendasi_rbs', 'metode_perhitungan_umur')) {
                $table->string('metode_perhitungan_umur', 50)->nullable()
                    ->after('tanggal_referensi_umur')
                    ->comment('tahun_tanam | tanggal_tanam | tidak_tersedia');
            }

            if (!Schema::hasColumn('rekomendasi_rbs', 'tanggal_referensi_umur')) {
                $table->date('tanggal_referensi_umur')->nullable()
                    ->after('umur_tanaman_snapshot')
                    ->comment('Tanggal observasi yang digunakan untuk menghitung umur');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            if (Schema::hasColumn('rekomendasi_rbs', 'metode_perhitungan_umur')) {
                $table->dropColumn('metode_perhitungan_umur');
            }
            if (Schema::hasColumn('rekomendasi_rbs', 'tanggal_referensi_umur')) {
                $table->dropColumn('tanggal_referensi_umur');
            }
        });
    }
};
