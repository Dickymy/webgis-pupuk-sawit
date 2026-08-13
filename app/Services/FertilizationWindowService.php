<?php

namespace App\Services;

use App\Models\KondisiLahan;
use Carbon\Carbon;

/**
 * FertilizationWindowService — Memeriksa kelayakan waktu aplikasi pupuk.
 *
 * Service ini mengatur WAKTU, bukan mengubah dosis tahunan.
 *
 * Referensi waktu aplikasi:
 * - PPKS 2025: curah hujan optimal 100-250 mm/bulan.
 * - Pradiko dkk. (PPKS 2021): tunda pada <60 atau >300 mm/bulan.
 * - Jeda 120 hari adalah adaptasi operasional dari frekuensi 2-3 aplikasi/tahun.
 */
class FertilizationWindowService
{
    // Status kelayakan
    public const LAYAK = 'LAYAK_DIJADWALKAN';

    public const TUNDA_HUJAN_RENDAH = 'TUNDA_HUJAN_RENDAH';

    public const TUNDA_HUJAN_TINGGI = 'TUNDA_HUJAN_TINGGI';

    public const TUNDA_TANAH_KERING = 'TUNDA_TANAH_KERING';

    public const TUNDA_INTERVAL = 'TUNDA_INTERVAL';

    public const PERLU_PERBAIKAN_DRAINASE = 'PERLU_PERBAIKAN_DRAINASE';

    public const TUNDA_KONDISI_LAHAN = 'TUNDA_KONDISI_LAHAN';

    public const PERLU_VERIFIKASI_DATA = 'PERLU_VERIFIKASI_DATA';

    public const TERLAMBAT = 'TERLAMBAT_PERLU_DIJADWALKAN';

