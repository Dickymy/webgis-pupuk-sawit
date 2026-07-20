<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.7: Tabel rekomendasi_operasional_histories — histori operasional.
 *
 * Setiap perubahan realisasi mencatat snapshot state operasional.
 * Histori TIDAK PERNAH dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_operasional_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekomendasi_rbs_id')->constrained('rekomendasi_rbs')->onDelete('cascade');
            $table->foreignId('program_pemupukan_id')->nullable()->constrained('program_pemupukans')->onDelete('set null');
            $table->string('event_type', 50)
                ->comment('ANALISIS_AWAL, REALISASI_DIBUAT, REALISASI_DIPERBARUI, REALISASI_DIBATALKAN, TAHAP_1_SEBAGIAN, TAHAP_1_SELESAI, TAHAP_2_SIAP, PROGRAM_SELESAI');
            $table->unsignedTinyInteger('active_stage')->nullable();
            $table->string('status_stage', 50)->nullable();
            $table->decimal('urea_aplikasi_saat_ini', 10, 2)->nullable();
            $table->decimal('kcl_aplikasi_saat_ini', 10, 2)->nullable();
            $table->decimal('urea_sisa_tahunan', 10, 2)->nullable();
            $table->decimal('kcl_sisa_tahunan', 10, 2)->nullable();
            $table->date('tanggal_minimum_tahap_berikutnya')->nullable();
            $table->text('alasan_tahap')->nullable();
            $table->string('analysis_fingerprint', 64)->nullable();
            $table->foreignId('source_realisasi_id')->nullable()->constrained('realisasi_pemupukans')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['rekomendasi_rbs_id', 'created_at']);
            $table->index(['program_pemupukan_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_operasional_histories');
    }
};
