<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->string('kondisi_pelepah', 100)->nullable()->after('kondisi_kategori_umur')
                ->comment('Kondisi pelepah target untuk matching');
            $table->string('kondisi_tandan', 100)->nullable()->after('kondisi_pelepah')
                ->comment('Kondisi tandan target untuk matching');
            $table->boolean('ada_serangan_hama')->nullable()->after('kondisi_tandan')
                ->comment('NULL = tidak relevan, true = harus ada hama');
        });
    }

    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn(['kondisi_pelepah', 'kondisi_tandan', 'ada_serangan_hama']);
        });
    }
};
