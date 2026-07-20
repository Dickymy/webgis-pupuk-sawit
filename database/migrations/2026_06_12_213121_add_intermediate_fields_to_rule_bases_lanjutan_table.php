<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->json('kondisi_intermediate')->nullable()->after('ada_serangan_hama')
                ->comment('Flag intermediate yang dihasilkan rule ini jika terpicu, e.g. {"butuh_pengapuran": true}');
            $table->json('prasyarat_intermediate')->nullable()->after('kondisi_intermediate')
                ->comment('Flag intermediate yang harus ada agar rule ini bisa dievaluasi, e.g. {"butuh_pengapuran": true}');
        });
    }

    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn(['kondisi_intermediate', 'prasyarat_intermediate']);
        });
    }
};
