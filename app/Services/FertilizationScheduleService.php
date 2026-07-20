<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;

/**
 * FertilizationScheduleService — Menyusun jadwal pemupukan.
 *
 * Aturan Pahan v2.3:
 * 1. Jadwal hanya dibuat jika status aplikasi layak.
 * 2. Data hujan numerik tersedia.
 * 3. Tahap 1 berstatus "Rencana".
 * 4. Tahap 2 berstatus "Menunggu Realisasi Tahap 1".
 * 5. Tahap 2 minimal 60 hari setelah realisasi tahap 1.
 * 6. Jangan membuat tanggal pasti tanpa data.
 * 7. Jangan menentukan Maret/September otomatis.
 * 8. Default: 50% / 50%.
 * 9. Pemisahan Urea-KCl 2-3 minggu dinonaktifkan (bukan aturan Pahan).
 *
 * Referensi: Pahan, 2013. Bab 9, hal. 157-159.
 */
class FertilizationScheduleService
{
    private const SPLIT_RATIO = [50, 50];

    private const MIN_INTERVAL_DAYS = 60;

    /**
     * Generate jadwal pemupukan berdasarkan konteks tanaman dan kelayakan.
     *
     * Pahan v2.5: Jadwal KOSONG ([]) jika belum layak.
     * Tahap persiapan (gulma/hama) menjadi prasyarat_persiapan, bukan tahap pemupukan bernomor.
     * total_urea/total_kcl berasal dari CurrentApplicationCalculator (tahap aktif saat ini).
     *
     * @param  array  $doseData  ['dosis_urea', 'dosis_kcl', 'total_urea', 'total_kcl']
     * @param  array  $windowResult  Output dari FertilizationWindowService::evaluate()
     * @param  array  $plantContext  Output dari PlantContextService::resolve()
     */
    public function generate(
        array $doseData,
        KondisiLahan $kondisi,
        BlokLahan $blok,
        array $windowResult,
        array $plantContext,
    ): array {
        // Rule 1: Jadwal hanya dibuat jika layak — jika tidak, return kosong
        if (! $windowResult['layak']) {
            return [];
        }

        // Rule 2: Data hujan numerik harus tersedia — jika tidak, return kosong
        if ($kondisi->curah_hujan_mm_bulanan === null) {
            return [];
        }

        $totalUrea = $doseData['total_urea'] ?? 0;
        $totalKcl = $doseData['total_kcl'] ?? 0;
        $dosisUrea = $doseData['dosis_urea'] ?? 0;
        $dosisKcl = $doseData['dosis_kcl'] ?? 0;

        if ($totalUrea <= 0 && $totalKcl <= 0) {
            return [];
        }

        $faseLabel = $plantContext['fase_label'] ?? 'Belum Ditentukan';
        $umur = $plantContext['umur'] ?? null;

        // Metode aplikasi berdasarkan umur saat observasi
        $metodeUrea = $this->getMetodeAplikasi($umur, 'urea');
        $metodeKcl = $this->getMetodeAplikasi($umur, 'kcl');

        $jadwal = [];

        // Pahan v2.5: Persiapan gulma/hama menjadi prasyarat, bukan tahap pemupukan bernomor
        $prasyaratPersiapan = null;
        if ($kondisi->ada_gulma_dominan || $kondisi->ada_serangan_hama) {
            $prasyaratPersiapan = $this->tahapPersiapan($kondisi);
        }

        // Pahan v2.5: Karena total_urea/total_kcl sudah dari CurrentApplicationCalculator
        // (yaitu jumlah tahap aktif saat ini), jadwal cukup satu entry operasional.
        $jadwal[] = [
            'tahap' => 1,
            'nama_tahap' => 'Aplikasi Urea + KCl — Tahap Aktif',
            'estimasi_waktu' => 'Saat curah hujan dalam rentang 100-250 mm/bulan',
            'urea_kg' => round($totalUrea, 2),
            'kcl_kg' => round($totalKcl, 2),
            'urea_per_pokok' => $dosisUrea > 0 ? round($totalUrea / max(1, (int) ($blok->luas_ha * $blok->sph)), 3) : 0,
            'kcl_per_pokok' => $dosisKcl > 0 ? round($totalKcl / max(1, (int) ($blok->luas_ha * $blok->sph)), 3) : 0,
            'metode_aplikasi' => $metodeUrea.' '.$metodeKcl,
            'catatan' => "Fase: {$faseLabel}. Aplikasi dilakukan saat curah hujan sesuai (100-250 mm/bulan). Pastikan piringan bersih sebelum pemupukan.",
            'status_tahap' => 'Rencana',
            'prasyarat_persiapan' => $prasyaratPersiapan,
        ];

        return $jadwal;
    }

    // REMOVED in Pahan v2.4: jadwalDitunda() and jadwalMenungguData()
    // Informasi penundaan hanya disimpan pada status_kelayakan_aplikasi dan alasan_kelayakan.
    // Jadwal_pemupukan HARUS kosong ([]) jika tidak layak.

    /**
     * Tahap persiapan (gulma/hama).
     */
    private function tahapPersiapan(KondisiLahan $kondisi): array
    {
        $metode = [];
        $catatan = [];

        if ($kondisi->ada_gulma_dominan) {
            $metode[] = 'Pembersihan gulma (ring weeding) pada piringan pokok.';
            $catatan[] = 'Piringan harus bersih (radius 1.5-2.0 meter) sebelum pemupukan.';
        }
        if ($kondisi->ada_serangan_hama) {
            $metode[] = 'Pengendalian hama secara terpadu (PHT).';
            $catatan[] = 'Pastikan hama terkendali sebelum pupuk ditabur.';
        }

        return [
            'tahap' => 1,
            'nama_tahap' => 'Tahap Persiapan: Pengendalian Hama dan Gulma',
            'estimasi_waktu' => '7-14 hari sebelum pemupukan',
            'persentase_urea' => 0,
            'persentase_kcl' => 0,
            'urea_kg' => 0,
            'kcl_kg' => 0,
            'urea_per_pokok' => 0,
            'kcl_per_pokok' => 0,
            'metode_aplikasi' => implode(' ', $metode),
            'catatan' => implode(' ', $catatan),
            'status_tahap' => 'Wajib Dilakukan',
        ];
    }

    /**
     * Metode aplikasi berdasarkan umur tanaman saat observasi.
     */
    private function getMetodeAplikasi(?int $umur, string $jenis): string
    {
        if ($umur !== null && $umur < 3) {
            return match ($jenis) {
                'urea' => 'Urea ditabur melingkar 30-50 cm dari pangkal batang.',
                'kcl' => 'KCl ditabur melingkar 30-50 cm dari pangkal batang di piringan bersih.',
            };
        }

        return match ($jenis) {
            'urea' => 'Urea ditabur melingkar di piringan bersih berjarak 1.5-2.0 m dari pangkal batang.',
            'kcl' => 'KCl ditabur melingkar berjarak 1.5-2.0 m dari pangkal batang.',
        };
    }
}
