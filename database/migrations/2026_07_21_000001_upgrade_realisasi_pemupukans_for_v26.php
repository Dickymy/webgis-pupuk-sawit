<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.6: Tambah field validasi override dan tahun program ke realisasi_pemupukans.
 *
 * Field baru:
 * - tahun_program: tahun program pemupukan (untuk memisahkan realisasi antar tahun)
 * - confirmed_over_plan: konfirmasi jika realisasi melebihi rencana tahap
 * - override_annual_limit: flag override kebutuhan tahunan
 * - override_reason: alasan override kebutuhan tahunan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (! Schema::hasColumn('realisasi_pemupukans', 'tahun_program')) {
                $table->unsignedSmallInteger('tahun_program')->nullable()->after('blok_lahan_id')
                    ->comment('Tahun program pemupukan');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'confirmed_over_plan')) {
                $table->boolean('confirmed_over_plan')->default(false)->after('catatan_pelaksana')
                    ->comment('Konfirmasi admin jika realisasi melebihi rencana tahap');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'override_annual_limit')) {
                $table->boolean('override_annual_limit')->default(false)->after('confirmed_over_plan')
                    ->comment('Override batas kebutuhan tahunan');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'override_reason')) {
                $table->text('override_reason')->nullable()->after('override_annual_limit')
                    ->comment('Alasan override kebutuhan tahunan');
            }
        });

        // Backfill tahun_program dari tanggal_realisasi untuk data lama
        if (Schema::hasColumn('realisasi_pemupukans', 'tahun_program') && Schema::hasColumn('realisasi_pemupukans', 'tanggal_realisasi')) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                DB::statement("
                    UPDATE realisasi_pemupukans
                    SET tahun_program = CAST(strftime('%Y', tanggal_realisasi) AS INTEGER)
                    WHERE tahun_program IS NULL AND tanggal_realisasi IS NOT NULL
                ");
            } else {
                DB::statement('
                    UPDATE realisasi_pemupukans
                    SET tahun_program = YEAR(tanggal_realisasi)
                    WHERE tahun_program IS NULL AND tanggal_realisasi IS NOT NULL
                ');
            }
        }
    }

    public function down(): void
    {
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            $columns = ['tahun_program', 'confirmed_over_plan', 'override_annual_limit', 'override_reason'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('realisasi_pemupukans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
