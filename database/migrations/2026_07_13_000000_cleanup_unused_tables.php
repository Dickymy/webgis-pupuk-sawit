<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus tabel-tabel yang tidak lagi digunakan:
     * - users & password_reset_tokens (diganti admins)
     * - jobs, job_batches, failed_jobs (tidak pakai queue)
     * - kriteria_lahans (sudah merge ke blok_lahans)
     * - rule_bases (diganti rule_bases_lanjutan)
     * - rekomendasi_spks (diganti rekomendasi_rbs)
     */
    public function up(): void
    {
        Schema::dropIfExists('rekomendasi_spks');
        Schema::dropIfExists('kriteria_lahans');
        Schema::dropIfExists('rule_bases');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }

    public function down(): void
    {
        // Tidak perlu recreate — tabel ini deprecated
    }
};
