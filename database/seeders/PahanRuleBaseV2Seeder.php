<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

/**
 * PahanRuleBaseV2Seeder — Menambahkan metadata provenance ke rule sistem
 * dan menandai rule lama yang belum memiliki sumber.
 *
 * Seeder ini IDEMPOTENT: aman dijalankan berulang kali.
 * - Tidak menghapus rule lama
 * - Menggunakan updateOrCreate berdasarkan indikasi_masalah
 * - Rule buatan admin tidak terpengaruh
 */
class PahanRuleBaseV2Seeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // LANGKAH 1: Tandai SEMUA rule lama yang belum punya sumber
        // ═══════════════════════════════════════════════════════════════
        RuleBaseLanjutan::whereNull('kode_rule')
            ->update([
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
                'tingkat_bukti'   => 'ADAPTASI_PENELITI',
                'versi_rule'      => '1.0',
                'is_system_rule'  => true,
            ]);

        $this->command->info('Rule lama tanpa kode ditandai PERLU_VALIDASI_AHLI.');

        // ═══════════════════════════════════════════════════════════════
        // LANGKAH 2: Update rule yang memiliki dasar dari Pahan
        // ═══════════════════════════════════════════════════════════════
        $pahanRules = $this->getPahanRuleUpdates();

        $updated = 0;
        foreach ($pahanRules as $rule) {
            $kodeRule = $rule['kode_rule'];
            $indikasi = $rule['match_indikasi'];
            unset($rule['match_indikasi']);

            // Prioritas: cari berdasarkan kode_rule dulu, lalu indikasi_masalah
            $existing = RuleBaseLanjutan::where('kode_rule', $kodeRule)->first()
                ?? RuleBaseLanjutan::where('indikasi_masalah', $indikasi)->first();

            if ($existing) {
                $existing->update($rule);
                $updated++;
            }
        }

        $this->command->info("Metadata provenance diupdate untuk {$updated} rule.");

        // ═══════════════════════════════════════════════════════════════
        // LANGKAH 3: Bersihkan teks dosis legacy yang bertentangan
        //            dengan PahanDoseReferenceService pada rule Urea/KCl
        // ═══════════════════════════════════════════════════════════════
        $this->cleanLegacyDoseText();

        // ═══════════════════════════════════════════════════════════════
        // LANGKAH 4: Statistik
        // ═══════════════════════════════════════════════════════════════
        $total = RuleBaseLanjutan::count();
        $terverifikasi = RuleBaseLanjutan::where('status_validasi', 'TERVERIFIKASI_SUMBER')->count();
        $perluValidasi = RuleBaseLanjutan::where('status_validasi', 'PERLU_VALIDASI_AHLI')->count();

        $this->command->info("Total rule: {$total} | Terverifikasi: {$terverifikasi} | Perlu validasi: {$perluValidasi}");
    }

    /**
     * Bersihkan teks dosis legacy pada rule yang berkaitan dengan Urea/KCl.
     * Dosis kuantitatif Urea/KCl sekarang HANYA berasal dari PahanDoseReferenceService.
     * Rule hanya menentukan diagnosis, tindakan, dan prioritas.
     */
    private function cleanLegacyDoseText(): void
    {
        $cleaned = 0;

        // Rule yang pupuk utamanya Urea — dosis_anjuran harus non-kuantitatif
        $ureaRules = RuleBaseLanjutan::where('jenis_pupuk_utama', 'LIKE', '%Urea%')
            ->where(function ($q) {
                $q->where('dosis_anjuran', 'LIKE', '%kg%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%kurangi dosis%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%Kurangi dosis%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%dosis penuh%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%70%%');
            })->get();

        foreach ($ureaRules as $rule) {
            $rule->update([
                'dosis_anjuran' => 'Besaran dosis Urea dihitung otomatis oleh sistem menggunakan tabel referensi Pahan (2013, Tabel 9.13 & 9.14) berdasarkan fase dan umur tanaman.',
            ]);
            $cleaned++;
        }

        // Rule yang pupuk utamanya KCl — dosis_anjuran harus non-kuantitatif
        $kclRules = RuleBaseLanjutan::where('jenis_pupuk_utama', 'LIKE', '%KCl%')
            ->where(function ($q) {
                $q->where('dosis_anjuran', 'LIKE', '%kg%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%kurangi dosis%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%Kurangi dosis%')
                  ->orWhere('dosis_anjuran', 'LIKE', '%dosis penuh%');
            })->get();

        foreach ($kclRules as $rule) {
            $rule->update([
                'dosis_anjuran' => 'Besaran dosis KCl dihitung otomatis oleh sistem menggunakan tabel referensi Pahan (2013, Tabel 9.13 & 9.14) berdasarkan fase dan umur tanaman.',
            ]);
            $cleaned++;
        }

        // Rule musim hujan yang menyebut "dosis penuh"
        $dosisLegacy = RuleBaseLanjutan::where('dosis_anjuran', 'LIKE', '%Dosis penuh%')
            ->orWhere('dosis_anjuran', 'LIKE', '%dosis penuh%')
            ->get();

        foreach ($dosisLegacy as $rule) {
            $rule->update([
                'dosis_anjuran' => 'Gunakan dosis tahunan sesuai rentang referensi Pahan berdasarkan fase dan umur tanaman.',
            ]);
            $cleaned++;
        }

        $this->command->info("Teks dosis legacy dibersihkan: {$cleaned} rule diupdate.");
    }

    /**
     * Daftar rule yang memiliki dasar dari Pahan (2013) beserta metadata.
     */
    private function getPahanRuleUpdates(): array
    {
        $sumberPahan = [
            'sumber_judul'   => 'Panduan Lengkap Kelapa Sawit',
            'sumber_penulis' => 'Iyung Pahan',
            'sumber_tahun'   => 2013,
        ];

        return [
            // ─── Defisiensi N: Klorosis umum ─────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Nitrogen — Klorosis Umum',
                'kode_rule'       => 'VIS-N-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Gejala klorosis umum pada daun tua merupakan indikasi defisiensi N yang diakui luas (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi N ringan ─────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Nitrogen Ringan — Pertumbuhan Lambat',
                'kode_rule'       => 'VIS-N-02',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Hijau pucat pada daun muda dan pertumbuhan lambat — indikasi N ringan (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi K: Orange Frond ──────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Kalium — Orange Frond (OF)',
                'kode_rule'       => 'VIS-K-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Orange Frond (OF) adalah gejala khas defisiensi K berat pada kelapa sawit (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi K sedang ─────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Kalium Sedang — Marginal Chlorosis',
                'kode_rule'       => 'VIS-K-02',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Klorosis tepi daun (marginal chlorosis) indikasi defisiensi K sedang (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi Mg ───────────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Magnesium — Interveinal Chlorosis',
                'kode_rule'       => 'VIS-MG-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Klorosis antar tulang daun pada daun tua — indikasi defisiensi Mg (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi B ────────────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Boron — Pucuk Abnormal / Blind Pocket',
                'kode_rule'       => 'VIS-B-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Blind pocket dan daun tombak tidak membuka — gejala khas defisiensi B (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── pH Sangat Masam ─────────────────────────────────────────
            [
                'match_indikasi'  => 'pH Sangat Masam — Penghambatan Penyerapan Unsur Hara',
                'kode_rule'       => 'TANAH-PH-01',
                'sumber_halaman'  => '155-157',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Pengapuran diperlukan pada pH <4.5 untuk meningkatkan ketersediaan hara (Pahan 2013, hal. 155-157).',
            ] + $sumberPahan,

            // ─── pH Masam ────────────────────────────────────────────────
            [
                'match_indikasi'  => 'pH Masam — Efisiensi Pupuk Rendah',
                'kode_rule'       => 'TANAH-PH-02',
                'sumber_halaman'  => '155-157',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'pH 4.5-5.5 memerlukan pengapuran ringan untuk efisiensi pemupukan (Pahan 2013, hal. 155-157).',
            ] + $sumberPahan,

            // ─── Drainase Buruk ──────────────────────────────────────────
            [
                'match_indikasi'  => 'Waterlogging — Akar Kekurangan Oksigen dan Leaching Hara',
                'kode_rule'       => 'LINGK-DR-01',
                'sumber_halaman'  => '157-159',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Pemupukan tanah tidak efektif saat genangan — tunda hingga drainase diperbaiki (Pahan 2013, hal. 157-159).',
            ] + $sumberPahan,

            // ─── Kekeringan ──────────────────────────────────────────────
            [
                'match_indikasi'  => 'Cekaman Kekeringan — Efisiensi Pupuk Sangat Rendah',
                'kode_rule'       => 'LINGK-KER-01',
                'sumber_halaman'  => '157-159',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Pupuk kimia tidak efektif saat sangat kering — risiko volatilisasi (Pahan 2013, hal. 157-159).',
            ] + $sumberPahan,

            // ─── Kemarau normal ──────────────────────────────────────────
            [
                'match_indikasi'  => 'Kemarau — Perlu Penyesuaian Dosis Pupuk',
                'kode_rule'       => 'LINGK-KER-02',
                'sumber_halaman'  => '157-159',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'ADAPTASI_PENELITI',
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Adaptasi: Pahan menyebut tunda saat kering, angka 70% adalah interpretasi peneliti. Dalam pahan-v2 dosis tahunan TIDAK dikurangi otomatis.',
            ] + $sumberPahan,

            // ─── Musim Hujan Optimal ─────────────────────────────────────
            [
                'match_indikasi'  => 'Kondisi Optimal untuk Pemupukan — Musim Hujan Normal',
                'kode_rule'       => 'LINGK-OPT-01',
                'sumber_halaman'  => '157-159',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Curah hujan 100-250 mm/bulan adalah jendela optimal aplikasi pupuk (Pahan 2013, Bab 9).',
            ] + $sumberPahan,

            // ─── TBM Defisiensi N ────────────────────────────────────────
            [
                'match_indikasi'  => 'Bibit/TBM Defisiensi Nitrogen — Pertumbuhan Vegetatif Terhambat',
                'kode_rule'       => 'UMUR-TBM-01',
                'sumber_halaman'  => '163-164',
                'sumber_tabel'    => '9.13',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Dosis TBM lebih rendah dari TM — lihat Tabel 9.13 (Pahan 2013, hal. 163).',
            ] + $sumberPahan,

            // ─── Tanaman Tua Renta ───────────────────────────────────────
            [
                'match_indikasi'  => 'Tanaman Tua Renta — Efisiensi Pemupukan Sangat Rendah',
                'kode_rule'       => 'UMUR-TUA-01',
                'sumber_halaman'  => '163-164',
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'ADAPTASI_PENELITI',
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Pahan tidak secara eksplisit menyebut batas umur 25 tahun untuk berhenti memupuk. Interpretasi bahwa efisiensi menurun drastis berdasarkan logika agronomis umum.',
            ] + $sumberPahan,

            // ─── Defisiensi P ────────────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Fosfor — Nekrosis Ujung Daun',
                'kode_rule'       => 'VIS-P-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Nekrosis ujung daun dan warna kecoklatan pada pelepah tua — indikasi defisiensi P (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi Fe ───────────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Besi (Fe) — pH Terlalu Tinggi',
                'kode_rule'       => 'VIS-FE-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Klorosis antar tulang pada daun muda dengan pH tinggi — indikasi defisiensi Fe (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Defisiensi Zn ───────────────────────────────────────────
            [
                'match_indikasi'  => 'Defisiensi Seng (Zn) — Klorosis Daun Muda',
                'kode_rule'       => 'VIS-ZN-01',
                'sumber_halaman'  => null,
                'sumber_tabel'    => null,
                'tingkat_bukti'   => 'ADAPTASI_PENELITI',
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Defisiensi Zn tidak secara detail dibahas pada Tabel 9.5 Pahan. Memerlukan sumber eksternal/validasi ahli untuk penggunaan sebagai rule utama.',
            ] + $sumberPahan,

            // ─── Rontok Tandan ───────────────────────────────────────────
            [
                'match_indikasi'  => 'Rontok Tandan Prematur — Defisiensi B atau K',
                'kode_rule'       => 'VIS-BK-01',
                'sumber_halaman'  => '145-148',
                'sumber_tabel'    => '9.5',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Rontok tandan prematur berkaitan dengan defisiensi B dan K (Pahan 2013, Tabel 9.5).',
            ] + $sumberPahan,

            // ─── Kondisi Normal ──────────────────────────────────────────
            [
                'match_indikasi'  => 'Kondisi Optimal — Pemupukan Standar',
                'kode_rule'       => 'NORMAL-01',
                'sumber_halaman'  => '163-164',
                'sumber_tabel'    => '9.13, 9.14',
                'tingkat_bukti'   => 'BUKU',
                'status_validasi' => 'TERVERIFIKASI_SUMBER',
                'versi_rule'      => '2.0',
                'is_system_rule'  => true,
                'catatan_validasi' => 'Pada kondisi optimal, dosis pemupukan mengikuti tabel standar Pahan (2013, Tabel 9.13 & 9.14).',
            ] + $sumberPahan,
        ];
    }
}
