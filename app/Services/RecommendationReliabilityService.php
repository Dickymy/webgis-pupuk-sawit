<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;

/**
 * RecommendationReliabilityService — Menghitung skor Kelengkapan & Keandalan Data.
 *
 * Skor ini BUKAN menyatakan akurasi agronomis. Skor hanya menggambarkan
 * seberapa lengkap dan andal data yang mendasari rekomendasi.
 *
 * Referensi bobot: Lihat config/fertilization.php → reliability_weights
 */
class RecommendationReliabilityService
{
    /**
     * Hitung skor kelengkapan dan keandalan data.
     *
     * @return array{
     *   score: int,
     *   kategori: string,
     *   rincian: array,
     *   saran_peningkatan: array
     * }
     */
    public function calculate(BlokLahan $blok, KondisiLahan $kondisi, array $rulesTerpicu = []): array
    {
        $weights = config('fertilization.reliability_weights');
        $rincian = [];
        $saran = [];
        $totalScore = 0;

        // 1. Identitas blok: luas, SPH, tahun/tanggal tanam (max 15)
        $identitasScore = 0;
        if ($blok->luas_ha > 0) $identitasScore += 5;
        if ($blok->sph > 0) $identitasScore += 5;
        if ($blok->tahun_tanam) $identitasScore += 5;
        else $saran[] = 'Lengkapi tahun tanam blok lahan.';

        $identitasScore = min($identitasScore, $weights['identitas_blok']);
        $rincian['identitas_blok'] = ['skor' => $identitasScore, 'max' => $weights['identitas_blok']];
        $totalScore += $identitasScore;

        // 2. Fase tanaman terverifikasi (max 10)
        $faseScore = 0;
        if ($blok->fase_tanaman !== null) {
            $faseScore = $weights['fase_terverifikasi'];
        } else {
            $umur = $blok->umur_tanaman;
            if ($umur !== null && $umur !== 3) {
                $faseScore = 5; // Auto-suggest tapi belum diverifikasi
            }
            $saran[] = 'Verifikasi fase tanaman (TBM/TM) untuk blok ini.';
        }
        $rincian['fase_terverifikasi'] = ['skor' => $faseScore, 'max' => $weights['fase_terverifikasi']];
        $totalScore += $faseScore;

        // 3. pH dan metode pengukuran (max 10)
        $phScore = 0;
        if ($kondisi->ph_tanah !== null) {
            $phScore += 6;
            if ($kondisi->metode_pengukuran_ph !== null && $kondisi->metode_pengukuran_ph !== 'estimasi') {
                $phScore += 4;
            } else {
                $saran[] = 'Gunakan pH meter atau kertas lakmus untuk pengukuran pH lebih akurat.';
            }
        } else {
            $saran[] = 'Lakukan pengukuran pH tanah.';
        }
        $phScore = min($phScore, $weights['ph_dan_metode']);
        $rincian['ph_dan_metode'] = ['skor' => $phScore, 'max' => $weights['ph_dan_metode']];
        $totalScore += $phScore;

        // 4. Curah hujan bulanan + periode (max 15)
        $hujanScore = 0;
        if ($kondisi->curah_hujan_mm_bulanan !== null) {
            $hujanScore += 10;
            if ($kondisi->periode_curah_hujan !== null) {
                $hujanScore += 3;
            }
            if ($kondisi->sumber_curah_hujan !== null) {
                $hujanScore += 2;
            }
        } elseif ($kondisi->curah_hujan_kategori !== null) {
            $hujanScore += 5; // Hanya kategori, kurang presisi
            $saran[] = 'Masukkan nilai curah hujan bulanan (mm) untuk presisi lebih tinggi.';
        } else {
            $saran[] = 'Lengkapi data curah hujan bulanan.';
        }
        $hujanScore = min($hujanScore, $weights['curah_hujan']);
        $rincian['curah_hujan'] = ['skor' => $hujanScore, 'max' => $weights['curah_hujan']];
        $totalScore += $hujanScore;

        // 5. Tanggal pemupukan terakhir (max 10)
        $tglScore = 0;
        if ($kondisi->tanggal_pemupukan_terakhir !== null) {
            $tglScore = $weights['tgl_pemupukan'];
        } else {
            $saran[] = 'Catat tanggal pemupukan terakhir untuk evaluasi interval.';
        }
        $rincian['tgl_pemupukan'] = ['skor' => $tglScore, 'max' => $weights['tgl_pemupukan']];
        $totalScore += $tglScore;

        // 6. Data visual: daun, pelepah, defisiensi (max 15)
        $visualScore = 0;
        if ($kondisi->warna_daun !== null) $visualScore += 6;
        else $saran[] = 'Observasi warna daun tanaman.';

        if ($kondisi->kondisi_pelepah !== null) $visualScore += 4;
        if (!empty($kondisi->gejala_defisiensi)) $visualScore += 5;

        $visualScore = min($visualScore, $weights['data_visual']);
        $rincian['data_visual'] = ['skor' => $visualScore, 'max' => $weights['data_visual']];
        $totalScore += $visualScore;

        // 7. Drainase, gulma, hama (max 10)
        $lingkunganScore = 0;
        if ($kondisi->kondisi_drainase !== null) $lingkunganScore += 5;
        // Gulma dan hama selalu terisi (boolean default)
        $lingkunganScore += 5;

        $lingkunganScore = min($lingkunganScore, $weights['drainase_gulma_hama']);
        $rincian['drainase_gulma_hama'] = ['skor' => $lingkunganScore, 'max' => $weights['drainase_gulma_hama']];
        $totalScore += $lingkunganScore;

        // 8. Rule terpicu memiliki sumber (max 10)
        $ruleScore = 0;
        if (!empty($rulesTerpicu)) {
            $bersumber = collect($rulesTerpicu)->filter(fn($r) =>
                $r->sumber_penulis !== null || $r->tingkat_bukti === 'BUKU'
            )->count();
            $total = count($rulesTerpicu);
            $ruleScore = $total > 0 ? (int) round(($bersumber / $total) * $weights['rule_bersumber']) : 0;
        }
        $rincian['rule_bersumber'] = ['skor' => $ruleScore, 'max' => $weights['rule_bersumber']];
        $totalScore += $ruleScore;

        // 9. Validasi ahli atau laboratorium (max 5)
        $validasiScore = 0;
        if ($kondisi->status_verifikasi_gejala === 'terverifikasi') {
            $validasiScore = $weights['validasi_ahli'];
        }
        $rincian['validasi_ahli'] = ['skor' => $validasiScore, 'max' => $weights['validasi_ahli']];
        $totalScore += $validasiScore;

        // Clamp
        $totalScore = max(0, min(100, $totalScore));

        // Kategori
        $kategori = $this->getKategori($totalScore);

        return [
            'score'              => $totalScore,
            'kategori'           => $kategori,
            'rincian'            => $rincian,
            'saran_peningkatan'  => $saran,
        ];
    }

    /**
     * Tentukan kategori dari skor.
     */
    private function getKategori(int $score): string
    {
        $categories = config('fertilization.reliability_categories', []);

        foreach ($categories as $cat) {
            if ($score >= $cat['min'] && $score <= $cat['max']) {
                return $cat['label'];
            }
        }

        return 'Data Tidak Cukup';
    }
}
