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
            $table->string('kondisi_topografi')->nullable()->after('kondisi_warna_daun')->comment('Opsi: Datar (0-8%), Bergelombang (8-15%), Curam (>15%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn('kondisi_topografi');
        });
    }
};
