<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus kolom-kolom yang sudah tidak dipakai di aplikasi.
 *
 * kondisi_lahans:
 *   - kondisi_pelepah  → dihapus dari form & model $fillable
 *   - kondisi_tandan   → dihapus dari form & model $fillable
 *
 * rule_bases_lanjutan:
 *   - kondisi_ph_min         → tidak ada di SaveRuleBaseRequest
 *   - kondisi_ph_max         → tidak ada di SaveRuleBaseRequest
 *   - kondisi_kelembaban     → tidak ada di SaveRuleBaseRequest
 *   - kondisi_curah_hujan_kategori → tidak ada di SaveRuleBaseRequest (diganti min/max mm)
 *   - kondisi_musim          → tidak ada di SaveRuleBaseRequest
 *   - kondisi_drainase       → tidak ada di SaveRuleBaseRequest
 *   - kondisi_defisiensi     → tidak ada di SaveRuleBaseRequest
 *   - kondisi_pelepah        → tidak ada di SaveRuleBaseRequest
 *   - kondisi_tandan         → tidak ada di SaveRuleBaseRequest
 *   - ada_serangan_hama      → tidak ada di SaveRuleBaseRequest
 *   - ada_gulma_dominan      → tidak ada di SaveRuleBaseRequest
 *   - kondisi_intermediate   → tidak ada di SaveRuleBaseRequest
 *   - prasyarat_intermediate → tidak ada di SaveRuleBaseRequest
 *   - jenis_pupuk_pendukung  → tidak ada di SaveRuleBaseRequest
 *   - dosis_anjuran          → tidak ada di SaveRuleBaseRequest
 *   - metode_aplikasi        → tidak ada di SaveRuleBaseRequest
 *   - waktu_aplikasi         → tidak ada di SaveRuleBaseRequest
 *   - keterangan_rule        → digantikan catatan_validasi
 *   - versi_rule             → tidak dipakai di UI
 *   - divalidasi_oleh        → tidak dipakai di UI
 *   - tanggal_validasi       → tidak dipakai di UI
 *   - kategori_kesimpulan    → tidak ada di SaveRuleBaseRequest
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── kondisi_lahans ────────────────────────────────────
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $drop = [];

            foreach (['kondisi_pelepah', 'kondisi_tandan'] as $col) {
                if (Schema::hasColumn('kondisi_lahans', $col)) {
                    $drop[] = $col;
                }
            }

            if ($drop) {
                $table->dropColumn($drop);
            }
        });

        // ── rule_bases_lanjutan ───────────────────────────────
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $candidates = [
                'kondisi_ph_min',
                'kondisi_ph_max',
                'kondisi_kelembaban',
                'kondisi_curah_hujan_kategori',
                'kondisi_musim',
                'kondisi_drainase',
                'kondisi_defisiensi',
                'kondisi_pelepah',
                'kondisi_tandan',
                'ada_serangan_hama',
                'ada_gulma_dominan',
                'kondisi_intermediate',
                'prasyarat_intermediate',
                'jenis_pupuk_pendukung',
                'dosis_anjuran',
                'metode_aplikasi',
                'waktu_aplikasi',
                'keterangan_rule',
                'versi_rule',
                'divalidasi_oleh',
                'tanggal_validasi',
                'kategori_kesimpulan',
            ];

            $drop = [];
            foreach ($candidates as $col) {
                if (Schema::hasColumn('rule_bases_lanjutan', $col)) {
                    $drop[] = $col;
                }
            }

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        // Restore kondisi_lahans
        Schema::table('kondisi_lahans', function (Blueprint $table) {
            $table->enum('kondisi_pelepah', ['Normal', 'Patah/Menggantung', 'Kering Prematur', 'Pertumbuhan Terhambat'])->nullable()->after('warna_daun');
            $table->enum('kondisi_tandan', ['Normal', 'Kecil', 'Rontok Prematur', 'Busuk Pangkal', 'Tidak Ada Tandan'])->nullable()->after('kondisi_pelepah');
        });

        // Restore rule_bases_lanjutan
        Schema::table('rule_bases_lanjutan', function (Blueprint $table) {
            $table->decimal('kondisi_ph_min', 4, 2)->nullable();
            $table->decimal('kondisi_ph_max', 4, 2)->nullable();
            $table->string('kondisi_kelembaban', 50)->nullable();
            $table->string('kondisi_curah_hujan_kategori', 50)->nullable();
            $table->string('kondisi_musim', 50)->nullable();
            $table->string('kondisi_drainase', 50)->nullable();
            $table->string('kondisi_defisiensi', 50)->nullable();
            $table->string('kondisi_pelepah', 100)->nullable();
            $table->string('kondisi_tandan', 100)->nullable();
            $table->boolean('ada_serangan_hama')->nullable();
            $table->boolean('ada_gulma_dominan')->nullable();
            $table->json('kondisi_intermediate')->nullable();
            $table->json('prasyarat_intermediate')->nullable();
            $table->string('jenis_pupuk_pendukung', 100)->nullable();
            $table->string('dosis_anjuran', 150)->nullable();
            $table->string('metode_aplikasi', 255)->nullable();
            $table->string('waktu_aplikasi', 150)->nullable();
            $table->text('keterangan_rule')->nullable();
            $table->string('versi_rule', 20)->default('1.0');
            $table->string('divalidasi_oleh', 100)->nullable();
            $table->date('tanggal_validasi')->nullable();
            $table->string('kategori_kesimpulan', 50)->nullable();
        });
    }
};
