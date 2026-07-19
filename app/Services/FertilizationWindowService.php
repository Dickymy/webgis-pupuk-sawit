<?php

namespace App\Services;

use App\Models\KondisiLahan;
use Carbon\Carbon;

/**
 * FertilizationWindowService — Memeriksa kelayakan waktu aplikasi pupuk.
 *
 * Service ini mengatur WAKTU, bukan mengubah dosis tahunan.
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 * - Curah hujan layak: 100–250 mm/bulan
 * - Interval minimal antar pupuk sejenis: 60 hari
 */
class FertilizationWindowService
{
    // Status kelayakan
    public const LAYAK = 'LAYAK_DIJADWALKAN';
    public const TUNDA_HUJAN_RENDAH = 'TUNDA_HUJAN_RENDAH';
    public const TUNDA_HUJAN_TINGGI = 'TUNDA_HUJAN_TINGGI';
    public const TUNDA_INTERVAL = 'TUNDA_INTERVAL';
    public const PERLU_PERBAIKAN_DRAINASE = 'PERLU_PERBAIKAN_DRAINASE';
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

        $rainfallMin = config('fertilization.window.rainfall_min_mm', 100);
        $rainfallMax = config('fertilization.window.rainfall_max_mm', 250);
        $minInterval = config('fertilization.window.min_interval_days', 60);
        $lateThreshold = config('fertilization.window.late_threshold_days', 120);

        // 1. Cek curah hujan bulanan
        $curahHujan = $kondisi->curah_hujan_mm_bulanan;
        if ($curahHujan !== null) {
            if ($curahHujan < $rainfallMin) {
                $statuses[] = self::TUNDA_HUJAN_RENDAH;
                $alasan[] = "Curah hujan {$curahHujan} mm/bulan (< {$rainfallMin} mm) — risiko efektivitas rendah, Urea mudah menguap.";
            } elseif ($curahHujan > $rainfallMax) {
                $statuses[] = self::TUNDA_HUJAN_TINGGI;
                $alasan[] = "Curah hujan {$curahHujan} mm/bulan (> {$rainfallMax} mm) — risiko pencucian dan aliran permukaan tinggi.";
            }
        } else {
            // Fallback: cek kategori curah hujan lama
            $kategori = $kondisi->curah_hujan_kategori;
            if ($kategori === 'Sangat Rendah') {
                $statuses[] = self::TUNDA_HUJAN_RENDAH;
                $alasan[] = "Curah hujan sangat rendah (kategori) — risiko efektivitas rendah.";
            } elseif ($kategori === 'Sangat Tinggi') {
                $statuses[] = self::TUNDA_HUJAN_TINGGI;
                $alasan[] = "Curah hujan sangat tinggi (kategori) — risiko pencucian hara.";
            }

            // Jika tidak ada data hujan sama sekali
            if ($curahHujan === null && $kategori === null) {
                $statuses[] = self::PERLU_VERIFIKASI_DATA;
                $alasan[] = "Data curah hujan tidak tersedia — verifikasi kondisi lapangan diperlukan.";
            }
        }

        // 2. Cek interval pemupukan terakhir
        $intervalHari = null;
        $terlambat = false;

        if ($kondisi->tanggal_pemupukan_terakhir) {
            $intervalHari = $kondisi->tanggal_pemupukan_terakhir->diffInDays($tanggalRencana);

            if ($intervalHari < $minInterval) {
                $statuses[] = self::TUNDA_INTERVAL;
                $alasan[] = "Pemupukan terakhir {$intervalHari} hari lalu (< {$minInterval} hari) — interval terlalu pendek.";
            } elseif ($intervalHari > $lateThreshold) {
                $terlambat = true;
                $alasan[] = "Pemupukan terlambat ({$intervalHari} hari sejak terakhir) — perlu segera dijadwalkan.";
            }
        }

        // 3. Cek drainase
        if ($kondisi->kondisi_drainase === 'Buruk — Tergenang') {
            $statuses[] = self::PERLU_PERBAIKAN_DRAINASE;
            $alasan[] = "Lahan tergenang — pupuk tanah tidak efektif, perbaiki drainase terlebih dahulu.";
        }

        // Tentukan status final
        if (empty($statuses)) {
            $statusFinal = $terlambat ? self::TERLAMBAT : self::LAYAK;
        } else {
            // Prioritas: Drainase > Interval > Hujan > Verifikasi
            $priority = [
                self::PERLU_PERBAIKAN_DRAINASE => 5,
                self::TUNDA_INTERVAL           => 4,
                self::TUNDA_HUJAN_TINGGI       => 3,
                self::TUNDA_HUJAN_RENDAH       => 3,
                self::PERLU_VERIFIKASI_DATA    => 1,
            ];

            usort($statuses, fn($a, $b) => ($priority[$b] ?? 0) - ($priority[$a] ?? 0));
            $statusFinal = $statuses[0];
        }

        $layak = $statusFinal === self::LAYAK || $statusFinal === self::TERLAMBAT;

        return [
            'status'         => $statusFinal,
            'layak'          => $layak,
            'alasan'         => $alasan,
            'curah_hujan_mm' => $curahHujan,
            'interval_hari'  => $intervalHari,
            'terlambat'      => $terlambat,
        ];
    }

    /**
     * Label status kelayakan dalam bahasa Indonesia.
     */
    public static function labelStatus(string $status): string
    {
        return match ($status) {
            self::LAYAK                    => 'Layak Dijadwalkan',
            self::TUNDA_HUJAN_RENDAH       => 'Tunda — Curah Hujan Rendah',
            self::TUNDA_HUJAN_TINGGI       => 'Tunda — Curah Hujan Tinggi',
            self::TUNDA_INTERVAL           => 'Tunda — Interval Terlalu Pendek',
            self::PERLU_PERBAIKAN_DRAINASE => 'Tunda — Drainase Perlu Diperbaiki',
            self::PERLU_VERIFIKASI_DATA    => 'Perlu Verifikasi Data',
            self::TERLAMBAT                => 'Terlambat — Perlu Dijadwalkan',
            default                        => $status,
        };
    }
}
