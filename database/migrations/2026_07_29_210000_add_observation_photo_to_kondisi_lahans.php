<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            if (! Schema::hasColumn('kondisi_lahans', 'foto_observasi_path')) {
                $table->string('foto_observasi_path')
                    ->nullable()
                    ->after('catatan_observasi')
                    ->comment('Lokasi foto pendukung hasil observasi lapangan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            if (Schema::hasColumn('kondisi_lahans', 'foto_observasi_path')) {
                $table->dropColumn('foto_observasi_path');
            }
        });
    }
};
