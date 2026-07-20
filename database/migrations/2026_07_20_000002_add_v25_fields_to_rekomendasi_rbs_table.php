<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.5: Tambahkan field snapshot luas/SPH dan field tahap aktif
 * ke tabel rekomendasi_rbs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            // Snapshot luas dan SPH saat analisis
            if (! Schema::hasColumn('rekomendasi_rbs', 'luas_ha_snapshot')) {
                $table->decimal('luas_ha_snapshot', 8, 4)->nullable();
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'sph_snapshot')) {
                $table->unsignedInteger('sph_snapshot')->nullable();
            }
            // Field tahap aktif dan sisa tahunan
            if (! Schema::hasColumn('rekomendasi_rbs', 'active_stage')) {
                $table->unsignedTinyInteger('active_stage')->nullable()
                    ->comment('Tahap aktif saat ini (1 atau 2)');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'status_stage')) {
                $table->string('status_stage', 50)->nullable()
                    ->comment('TAHAP_1_SIAP, TAHAP_1_SEBAGIAN, MENUNGGU_INTERVAL, MENUNGGU_KELAYAKAN, TAHAP_2_SIAP, SELESAI_TAHUNAN, PERLU_VERIFIKASI_REALISASI');
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'urea_sisa_tahunan')) {
                $table->decimal('urea_sisa_tahunan', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'kcl_sisa_tahunan')) {
                $table->decimal('kcl_sisa_tahunan', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'tanggal_minimum_tahap_berikutnya')) {
                $table->date('tanggal_minimum_tahap_berikutnya')->nullable();
            }
            if (! Schema::hasColumn('rekomendasi_rbs', 'alasan_tahap')) {
                $table->text('alasan_tahap')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $columns = [
                'luas_ha_snapshot', 'sph_snapshot',
                'active_stage', 'status_stage',
                'urea_sisa_tahunan', 'kcl_sisa_tahunan',
                'tanggal_minimum_tahap_berikutnya', 'alasan_tahap',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('rekomendasi_rbs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
