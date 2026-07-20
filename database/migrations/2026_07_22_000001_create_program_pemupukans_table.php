<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.7: Tabel program_pemupukans — identitas program pemupukan tahunan per blok.
 *
 * Aturan:
 * - Satu blok hanya boleh memiliki satu program aktif per tahun.
 * - Realisasi hanya dihitung dalam program yang sama.
 * - Program selesai tidak boleh dibuka kembali tanpa proses eksplisit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_pemupukans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('blok_lahan_id')->constrained('blok_lahans')->onDelete('cascade');
            $table->unsignedSmallInteger('tahun_program');
            $table->foreignId('rekomendasi_awal_id')->nullable()->constrained('rekomendasi_rbs')->onDelete('set null');
            $table->string('status_program', 30)->default('AKTIF')
                ->comment('AKTIF, SELESAI, DIBATALKAN, ARSIP');
            $table->timestamps();

            $table->index(['blok_lahan_id', 'tahun_program', 'status_program'], 'idx_blok_tahun_status');
        });

        // Tambah program_pemupukan_id ke rekomendasi_rbs
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            if (! Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id')) {
                $table->foreignId('program_pemupukan_id')->nullable()->after('blok_lahan_id')
                    ->constrained('program_pemupukans')->onDelete('set null');
            }
        });

        // Tambah program_pemupukan_id ke realisasi_pemupukans
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (! Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id')) {
                $table->foreignId('program_pemupukan_id')->nullable()->after('blok_lahan_id')
                    ->constrained('program_pemupukans')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        // Drop FK sebelum drop column
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (Schema::hasColumn('realisasi_pemupukans', 'program_pemupukan_id')) {
                $table->dropForeign(['program_pemupukan_id']);
                $table->dropColumn('program_pemupukan_id');
            }
        });

        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            if (Schema::hasColumn('rekomendasi_rbs', 'program_pemupukan_id')) {
                $table->dropForeign(['program_pemupukan_id']);
                $table->dropColumn('program_pemupukan_id');
            }
        });

        Schema::dropIfExists('program_pemupukans');
    }
};
