<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pahan v2.5: Upgrade tabel realisasi_pemupukans untuk mendukung
 * integrasi tahap dan tracking rencana vs realisasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            // Field baru untuk tracking tahap
            if (! Schema::hasColumn('realisasi_pemupukans', 'blok_lahan_id')) {
                $table->foreignId('blok_lahan_id')->nullable()->after('rekomendasi_rbs_id')
                    ->constrained('blok_lahans')->onDelete('cascade');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'tahap')) {
                $table->unsignedTinyInteger('tahap')->default(1)->after('admin_id')
                    ->comment('Nomor tahap pemupukan (1 atau 2)');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'urea_rencana_kg')) {
                $table->decimal('urea_rencana_kg', 10, 2)->default(0)->after('tanggal_realisasi');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'kcl_rencana_kg')) {
                $table->decimal('kcl_rencana_kg', 10, 2)->default(0)->after('urea_rencana_kg');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'urea_realisasi_kg')) {
                $table->decimal('urea_realisasi_kg', 10, 2)->default(0)->after('kcl_rencana_kg');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'kcl_realisasi_kg')) {
                $table->decimal('kcl_realisasi_kg', 10, 2)->default(0)->after('urea_realisasi_kg');
            }
            if (! Schema::hasColumn('realisasi_pemupukans', 'status_realisasi')) {
                $table->string('status_realisasi', 30)->default('SELESAI')->after('kcl_realisasi_kg')
                    ->comment('SELESAI, SEBAGIAN, BATAL');
            }
        });

        // Migrate data lama: copy jumlah_urea_realisasi → urea_realisasi_kg jika ada
        // Tangani masing-masing kolom secara terpisah (kolom mungkin tidak semua tersedia)
        $hasUreaLama = Schema::hasColumn('realisasi_pemupukans', 'jumlah_urea_realisasi');
        $hasKclLama = Schema::hasColumn('realisasi_pemupukans', 'jumlah_kcl_realisasi');

        if ($hasUreaLama && $hasKclLama) {
            DB::statement('
                UPDATE realisasi_pemupukans
                SET urea_realisasi_kg = jumlah_urea_realisasi,
                    kcl_realisasi_kg = jumlah_kcl_realisasi
                WHERE urea_realisasi_kg = 0 AND jumlah_urea_realisasi > 0
            ');
        } elseif ($hasUreaLama) {
            DB::statement('
                UPDATE realisasi_pemupukans
                SET urea_realisasi_kg = jumlah_urea_realisasi
                WHERE urea_realisasi_kg = 0 AND jumlah_urea_realisasi > 0
            ');
        } elseif ($hasKclLama) {
            DB::statement('
                UPDATE realisasi_pemupukans
                SET kcl_realisasi_kg = jumlah_kcl_realisasi
                WHERE kcl_realisasi_kg = 0 AND jumlah_kcl_realisasi > 0
            ');
        }
    }

    public function down(): void
    {
        // Drop foreign key SEBELUM drop column (fix MySQL constraint issue)
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (Schema::hasColumn('realisasi_pemupukans', 'blok_lahan_id')) {
                $table->dropForeign(['blok_lahan_id']);
            }
        });

        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            $columns = ['blok_lahan_id', 'tahap', 'urea_rencana_kg', 'kcl_rencana_kg', 'urea_realisasi_kg', 'kcl_realisasi_kg', 'status_realisasi'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('realisasi_pemupukans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
