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
        // Rule 1: Jadwal hanya dibuat jika layak
        if (! $windowResult['layak']) {
            return $this->jadwalDitunda($windowResult, $plantContext);
        }

        // Rule 2: Data hujan numerik harus tersedia
        if ($kondisi->curah_hujan_mm_bulanan === null) {
            return $this->jadwalMenungguData($plantContext);
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

        // Rule 8: Default 50/50
        $persen1 = self::SPLIT_RATIO[0];
        $persen2 = self::SPLIT_RATIO[1];

        $jadwal = [];

        // Tahap persiapan jika ada gulma/hama
        if ($kondisi->ada_gulma_dominan || $kondisi->ada_serangan_hama) {
            $jadwal[] = $this->tahapPersiapan($kondisi);
        }

        // Rule 3: Tahap 1 - Rencana
        $jadwal[] = [
            'tahap' => count($jadwal) + 1,
            'nama_tahap' => "Tahap 1: Aplikasi Urea + KCl ({$persen1}%)",
            'estimasi_waktu' => 'Saat curah hujan dalam rentang 100-250 mm/bulan',
            'persentase_urea' => $persen1,
            'persentase_kcl' => $persen1,
            'urea_kg' => round(($totalUrea * $persen1) / 100, 2),
            'kcl_kg' => round(($totalKcl * $persen1) / 100, 2),
            'urea_per_pokok' => round(($dosisUrea * $persen1) / 100, 3),
            'kcl_per_pokok' => round(($dosisKcl * $persen1) / 100, 3),
            'metode_aplikasi' => $metodeUrea.' '.$metodeKcl,
            'catatan' => "Fase: {$faseLabel}. Aplikasi dilakukan saat curah hujan sesuai (100-250 mm/bulan). Pastikan piringan bersih sebelum pemupukan.",
            'status_tahap' => 'Rencana',
        ];

        // Rule 4: Tahap 2 - Menunggu Realisasi Tahap 1
        $jadwal[] = [
            'tahap' => count($jadwal) + 1,
            'nama_tahap' => "Tahap 2: Aplikasi Urea + KCl ({$persen2}%)",
            'estimasi_waktu' => 'Minimal '.self::MIN_INTERVAL_DAYS.' hari setelah realisasi Tahap 1',
            'persentase_urea' => $persen2,
            'persentase_kcl' => $persen2,
            'urea_kg' => round(($totalUrea * $persen2) / 100, 2),
            'kcl_kg' => round(($totalKcl * $persen2) / 100, 2),
            'urea_per_pokok' => round(($dosisUrea * $persen2) / 100, 3),
            'kcl_per_pokok' => round(($dosisKcl * $persen2) / 100, 3),
            'metode_aplikasi' => $metodeUrea.' '.$metodeKcl,
            'catatan' => "Fase: {$faseLabel}. Tunggu minimal ".self::MIN_INTERVAL_DAYS.' hari setelah realisasi Tahap 1. Pastikan curah hujan dalam rentang layak.',
            'status_tahap' => 'Menunggu Realisasi Tahap 1',
        ];

        return $jadwal;
    }

    /**
     * Jadwal saat aplikasi ditunda.
     */
    private function jadwalDitunda(array $windowResult, array $plantContext): array
    {
        $faseLabel = $plantContext['fase_label'] ?? 'Belum Ditentukan';
        $alasan = implode(' ', $windowResult['alasan'] ?? []);

        return [[
            'tahap' => 1,
            'nama_tahap' => 'Pemupukan Ditunda',
            'estimasi_waktu' => 'Setelah kondisi kelayakan terpenuhi',
            'persentase_urea' => 0,
            'persentase_kcl' => 0,
            'urea_kg' => 0,
            'kcl_kg' => 0,
            'urea_per_pokok' => 0,
            'kcl_per_pokok' => 0,
            'metode_aplikasi' => 'Tidak ada aplikasi saat ini.',
            'catatan' => "Fase: {$faseLabel}. Alasan penundaan: {$alasan}. Kebutuhan tahunan tetap berlaku dan akan dijadwalkan saat kondisi memenuhi syarat.",
            'status_tahap' => 'Ditunda',
        ]];
    }

    /**
     * Jadwal saat data hujan numerik belum tersedia.
     */
    private function jadwalMenungguData(array $plantContext): array
    {
        $faseLabel = $plantContext['fase_label'] ?? 'Belum Ditentukan';

        return [[
            'tahap' => 1,
            'nama_tahap' => 'Menunggu Data Curah Hujan',
            'estimasi_waktu' => 'Setelah data curah hujan numerik tersedia',
            'persentase_urea' => 0,
            'persentase_kcl' => 0,
            'urea_kg' => 0,
            'kcl_kg' => 0,
            'urea_per_pokok' => 0,
            'kcl_per_pokok' => 0,
            'metode_aplikasi' => 'Jadwal belum dapat disusun tanpa data curah hujan numerik.',
            'catatan' => "Fase: {$faseLabel}. Masukkan data curah hujan bulanan (mm/bulan) untuk menghasilkan jadwal pemupukan yang presisi.",
            'status_tahap' => 'Menunggu Data',
        ]];
    }

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
