<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            if (!Schema::hasColumn('rekomendasi_rbs', 'analysis_fingerprint')) {
                $table->string('analysis_fingerprint', 64)->nullable();
                $table->index('analysis_fingerprint');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $table->dropIndex(['analysis_fingerprint']);
            $table->dropColumn('analysis_fingerprint');
        });
    }
};
