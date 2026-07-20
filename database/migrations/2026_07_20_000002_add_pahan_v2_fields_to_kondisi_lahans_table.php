<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $table->decimal('curah_hujan_mm_bulanan', 6, 1)->nullable()->after('curah_hujan_kategori')
                ->comment('Curah hujan bulanan dalam mm/bulan');
            $table->string('periode_curah_hujan', 50)->nullable()->after('curah_hujan_mm_bulanan')
                ->comment('Periode data curah hujan, misal: Juni 2026');
            $table->enum('sumber_curah_hujan', ['manual', 'open-meteo', 'alat_ukur', 'lainnya'])
                ->nullable()->after('periode_curah_hujan')
                ->comment('Sumber data curah hujan');
            $table->enum('metode_pengukuran_ph', ['kertas_lakmus', 'ph_meter', 'estimasi', 'laboratorium'])
                ->nullable()->after('ph_tanah')
                ->comment('Metode pengukuran pH tanah');
            $table->enum('status_verifikasi_gejala', ['belum_diverifikasi', 'terverifikasi', 'perlu_konfirmasi'])
                ->default('belum_diverifikasi')->after('catatan_observasi')
                ->comment('Status verifikasi gejala visual');
        });
    }

    public function down(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $table->dropColumn([
                'curah_hujan_mm_bulanan',
                'periode_curah_hujan',
                'sumber_curah_hujan',
                'metode_pengukuran_ph',
                'status_verifikasi_gejala',
            ]);
        });
    }
};
