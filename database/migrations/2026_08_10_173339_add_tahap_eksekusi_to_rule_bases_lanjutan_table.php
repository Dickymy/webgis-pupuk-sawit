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
            $table->tinyInteger('tahap_eksekusi')->default(1)->after('jenis_rule')->comment('1: Diagnosis Kondisi, 2: Penentuan Dosis, 3: Penyesuaian Final');
            $table->json('fakta_yang_dihasilkan')->nullable()->after('rekomendasi_dosis_kcl')->comment('JSON berisi kesimpulan antara untuk working memory');
            $table->json('prasyarat_fakta')->nullable()->after('fakta_yang_dihasilkan')->comment('JSON berisi prasyarat fakta dari tahap sebelumnya');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn(['tahap_eksekusi', 'fakta_yang_dihasilkan', 'prasyarat_fakta']);
        });
    }
};
