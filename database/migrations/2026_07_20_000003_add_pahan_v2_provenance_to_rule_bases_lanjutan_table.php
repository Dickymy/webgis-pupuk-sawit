<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->string('kode_rule', 30)->nullable()->after('id')
                ->comment('Kode identifikasi rule unik, misal: VIS-N-01');
            $table->string('sumber_judul', 255)->nullable()->after('keterangan_rule');
            $table->string('sumber_penulis', 100)->nullable()->after('sumber_judul');
            $table->integer('sumber_tahun')->nullable()->after('sumber_penulis');
            $table->string('sumber_halaman', 50)->nullable()->after('sumber_tahun');
            $table->string('sumber_tabel', 50)->nullable()->after('sumber_halaman');
            $table->enum('tingkat_bukti', ['BUKU', 'JURNAL', 'AHLI', 'ADAPTASI_PENELITI'])
                ->default('ADAPTASI_PENELITI')->after('sumber_tabel');
            $table->string('versi_rule', 20)->default('1.0')->after('tingkat_bukti');
            $table->boolean('is_system_rule')->default(false)->after('versi_rule');
            $table->enum('status_validasi', ['TERVERIFIKASI_SUMBER', 'PERLU_VALIDASI_AHLI', 'EKSPERIMENTAL', 'NONAKTIF'])
                ->default('PERLU_VALIDASI_AHLI')->after('is_system_rule');
            $table->string('divalidasi_oleh', 100)->nullable()->after('status_validasi');
            $table->date('tanggal_validasi')->nullable()->after('divalidasi_oleh');
            $table->text('catatan_validasi')->nullable()->after('tanggal_validasi');
        });
    }

    public function down(): void
    {
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->dropColumn([
                'kode_rule',
                'sumber_judul',
                'sumber_penulis',
                'sumber_tahun',
                'sumber_halaman',
                'sumber_tabel',
                'tingkat_bukti',
                'versi_rule',
                'is_system_rule',
                'status_validasi',
                'divalidasi_oleh',
                'tanggal_validasi',
                'catatan_validasi',
            ]);
        });
    }
};
