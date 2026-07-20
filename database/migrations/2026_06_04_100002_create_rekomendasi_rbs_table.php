<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_rbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blok_lahan_id')->constrained('blok_lahans')->onDelete('cascade');
            $table->foreignId('kondisi_lahan_id')->constrained('kondisi_lahans')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->date('tanggal_analisis');

            // Hasil analisis (JSON array of matched rules)
            $table->json('rules_terpicu')->comment('[{rule_id, indikasi, pupuk, status, prioritas}]');

            // Ringkasan output
            $table->json('masalah_teridentifikasi')->comment('array string masalah');
            $table->json('rekomendasi_pupuk')->comment('[{jenis, dosis, metode, waktu}]');
            $table->text('saran_tindakan_utama');
            $table->enum('status_kebutuhan_dominan', ['Darurat', 'Segera', 'Normal', 'Tunda']);
            $table->tinyInteger('jumlah_rule_terpicu')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_rbs');
    }
};
