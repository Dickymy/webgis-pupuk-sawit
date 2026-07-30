<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan dua istilah observasi yang dipakai rule akademik N dan K.
     * SQLite dibiarkan tanpa ALTER karena enum Laravel pada lingkungan uji
     * tidak memerlukan perubahan tipe kolom.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE kondisi_lahans MODIFY warna_daun ENUM(
            'Hijau Normal',
            'Hijau Pucat',
            'Kuning Merata',
            'Kuning Tepi',
            'Kuning Antar Tulang',
            'Oranye/Kemerahan',
            'Coklat Ujung',
            'Bercak Nekrotik',
            'Daun Bawah Menguning',
            'Bercak Kuning/Transparan pada Daun Tua'
        ) NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('kondisi_lahans')
            ->where('warna_daun', 'Daun Bawah Menguning')
            ->update(['warna_daun' => 'Kuning Merata']);

        DB::table('kondisi_lahans')
            ->where('warna_daun', 'Bercak Kuning/Transparan pada Daun Tua')
            ->update(['warna_daun' => 'Kuning Tepi']);

        DB::statement("ALTER TABLE kondisi_lahans MODIFY warna_daun ENUM(
            'Hijau Normal',
            'Hijau Pucat',
            'Kuning Merata',
            'Kuning Tepi',
            'Kuning Antar Tulang',
            'Oranye/Kemerahan',
            'Coklat Ujung',
            'Bercak Nekrotik'
        ) NULL");
    }
};
