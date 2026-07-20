<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $table->text('catatan_dosis')->nullable()->after('total_kcl')
                ->comment('Catatan kontekstual terkait kapan/bagaimana dosis boleh diaplikasikan');
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $table->dropColumn('catatan_dosis');
        });
    }
};
