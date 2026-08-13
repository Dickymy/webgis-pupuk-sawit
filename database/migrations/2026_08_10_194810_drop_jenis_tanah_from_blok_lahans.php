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
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropColumn('jenis_tanah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->string('jenis_tanah', 255)->nullable();
        });
    }
};
