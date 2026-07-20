<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom kriteria ke blok_lahans
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->integer('tahun_tanam')->nullable()->after('koordinat_geojson');
            $table->string('jenis_tanah', 255)->nullable()->after('tahun_tanam');
            $table->string('topografi', 50)->nullable()->after('jenis_tanah');
        });

        // 2. Migrasi data dari kriteria_lahans ke blok_lahans
        $kriteria = DB::table('kriteria_lahans')->get();
        foreach ($kriteria as $k) {
            DB::table('blok_lahans')->where('id', $k->blok_lahan_id)->update([
                'tahun_tanam' => $k->tahun_tanam,
                'jenis_tanah' => $k->jenis_tanah,
                'topografi' => $k->topografi,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropColumn(['tahun_tanam', 'jenis_tanah', 'topografi']);
        });
    }
};
