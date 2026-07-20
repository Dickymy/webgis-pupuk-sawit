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
            if (! Schema::hasColumn('rekomendasi_rbs', 'tanggal_referensi_umur')) {
                $table->date('tanggal_referensi_umur')->nullable()
                    ->comment('Tanggal observasi yang digunakan untuk menghitung umur');
            }

            if (! Schema::hasColumn('rekomendasi_rbs', 'metode_perhitungan_umur')) {
                $table->string('metode_perhitungan_umur', 50)->nullable()
                    ->comment('tahun_tanam | tanggal_tanam | tidak_tersedia');
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
