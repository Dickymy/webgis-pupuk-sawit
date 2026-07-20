<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $table->date('tanggal_pemupukan_terakhir')->nullable()->after('tanggal_observasi')
                ->comment('Tanggal terakhir kali blok ini dipupuk');
        });
    }

    public function down(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $table->dropColumn('tanggal_pemupukan_terakhir');
        });
    }
};
