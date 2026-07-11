<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Migration ini di-skip karena:
        // Tabel kriteria_lahans sudah di-drop dan kolomnya dipindah ke blok_lahans
        // pada migrasi 2026_06_07_200000_merge_kriteria_into_blok_lahans_table
        // Tidak ada aksi yang perlu dilakukan.
    }

    public function down(): void
    {
        // No-op
    }
};
