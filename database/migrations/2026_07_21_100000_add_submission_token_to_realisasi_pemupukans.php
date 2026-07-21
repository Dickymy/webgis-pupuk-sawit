<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perlindungan double-submit: Tambah kolom submission_token pada realisasi_pemupukans.
 *
 * Token bersifat:
 * - Nullable (data lama tidak memiliki token)
 * - Unique (tidak boleh ada dua realisasi dengan token sama)
 * - Maksimal 64 karakter (UUID v4 = 36 chars)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (! Schema::hasColumn('realisasi_pemupukans', 'submission_token')) {
                $table->string('submission_token', 64)->nullable()->unique()
                    ->after('override_reason')
                    ->comment('Idempotency token untuk mencegah double-submit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_pemupukans', function (Blueprint $table) {
            if (Schema::hasColumn('realisasi_pemupukans', 'submission_token')) {
                $table->dropUnique(['submission_token']);
                $table->dropColumn('submission_token');
            }
        });
    }
};
