<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom anggota_id (nullable dulu untuk migrasi data)
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->foreignId('anggota_id')->nullable()->after('id')->constrained('anggotas')->nullOnDelete();
        });

        // 2. Migrate existing data: buat anggota dari nama_pemilik yang sudah ada
        $existingPemilik = DB::table('blok_lahans')
            ->select('nama_pemilik')
            ->distinct()
            ->whereNotNull('nama_pemilik')
            ->where('nama_pemilik', '!=', '')
            ->get();

        foreach ($existingPemilik as $row) {
            $anggotaId = DB::table('anggotas')->insertGetId([
                'nama' => $row->nama_pemilik,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('blok_lahans')
                ->where('nama_pemilik', $row->nama_pemilik)
                ->update(['anggota_id' => $anggotaId]);
        }

        // 3. Hapus kolom nama_pemilik (sudah dipindah ke relasi)
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropColumn('nama_pemilik');
        });
    }

    public function down(): void
    {
        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->string('nama_pemilik', 100)->nullable()->after('nama_blok');
        });

        // Restore data dari relasi
        $bloks = DB::table('blok_lahans')
            ->join('anggotas', 'blok_lahans.anggota_id', '=', 'anggotas.id')
            ->select('blok_lahans.id', 'anggotas.nama')
            ->get();

        foreach ($bloks as $blok) {
            DB::table('blok_lahans')->where('id', $blok->id)->update(['nama_pemilik' => $blok->nama]);
        }

        Schema::table('blok_lahans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anggota_id');
        });
    }
};
