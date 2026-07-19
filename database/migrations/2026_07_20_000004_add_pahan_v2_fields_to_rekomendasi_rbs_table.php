<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            // Snapshot fase & umur
            $table->string('fase_tanaman_snapshot', 10)->nullable()->after('notifikasi_data');
            $table->integer('umur_tanaman_snapshot')->nullable()->after('fase_tanaman_snapshot');

            // Rentang dosis referensi Pahan
            $table->decimal('urea_min_kg_per_pokok_tahun', 5, 2)->nullable()->after('umur_tanaman_snapshot');
            $table->decimal('urea_max_kg_per_pokok_tahun', 5, 2)->nullable()->after('urea_min_kg_per_pokok_tahun');
            $table->decimal('urea_estimasi_kg_per_pokok_tahun', 5, 2)->nullable()->after('urea_max_kg_per_pokok_tahun');
            $table->decimal('kcl_min_kg_per_pokok_tahun', 5, 2)->nullable()->after('urea_estimasi_kg_per_pokok_tahun');
            $table->decimal('kcl_max_kg_per_pokok_tahun', 5, 2)->nullable()->after('kcl_min_kg_per_pokok_tahun');
            $table->decimal('kcl_estimasi_kg_per_pokok_tahun', 5, 2)->nullable()->after('kcl_max_kg_per_pokok_tahun');

            // Strategi & snapshot
            $table->string('strategi_estimasi_dosis', 30)->nullable()->after('kcl_estimasi_kg_per_pokok_tahun');
            $table->integer('jumlah_pokok_snapshot')->nullable()->after('strategi_estimasi_dosis');
            $table->json('dasar_perhitungan_json')->nullable()->after('jumlah_pokok_snapshot');
            $table->json('peringatan_json')->nullable()->after('dasar_perhitungan_json');

            // Skor keandalan
            $table->integer('kelengkapan_data_score')->nullable()->after('peringatan_json');
            $table->string('kategori_keandalan', 30)->nullable()->after('kelengkapan_data_score');
            $table->json('rincian_skor_json')->nullable()->after('kategori_keandalan');

            // Status kondisi & kelayakan (dua dimensi)
            $table->string('status_kondisi_tanaman', 50)->nullable()->after('rincian_skor_json');
            $table->string('status_kelayakan_aplikasi', 50)->nullable()->after('status_kondisi_tanaman');
            $table->text('alasan_kelayakan')->nullable()->after('status_kelayakan_aplikasi');

            // Versi mesin
            $table->string('versi_mesin_rekomendasi', 30)->default('legacy-v1')->after('alasan_kelayakan');
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_rbs', function (Blueprint $table) {
            $table->dropColumn([
                'fase_tanaman_snapshot',
                'umur_tanaman_snapshot',
                'urea_min_kg_per_pokok_tahun',
                'urea_max_kg_per_pokok_tahun',
                'urea_estimasi_kg_per_pokok_tahun',
                'kcl_min_kg_per_pokok_tahun',
                'kcl_max_kg_per_pokok_tahun',
                'kcl_estimasi_kg_per_pokok_tahun',
                'strategi_estimasi_dosis',
                'jumlah_pokok_snapshot',
                'dasar_perhitungan_json',
                'peringatan_json',
                'kelengkapan_data_score',
                'kategori_keandalan',
                'rincian_skor_json',
                'status_kondisi_tanaman',
                'status_kelayakan_aplikasi',
                'alasan_kelayakan',
                'versi_mesin_rekomendasi',
            ]);
        });
    }
};
