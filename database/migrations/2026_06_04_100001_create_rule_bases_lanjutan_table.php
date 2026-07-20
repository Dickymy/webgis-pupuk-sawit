<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_bases_lanjutan', function (Blueprint $table) {
            $table->id();

            // Kondisi (IF) — kombinasi parameter
            $table->string('kondisi_warna_daun', 100)->nullable();
            $table->decimal('kondisi_ph_min', 4, 2)->nullable();
            $table->decimal('kondisi_ph_max', 4, 2)->nullable();
            $table->string('kondisi_kelembaban', 50)->nullable();
            $table->string('kondisi_musim', 50)->nullable();
            $table->string('kondisi_drainase', 50)->nullable();
            $table->string('kondisi_defisiensi', 50)->nullable()->comment('satu nilai defisiensi target');
            $table->string('kondisi_kategori_umur', 50)->nullable()->comment('boleh NULL = berlaku semua umur');

            // Hasil Diagnosa (THEN)
            $table->string('indikasi_masalah', 255);
            $table->string('jenis_pupuk_utama', 100);
            $table->string('jenis_pupuk_pendukung', 100)->nullable();
            $table->string('dosis_anjuran', 150);
            $table->string('metode_aplikasi', 255)->nullable();
            $table->string('waktu_aplikasi', 150)->nullable();
            $table->text('saran_tindakan');
            $table->enum('status_kebutuhan', ['Darurat', 'Segera', 'Normal', 'Tunda'])->default('Normal');
            $table->tinyInteger('prioritas')->unsigned()->default(5)->comment('1 (tertinggi) – 10 (terendah)');

            $table->boolean('aktif')->default(true);
            $table->text('keterangan_rule')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_bases_lanjutan');
    }
};
