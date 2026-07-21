<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use Carbon\Carbon;

/**
 * FertilizationRealizationService — Mengelola data realisasi pemupukan.
 *
 * Pahan v2.6 Perubahan:
 * - tahap_1_selesai ditentukan dari status record, bukan hanya keberadaan record
 * - Realisasi SEBAGIAN = Tahap 1 belum selesai
 * - Realisasi BATAL tidak dihitung
 * - Ringkasan mencakup persentase per pupuk
 * - Toleransi pembulatan: 0.01 kg
 * - Urea dan KCl dievaluasi secara independen
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 */
class FertilizationRealizationService
{
    private const MIN_INTERVAL_DAYS = 60;

    /**
     * Toleransi pembulatan untuk menentukan apakah rencana terpenuhi.
     */
    private const TOLERANCE_KG = 0.01;

    /**
     * Ambil ringkasan realisasi untuk blok lahan tertentu.
     *
     * Pahan v2.6: Gunakan rekomendasi_rbs_id dan tahun_program sebagai filter utama.
     * Jatuh ke filter tahun kalender jika tahun_program belum diisi.
     *
     * @return array{
     *   tahap_1_ada: bool,
     *   tahap_1_sebagian: bool,
     *   tahap_1_selesai: bool,
     *   tahap_1_batal: bool,
     *   tahap_1_tanggal: ?string,
     *   urea_rencana_tahap_1: float,
     *   kcl_rencana_tahap_1: float,
     *   urea_realisasi_tahap_1: float,
     *   kcl_realisasi_tahap_1: float,
     *   persentase_realisasi_tahap_1: float,
     *   total_urea_realisasi: float,
     *   total_kcl_realisasi: float,
     *   realisasi_tahap_1: array,
     *   realisasi_tahap_2: array,
     *   tanggal_minimum_tahap_2: ?string,
     *   interval_hari_sejak_tahap_1: ?int,
     *   interval_terpenuhi: bool
     * }
     */
    public function getRealizationSummary(BlokLahan $blok, ?int $rekomendasiRbsId = null, ?Carbon $analysisDate = null): array
    {
        $analysisDate = $analysisDate ?? now();
        $tahunBerjalan = $analysisDate->year;

        // Query realisasi aktif (non-batal) untuk blok ini
        $query = RealisasiPemupukan::where('blok_lahan_id', $blok->id)
            ->where('status_realisasi', '!=', RealisasiPemupukan::STATUS_BATAL)
            ->orderBy('tahap')
            ->orderBy('tanggal_realisasi');

        // Filter berdasarkan rekomendasi spesifik jika diberikan
        if ($rekomendasiRbsId) {
            $query->where('rekomendasi_rbs_id', $rekomendasiRbsId);
        } else {
            // Fallback: filter tahun program atau tahun kalender
            $query->where(function ($q) use ($tahunBerjalan) {
                $q->where('tahun_program', $tahunBerjalan)
                    ->orWhere(function ($q2) use ($tahunBerjalan) {
                        $q2->whereNull('tahun_program')
                            ->whereYear('tanggal_realisasi', $tahunBerjalan);
                    });
            });
        }

        $realisasis = $query->get();

        // Pisahkan per tahap
        $tahap1 = $realisasis->where('tahap', 1);
        $tahap2 = $realisasis->where('tahap', 2);

        // Hitung total realisasi aktif (semua tahap)
        $totalUrea = $realisasis->sum('urea_realisasi_kg');
        $totalKcl = $realisasis->sum('kcl_realisasi_kg');

        // === TAHAP 1: Evaluasi status berdasarkan record ===
        $tahap1Ada = $tahap1->isNotEmpty();
        $ureaRealisasiTahap1 = (float) $tahap1->sum('urea_realisasi_kg');
        $kclRealisasiTahap1 = (float) $tahap1->sum('kcl_realisasi_kg');
        $ureaRencanaTahap1 = (float) ($tahap1->max('urea_rencana_kg') ?? 0);
        $kclRencanaTahap1 = (float) ($tahap1->max('kcl_rencana_kg') ?? 0);

        // Tentukan status Tahap 1 berdasarkan total realisasi vs rencana
        $tahap1Selesai = $this->isTahapSelesai($tahap1, $ureaRealisasiTahap1, $kclRealisasiTahap1, $ureaRencanaTahap1, $kclRencanaTahap1);
        $tahap1Sebagian = $tahap1Ada && ! $tahap1Selesai;
        $tahap1Batal = ! $tahap1Ada && $this->adaRealisasiBatal($blok, 1, $rekomendasiRbsId, $tahunBerjalan);

        // Persentase realisasi Tahap 1
        $maxRencana = max($ureaRencanaTahap1, $kclRencanaTahap1);
        $persentaseTahap1 = 0;
        if ($maxRencana > 0) {
            $pctUrea = $ureaRencanaTahap1 > 0 ? ($ureaRealisasiTahap1 / $ureaRencanaTahap1) * 100 : 100;
            $pctKcl = $kclRencanaTahap1 > 0 ? ($kclRealisasiTahap1 / $kclRencanaTahap1) * 100 : 100;
            $persentaseTahap1 = round(min($pctUrea, $pctKcl), 1);
        }

        // Tanggal realisasi Tahap 1 terakhir (yang menyelesaikan tahap)
        $tahap1Tanggal = $tahap1->max('tanggal_realisasi');

        // Hitung interval dan tanggal minimum tahap 2
        $tanggalMinTahap2 = null;
        $intervalHari = null;
        $intervalTerpenuhi = false;

        if ($tahap1Tanggal) {
            $tglTahap1 = Carbon::parse($tahap1Tanggal);
            $tanggalMinTahap2 = $tglTahap1->copy()->addDays(self::MIN_INTERVAL_DAYS)->toDateString();
            $intervalHari = (int) $tglTahap1->diffInDays($analysisDate);
            $intervalTerpenuhi = $intervalHari >= self::MIN_INTERVAL_DAYS;
        }

        return [
            // Pahan v2.6: Status detail Tahap 1
            'tahap_1_ada' => $tahap1Ada,
            'tahap_1_sebagian' => $tahap1Sebagian,
            'tahap_1_selesai' => $tahap1Selesai,
            'tahap_1_batal' => $tahap1Batal,
            'tahap_1_tanggal' => $tahap1Tanggal ? (string) $tahap1Tanggal : null,
            'urea_rencana_tahap_1' => round($ureaRencanaTahap1, 2),
            'kcl_rencana_tahap_1' => round($kclRencanaTahap1, 2),
            'urea_realisasi_tahap_1' => round($ureaRealisasiTahap1, 2),
            'kcl_realisasi_tahap_1' => round($kclRealisasiTahap1, 2),
            'persentase_realisasi_tahap_1' => $persentaseTahap1,
            // Total realisasi semua tahap
            'total_urea_realisasi' => round((float) $totalUrea, 2),
            'total_kcl_realisasi' => round((float) $totalKcl, 2),
            // Detail per tahap
            'urea_realisasi_tahap_2' => round((float) $tahap2->sum('urea_realisasi_kg'), 2),
            'kcl_realisasi_tahap_2' => round((float) $tahap2->sum('kcl_realisasi_kg'), 2),
            'realisasi_tahap_1' => $tahap1->toArray(),
            'realisasi_tahap_2' => $tahap2->toArray(),
            // Interval
            'tanggal_minimum_tahap_2' => $tanggalMinTahap2,
            'interval_hari_sejak_tahap_1' => $intervalHari,
            'interval_terpenuhi' => $intervalTerpenuhi,
        ];
    }

