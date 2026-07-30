<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rule_bases_lanjutan')) {
            DB::table('rule_bases_lanjutan')
                ->where('is_system_rule', true)
                ->where(function ($query) {
                    $query->whereNull('kode_rule')
                        ->orWhereNotIn('kode_rule', [
                            'VIS-N-01',
                            'VIS-K-02',
                            'VIS-MG-01',
                            'VIS-B-01',
                            'WAKTU-HUJAN-RENDAH',
                            'WAKTU-HUJAN-OPTIMAL',
                            'WAKTU-HUJAN-TINGGI',
                        ]);
                })
                ->delete();
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('rule_bases_lanjutan', 'terakhir_diuji_pada') ? 'terakhir_diuji_pada' : null,
            Schema::hasColumn('rule_bases_lanjutan', 'hasil_uji_terakhir') ? 'hasil_uji_terakhir' : null,
            Schema::hasColumn('rule_bases_lanjutan', 'diuji_oleh') ? 'diuji_oleh' : null,
        ]));

        if ($columns !== []) {
            Schema::table('rule_bases_lanjutan', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        // Artefak fitur yang sudah dibatalkan tidak dibuat kembali.
    }
};
