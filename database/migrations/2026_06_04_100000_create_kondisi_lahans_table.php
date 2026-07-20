<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kondisi_lahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blok_lahan_id')->constrained('blok_lahans')->onDelete('cascade');
            $table->date('tanggal_observasi');

            // Parameter Tanah
            $table->decimal('ph_tanah', 4, 2)->nullable()->comment('rentang 3.0–8.0');
            $table->enum('kelembaban_tanah', ['Sangat Kering', 'Kering', 'Normal', 'Lembab', 'Sangat Lembab'])->nullable();

            // Parameter Iklim
            $table->enum('curah_hujan_kategori', ['Sangat Rendah', 'Rendah', 'Normal', 'Tinggi', 'Sangat Tinggi'])->nullable();
            $table->enum('musim_saat_ini', ['Musim Hujan', 'Musim Kemarau', 'Peralihan'])->nullable();

            // Gejala Visual Daun
            $table->enum('warna_daun', [
                'Hijau Normal',
                'Hijau Pucat',
                'Kuning Merata',
                'Kuning Tepi',
                'Kuning Antar Tulang',
                'Oranye/Kemerahan',
                'Coklat Ujung',
                'Bercak Nekrotik',
            ])->nullable();
            $table->enum('kondisi_pelepah', ['Normal', 'Patah/Menggantung', 'Kering Prematur', 'Pertumbuhan Terhambat'])->nullable();
            $table->json('gejala_defisiensi')->nullable()->comment('array: N,P,K,Mg,B,Fe,Zn');

            // Gejala Visual Buah & Tandan
            $table->enum('kondisi_tandan', ['Normal', 'Kecil', 'Rontok Prematur', 'Busuk Pangkal', 'Tidak Ada Tandan'])->nullable();

            // Kondisi Fisik Lahan
            $table->enum('kondisi_drainase', ['Baik', 'Cukup', 'Buruk — Tergenang'])->nullable();
            $table->boolean('ada_gulma_dominan')->default(false);
            $table->boolean('ada_serangan_hama')->default(false);

            // Catatan Tambahan
            $table->text('catatan_observasi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kondisi_lahans');
    }
};
