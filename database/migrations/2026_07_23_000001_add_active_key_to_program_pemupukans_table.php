<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.8: Tambah active_key untuk mencegah program aktif ganda via unique constraint.
 *
 * Desain:
 * - active_key = "{blok_lahan_id}-{tahun_program}" ketika status AKTIF
 * - active_key = null ketika status bukan AKTIF
 * - UNIQUE index pada active_key → hanya satu program aktif per blok/tahun
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_pemupukans', function (Blueprint $table) {
            if (! Schema::hasColumn('program_pemupukans', 'active_key')) {
                $table->string('active_key', 50)->nullable()->unique()
                    ->after('status_program')
                    ->comment('"{blok_lahan_id}-{tahun_program}" saat AKTIF, null saat tidak aktif');
            }
        });

        // Backfill active_key untuk program yang sudah AKTIF
        DB::table('program_pemupukans')
            ->where('status_program', 'AKTIF')
            ->orderBy('id')
            ->each(function ($program) {
                $key = $program->blok_lahan_id.'-'.$program->tahun_program;

                // Cek duplikat — hanya set pertama yang mendapat key
                $exists = DB::table('program_pemupukans')
                    ->where('active_key', $key)
                    ->exists();

                if (! $exists) {
                    DB::table('program_pemupukans')
                        ->where('id', $program->id)
                        ->update(['active_key' => $key]);
                } else {
                    // Duplikat → arsipkan
                    DB::table('program_pemupukans')
                        ->where('id', $program->id)
                        ->update(['status_program' => 'ARSIP', 'active_key' => null]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('program_pemupukans', function (Blueprint $table) {
            if (Schema::hasColumn('program_pemupukans', 'active_key')) {
                $table->dropUnique(['active_key']);
                $table->dropColumn('active_key');
            }
        });
    }
};
