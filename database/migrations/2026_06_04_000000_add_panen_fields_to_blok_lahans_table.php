<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->decimal('total_tonase_panen', 10, 2)->nullable()->default(null)->after('koordinat_geojson')
                ->comment('Total tonase hasil panen (ton)');
            $table->decimal('yield_per_hektar', 10, 2)->nullable()->default(null)->after('total_tonase_panen')
                ->comment('Produktivitas: tonase panen / luas lahan (ton/ha)');
        });
    }

    public function down(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropColumn(['total_tonase_panen', 'yield_per_hektar']);
        });
    }
};
