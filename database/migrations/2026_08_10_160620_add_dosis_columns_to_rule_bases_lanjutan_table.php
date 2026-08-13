<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->integer('kondisi_umur_tahun')->nullable()->after('kondisi_kategori_umur')->comment('Umur spesifik dalam tahun (untuk rule penentu dosis)');
            $table->decimal('rekomendasi_dosis_urea', 8, 2)->nullable()->after('status_kebutuhan')->comment('Keluaran dosis Urea dalam kg/pohon (untuk rule penentu dosis)');
            $table->decimal('rekomendasi_dosis_kcl', 8, 2)->nullable()->after('rekomendasi_dosis_urea')->comment('Keluaran dosis KCl dalam kg/pohon (untuk rule penentu dosis)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn(['kondisi_umur_tahun', 'rekomendasi_dosis_urea', 'rekomendasi_dosis_kcl']);
        });
    }
};