    /**
     * Tentukan apakah Tahap 1 dianggap selesai.
     *
     * Aturan Pahan v2.7:
     * - JANGAN langsung selesai hanya karena ada record berstatus SELESAI
     * - Gunakan total kumulatif terhadap rencana resmi
     * - Urea dan KCl dievaluasi independen
     * - Jika salah satu pupuk belum memenuhi rencana, tahap belum selesai
     * - Beberapa record SEBAGIAN yang totalnya memenuhi rencana boleh menyelesaikan tahap
     */
    private function isTahapSelesai(
        $records,
        float $ureaTotalRealisasi,
        float $kclTotalRealisasi,
        float $ureaRencana,
        float $kclRencana
    ): bool {
        if ($records->isEmpty()) {
            return false;
        }

        // Jika tidak ada rencana, tidak bisa menentukan selesai
        if ($ureaRencana <= 0 && $kclRencana <= 0) {
            return false;
        }

        // v2.7: Cek apakah total realisasi sudah memenuhi rencana (kedua pupuk)
        // Status SELESAI pada record SAJA tidak cukup — harus dicek jumlah aktual
        $ureaTerpenuhi = $ureaRencana <= 0 || ($ureaTotalRealisasi >= ($ureaRencana - self::TOLERANCE_KG));
        $kclTerpenuhi = $kclRencana <= 0 || ($kclTotalRealisasi >= ($kclRencana - self::TOLERANCE_KG));

        return $ureaTerpenuhi && $kclTerpenuhi;
    }

