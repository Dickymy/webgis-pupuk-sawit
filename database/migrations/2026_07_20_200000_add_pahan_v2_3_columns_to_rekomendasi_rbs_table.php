<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Pahan v2.3:
 * - Menambah field total kebutuhan tahunan
 * - Menambah field aplikasi saat ini
 * - Menambah field karung estimasi tahunan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            // Total kebutuhan tahunan
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_total_min_tahunan')) {
                $table->decimal('urea_total_min_tahunan', 10, 2)->nullable()
                    ->after('jumlah_pokok_snapshot')
                    ->comment('Total min kebutuhan Urea tahunan (kg)');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_total_max_tahunan')) {
                $table->decimal('urea_total_max_tahunan', 10, 2)->nullable()
                    ->after('urea_total_min_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_total_estimasi_tahunan')) {
                $table->decimal('urea_total_estimasi_tahunan', 10, 2)->nullable()
                    ->after('urea_total_max_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_total_min_tahunan')) {
                $table->decimal('kcl_total_min_tahunan', 10, 2)->nullable()
                    ->after('urea_total_estimasi_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_total_max_tahunan')) {
                $table->decimal('kcl_total_max_tahunan', 10, 2)->nullable()
                    ->after('kcl_total_min_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_total_estimasi_tahunan')) {
                $table->decimal('kcl_total_estimasi_tahunan', 10, 2)->nullable()
                    ->after('kcl_total_max_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_karung_estimasi_tahunan')) {
                $table->integer('urea_karung_estimasi_tahunan')->nullable()
                    ->after('kcl_total_estimasi_tahunan');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_karung_estimasi_tahunan')) {
                $table->integer('kcl_karung_estimasi_tahunan')->nullable()
                    ->after('urea_karung_estimasi_tahunan');
            }
            // Aplikasi saat ini (0 jika ditunda)
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_aplikasi_saat_ini')) {
                $table->decimal('urea_aplikasi_saat_ini', 10, 2)->nullable()
                    ->after('kcl_karung_estimasi_tahunan')
                    ->comment('Total Urea untuk aplikasi saat ini (0 jika ditunda)');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_aplikasi_saat_ini')) {
                $table->decimal('kcl_aplikasi_saat_ini', 10, 2)->nullable()
                    ->after('urea_aplikasi_saat_ini')
                    ->comment('Total KCl untuk aplikasi saat ini (0 jika ditunda)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $columns = [
                'urea_total_min_tahunan',
                'urea_total_max_tahunan',
                'urea_total_estimasi_tahunan',
                'kcl_total_min_tahunan',
                'kcl_total_max_tahunan',
                'kcl_total_estimasi_tahunan',
                'urea_karung_estimasi_tahunan',
                'kcl_karung_estimasi_tahunan',
                'urea_aplikasi_saat_ini',
                'kcl_aplikasi_saat_ini',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('rekomendasi_rbs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
