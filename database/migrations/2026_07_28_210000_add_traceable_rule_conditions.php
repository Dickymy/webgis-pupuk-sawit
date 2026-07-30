<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            if (! Schema::hasColumn('rule_bases_lanjutan', 'kondisi_curah_hujan_min_mm')) {
                $table->decimal('kondisi_curah_hujan_min_mm', 6, 1)
                    ->nullable()
                    ->after('kondisi_curah_hujan_kategori');
            }

            if (! Schema::hasColumn('rule_bases_lanjutan', 'kondisi_curah_hujan_max_mm')) {
                $table->decimal('kondisi_curah_hujan_max_mm', 6, 1)
                    ->nullable()
                    ->after('kondisi_curah_hujan_min_mm');
            }
        });

        if (DB::getDriverName() === 'mysql') {
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
                'Bercak Kuning/Transparan pada Daun Tua',
                'Tepi Daun Tua Menguning pada Bagian Terbuka',
                'Daun Muda Berbentuk Kait atau Memendek'
            ) NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('kondisi_lahans')
                ->where('warna_daun', 'Tepi Daun Tua Menguning pada Bagian Terbuka')
                ->update(['warna_daun' => 'Kuning Tepi']);

            DB::table('kondisi_lahans')
                ->where('warna_daun', 'Daun Muda Berbentuk Kait atau Memendek')
                ->update(['warna_daun' => 'Hijau Pucat']);

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

        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('rule_bases_lanjutan', 'kondisi_curah_hujan_min_mm')
                    ? 'kondisi_curah_hujan_min_mm'
                    : null,
                Schema::hasColumn('rule_bases_lanjutan', 'kondisi_curah_hujan_max_mm')
                    ? 'kondisi_curah_hujan_max_mm'
                    : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