    /**
     * Cek apakah ada realisasi yang dibatalkan untuk tahap tertentu.
     */
    private function adaRealisasiBatal(BlokLahan $blok, int $tahap, ?int $rekomendasiRbsId, int $tahun): bool
    {
        $query = RealisasiPemupukan::where('blok_lahan_id', $blok->id)
            ->where('tahap', $tahap)
            ->where('status_realisasi', RealisasiPemupukan::STATUS_BATAL);

        if ($rekomendasiRbsId) {
            $query->where('rekomendasi_rbs_id', $rekomendasiRbsId);
        } else {
            $query->where(function ($q) use ($tahun) {
                $q->where('tahun_program', $tahun)
                    ->orWhere(function ($q2) use ($tahun) {
                        $q2->whereNull('tahun_program')
                            ->whereYear('tanggal_realisasi', $tahun);
                    });
            });
        }

        return $query->exists();
    }

    /**
     * Ambil ringkasan realisasi berbasis program pemupukan (Pahan v2.8).
     *
     * Method utama yang disarankan: mengisolasi realisasi berdasarkan program,
     * bukan tahun kalender atau rekomendasi tertentu.
     *
     * @return array Sama seperti getRealizationSummary() dengan tambahan metadata program
     */
    public function getRealizationSummaryForProgram(
        ProgramPemupukan $program,
        ?Carbon $analysisDate = null
    ): array {
        $analysisDate = $analysisDate ?? now();

        // Query realisasi aktif (non-batal) untuk PROGRAM ini
        $realisasis = RealisasiPemupukan::where('program_pemupukan_id', $program->id)
            ->where('status_realisasi', '!=', RealisasiPemupukan::STATUS_BATAL)
            ->orderBy('tahap')
            ->orderBy('tanggal_realisasi')
            ->get();

        // Pisahkan per tahap
        $tahap1 = $realisasis->where('tahap', 1);
        $tahap2 = $realisasis->where('tahap', 2);

        // Hitung total realisasi aktif
        $totalUrea = $realisasis->sum('urea_realisasi_kg');
        $totalKcl = $realisasis->sum('kcl_realisasi_kg');

        // === TAHAP 1: Evaluasi status ===
        $tahap1Ada = $tahap1->isNotEmpty();
        $ureaRealisasiTahap1 = (float) $tahap1->sum('urea_realisasi_kg');
        $kclRealisasiTahap1 = (float) $tahap1->sum('kcl_realisasi_kg');
        $ureaRencanaTahap1 = (float) ($tahap1->max('urea_rencana_kg') ?? 0);
        $kclRencanaTahap1 = (float) ($tahap1->max('kcl_rencana_kg') ?? 0);

        $tahap1Selesai = $this->isTahapSelesai($tahap1, $ureaRealisasiTahap1, $kclRealisasiTahap1, $ureaRencanaTahap1, $kclRencanaTahap1);
        $tahap1Sebagian = $tahap1Ada && ! $tahap1Selesai;
        $tahap1Batal = ! $tahap1Ada && RealisasiPemupukan::where('program_pemupukan_id', $program->id)
            ->where('tahap', 1)
            ->where('status_realisasi', RealisasiPemupukan::STATUS_BATAL)
            ->exists();

        // Persentase realisasi Tahap 1
        $maxRencana = max($ureaRencanaTahap1, $kclRencanaTahap1);
        $persentaseTahap1 = 0;
        if ($maxRencana > 0) {
            $pctUrea = $ureaRencanaTahap1 > 0 ? ($ureaRealisasiTahap1 / $ureaRencanaTahap1) * 100 : 100;
            $pctKcl = $kclRencanaTahap1 > 0 ? ($kclRealisasiTahap1 / $kclRencanaTahap1) * 100 : 100;
            $persentaseTahap1 = round(min($pctUrea, $pctKcl), 1);
        }

        $tahap1Tanggal = $tahap1->max('tanggal_realisasi');

        // Interval dan tanggal minimum tahap 2
        $tanggalMinTahap2 = null;
        $intervalHari = null;
        $intervalTerpenuhi = false;

        if ($tahap1Tanggal) {
            $tglTahap1 = Carbon::parse($tahap1Tanggal);
            $tanggalMinTahap2 = $tglTahap1->copy()->addDays(self::MIN_INTERVAL_DAYS)->toDateString();
            $intervalHari = (int) $tglTahap1->diffInDays($analysisDate);
            $intervalTerpenuhi = $intervalHari >= self::MIN_INTERVAL_DAYS;
        }

        return [
            'tahap_1_ada' => $tahap1Ada,
            'tahap_1_sebagian' => $tahap1Sebagian,
            'tahap_1_selesai' => $tahap1Selesai,
            'tahap_1_batal' => $tahap1Batal,
            'tahap_1_tanggal' => $tahap1Tanggal ? (string) $tahap1Tanggal : null,
            'urea_rencana_tahap_1' => round($ureaRencanaTahap1, 2),
            'kcl_rencana_tahap_1' => round($kclRencanaTahap1, 2),
            'urea_realisasi_tahap_1' => round($ureaRealisasiTahap1, 2),
            'kcl_realisasi_tahap_1' => round($kclRealisasiTahap1, 2),
            'persentase_realisasi_tahap_1' => $persentaseTahap1,
            'total_urea_realisasi' => round((float) $totalUrea, 2),
            'total_kcl_realisasi' => round((float) $totalKcl, 2),
            'urea_realisasi_tahap_2' => round((float) $tahap2->sum('urea_realisasi_kg'), 2),
            'kcl_realisasi_tahap_2' => round((float) $tahap2->sum('kcl_realisasi_kg'), 2),
            'realisasi_tahap_1' => $tahap1->toArray(),
            'realisasi_tahap_2' => $tahap2->toArray(),
            'tanggal_minimum_tahap_2' => $tanggalMinTahap2,
            'interval_hari_sejak_tahap_1' => $intervalHari,
            'interval_terpenuhi' => $intervalTerpenuhi,
            // Pahan v2.8: metadata program
            'program_pemupukan_id' => $program->id,
            'tahun_program' => $program->tahun_program,
            'mode_ringkasan' => 'PROGRAM_BASED',
        ];
    }

