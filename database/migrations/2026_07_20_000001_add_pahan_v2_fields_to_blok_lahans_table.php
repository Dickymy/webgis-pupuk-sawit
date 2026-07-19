<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->enum('fase_tanaman', ['TBM', 'TM'])->nullable()->after('topografi')
                  ->comment('Fase tanaman: TBM (Tanaman Belum Menghasilkan) atau TM (Tanaman Menghasilkan)');
        });
    }

    public function down(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropColumn('fase_tanaman');
        });
    }
};
