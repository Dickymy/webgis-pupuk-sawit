<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus kolom ph_tanah dan gejala_defisiensi dari tabel kondisi_lahans.
 *
 * Alasan:
 * - ph_tanah  : tidak ada di form observasi UI, tidak bisa diisi pengguna,
 *               dan kondisi_ph_min/max sudah dihapus dari rule_bases_lanjutan.
 * - gejala_defisiensi : tidak ada di form observasi UI, tidak bisa diisi pengguna,
 *                       dan kondisi_defisiensi sudah dihapus dari rule_bases_lanjutan.
 *
 * Kedua kolom ini adalah "yatim piatu" — ada di DB tapi tidak pernah terisi
 * karena tidak ada input UI-nya. Penghapusan ini merapikan skema agar
 * konsisten dengan tampilan aplikasi dan dokumentasi skripsi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('kondisi_lahans', 'ph_tanah')) {
                $drop[] = 'ph_tanah';
            }
            if (Schema::hasColumn('kondisi_lahans', 'metode_pengukuran_ph')) {
                $drop[] = 'metode_pengukuran_ph';
            }
            if (Schema::hasColumn('kondisi_lahans', 'gejala_defisiensi')) {
                $drop[] = 'gejala_defisiensi';
            }

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            if (! Schema::hasColumn('kondisi_lahans', 'ph_tanah')) {
                $table->decimal('ph_tanah', 4, 2)->nullable()->after('tanggal_observasi')
                    ->comment('rentang 3.0–8.0');
            }
            if (! Schema::hasColumn('kondisi_lahans', 'metode_pengukuran_ph')) {
                $table->string('metode_pengukuran_ph', 50)->nullable()->after('ph_tanah');
            }
            if (! Schema::hasColumn('kondisi_lahans', 'gejala_defisiensi')) {
                $table->json('gejala_defisiensi')->nullable()->after('warna_daun')
                    ->comment('array: N,P,K,Mg,B,Fe,Zn');
            }
        });
    }
};