    /**
     * Hitung sisa kebutuhan tahunan setelah realisasi.
     */
    public function calculateRemaining(
        float $totalEstimasiTahunanUrea,
        float $totalEstimasiTahunanKcl,
        array $realizationSummary
    ): array {
        $sisaUrea = max(0, $totalEstimasiTahunanUrea - $realizationSummary['total_urea_realisasi']);
        $sisaKcl = max(0, $totalEstimasiTahunanKcl - $realizationSummary['total_kcl_realisasi']);

        $kebutuhanTerpenuhi = ($sisaUrea <= 0 && $sisaKcl <= 0);

        return [
            'urea_sisa_tahunan' => round($sisaUrea, 2),
            'kcl_sisa_tahunan' => round($sisaKcl, 2),
            'kebutuhan_tahunan_terpenuhi' => $kebutuhanTerpenuhi,
            'persentase_urea_terpenuhi' => $totalEstimasiTahunanUrea > 0
                ? round(($realizationSummary['total_urea_realisasi'] / $totalEstimasiTahunanUrea) * 100, 1)
                : 0,
            'persentase_kcl_terpenuhi' => $totalEstimasiTahunanKcl > 0
                ? round(($realizationSummary['total_kcl_realisasi'] / $totalEstimasiTahunanKcl) * 100, 1)
                : 0,
        ];
    }

    /**
     * Cek apakah total realisasi melebihi kebutuhan tahunan.
     */
    public function exceedsAnnualRequirement(
        float $totalEstimasiTahunanUrea,
        float $totalEstimasiTahunanKcl,
        array $realizationSummary
    ): bool {
        return $realizationSummary['total_urea_realisasi'] > $totalEstimasiTahunanUrea
            || $realizationSummary['total_kcl_realisasi'] > $totalEstimasiTahunanKcl;
    }
}