    /**
     * Evaluasi kelayakan waktu aplikasi pupuk.
     *
     * @return array{
     *   status: string,
     *   layak: bool,
     *   alasan: array,
     *   curah_hujan_mm: ?float,
     *   interval_hari: ?int,
     *   terlambat: bool
     * }
     */
    public function evaluate(KondisiLahan $kondisi, ?Carbon $tanggalRencana = null): array
    {
        $alasan = [];
        $statuses = [];
        $tanggalRencana = $tanggalRencana ?? now();

        $rainfallOptimalMin = config('fertilization.window.rainfall_optimal_min_mm', 100);
        $rainfallOptimalMax = config('fertilization.window.rainfall_optimal_max_mm', 250);
        $rainfallDeferBelow = config('fertilization.window.rainfall_defer_below_mm', 60);
        $rainfallDeferAbove = config('fertilization.window.rainfall_defer_above_mm', 300);
        $minInterval = config('fertilization.window.min_interval_days', 120);

        // 1. Cek curah hujan bulanan
        $curahHujan = $kondisi->curah_hujan_mm_bulanan;
        if ($curahHujan !== null) {
            if ($curahHujan < $rainfallDeferBelow) {
                $statuses[] = self::TUNDA_HUJAN_RENDAH;
                $alasan[] = "Curah hujan {$curahHujan} mm/bulan (< {$rainfallDeferBelow} mm) — tunda aplikasi sampai kelembapan tanah memadai.";
            } elseif ($curahHujan > $rainfallDeferAbove) {
                $statuses[] = self::TUNDA_HUJAN_TINGGI;
                $alasan[] = "Curah hujan {$curahHujan} mm/bulan (> {$rainfallDeferAbove} mm) — tunda karena risiko pencucian dan aliran permukaan.";
            } elseif ($curahHujan < $rainfallOptimalMin || $curahHujan > $rainfallOptimalMax) {
                $statuses[] = self::PERLU_VERIFIKASI_DATA;
                $alasan[] = "Curah hujan {$curahHujan} mm/bulan berada di luar rentang optimal {$rainfallOptimalMin}-{$rainfallOptimalMax} mm, tetapi belum mencapai batas tunda. Verifikasi kelembapan tanah dan prakiraan hujan sebelum aplikasi.";
            }
        } else {
            // Fallback: cek kategori curah hujan lama
            // PERBAIKAN: Kategori tanpa nilai numerik TIDAK boleh langsung menyatakan layak
            $kategori = $kondisi->curah_hujan_kategori;
            if ($kategori === 'Sangat Rendah') {
                $statuses[] = self::TUNDA_HUJAN_RENDAH;
                $alasan[] = 'Curah hujan sangat rendah (kategori, tanpa data numerik) — indikasi tunda, verifikasi nilai aktual disarankan.';
            } elseif ($kategori === 'Sangat Tinggi') {
                $statuses[] = self::TUNDA_HUJAN_TINGGI;
                $alasan[] = 'Curah hujan sangat tinggi (kategori, tanpa data numerik) — indikasi tunda, verifikasi nilai aktual disarankan.';
            } elseif ($kategori === 'Rendah' || $kategori === 'Tinggi') {
                // Kategori Rendah/Tinggi tanpa numerik → tidak bisa memastikan layak
                $statuses[] = self::PERLU_VERIFIKASI_DATA;
                $alasan[] = "Curah hujan '{$kategori}' (hanya kategori, tanpa nilai numerik mm/bulan) — belum cukup untuk menentukan waktu pemupukan. Masukkan jumlah hujan dalam mm/bulan.";
            } elseif ($kategori === 'Normal') {
                // Kategori Normal tanpa numerik → perlu verifikasi karena range bisa luas
                $statuses[] = self::PERLU_VERIFIKASI_DATA;
                $alasan[] = "Curah hujan 'Normal' (hanya kategori, tanpa nilai numerik mm/bulan) — verifikasi apakah dalam rentang 100-250 mm.";
            }

            // Jika tidak ada data hujan sama sekali
            if ($curahHujan === null && $kategori === null) {
                $statuses[] = self::PERLU_VERIFIKASI_DATA;
                $alasan[] = 'Data curah hujan tidak tersedia — verifikasi kondisi lapangan diperlukan.';
            }
        }

        // 2. Cek kelembapan aktual tanah. Fakta lapangan mengungguli label musim.
        if ($kondisi->kelembaban_tanah === 'Sangat Kering') {
            $statuses[] = self::TUNDA_TANAH_KERING;
            $alasan[] = 'Tanah sangat kering — tunda pupuk tanah sampai kelembapan memadai.';
        } elseif ($kondisi->kelembaban_tanah === 'Kering') {
            $statuses[] = self::PERLU_VERIFIKASI_DATA;
            $alasan[] = 'Tanah kering — periksa kelembapan aktual dan peluang hujan sebelum aplikasi.';
        }

        // 3. Cek interval pemupukan terakhir
        $intervalHari = null;
        $terlambat = false; // Kolom kompatibilitas histori; status terlambat tidak dihitung otomatis.

        if ($kondisi->tanggal_pemupukan_terakhir) {
            $intervalHari = (int) $kondisi->tanggal_pemupukan_terakhir->diffInDays($tanggalRencana);

            if ($intervalHari < $minInterval) {
                $statuses[] = self::TUNDA_INTERVAL;
                $alasan[] = "Pemupukan terakhir {$intervalHari} hari lalu (< {$minInterval} hari) — jarak waktu terlalu pendek.";

            }
        }

        // 4. Cek drainase
        if ($kondisi->kondisi_drainase === 'Buruk — Tergenang') {
            $statuses[] = self::PERLU_PERBAIKAN_DRAINASE;
            $alasan[] = 'Lahan tergenang — pupuk tanah tidak efektif, perbaiki drainase terlebih dahulu.';
        }

        // Tentukan status final
        if (empty($statuses)) {
            $statusFinal = self::LAYAK;
        } else {
            // Prioritas: Drainase > Interval > Hujan > Verifikasi
            $priority = [
                self::PERLU_PERBAIKAN_DRAINASE => 5,
                self::TUNDA_INTERVAL => 4,
                self::TUNDA_HUJAN_TINGGI => 3,
                self::TUNDA_HUJAN_RENDAH => 3,
                self::TUNDA_TANAH_KERING => 3,
                self::PERLU_VERIFIKASI_DATA => 1,
            ];

            usort($statuses, fn ($a, $b) => ($priority[$b] ?? 0) - ($priority[$a] ?? 0));
            $statusFinal = $statuses[0];
        }

        $layak = $statusFinal === self::LAYAK || $statusFinal === self::TERLAMBAT;

        return [
            'status' => $statusFinal,
            'layak' => $layak,
            'alasan' => $alasan,
            'curah_hujan_mm' => $curahHujan,
            'interval_hari' => $intervalHari,
            'terlambat' => $terlambat,
        ];
    }

    /**
     * Label status kelayakan dalam bahasa Indonesia.
     */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            self::LAYAK => 'Siap Dipupuk',
            self::TUNDA_HUJAN_RENDAH => 'Belum Dipupuk — Hujan Terlalu Rendah',
            self::TUNDA_HUJAN_TINGGI => 'Belum Dipupuk — Hujan Terlalu Tinggi',
            self::TUNDA_TANAH_KERING => 'Belum Dipupuk — Tanah Sangat Kering',
            self::TUNDA_INTERVAL => 'Belum Dipupuk — Jarak Waktu Belum Cukup',
            self::PERLU_PERBAIKAN_DRAINASE => 'Belum Dipupuk — Perbaiki Saluran Air',
            self::TUNDA_KONDISI_LAHAN => 'Belum Dipupuk — Kondisi Lahan Tidak Memenuhi',
            self::PERLU_VERIFIKASI_DATA => 'Data Pemeriksaan Belum Lengkap',
            self::TERLAMBAT => 'Segera Dijadwalkan',
            default => $status,
        };
    }
}
