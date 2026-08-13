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
            $table->string('kondisi_kelembaban', 50)->nullable()->after('kondisi_topografi');
            $table->string('kondisi_drainase', 50)->nullable()->after('kondisi_kelembaban');
            $table->boolean('ada_gulma_dominan')->nullable()->after('kondisi_drainase');
            $table->boolean('ada_serangan_hama')->nullable()->after('ada_gulma_dominan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn([
                'kondisi_kelembaban',
                'kondisi_drainase',
                'ada_gulma_dominan',
                'ada_serangan_hama',
            ]);
        });
    }
};
