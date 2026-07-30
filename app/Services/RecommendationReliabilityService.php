<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;

/**
 * RecommendationReliabilityService — Menghitung kelengkapan data pendukung.
 *
 * Skor ini BUKAN menyatakan akurasi agronomis. Skor hanya menggambarkan
 * seberapa lengkap data yang mendasari rekomendasi.
 *
 * Referensi bobot: Lihat config/fertilization.php → reliability_weights
 */
class RecommendationReliabilityService
{
    /**
     * Hitung kelengkapan data pendukung.
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

        // 1. Identitas blok: luas, SPH, tahun/tanggal tanam (max 20)
        $identitasScore = 0;
        if ($blok->luas_ha > 0) {
            $identitasScore += 7;
        }
        if ($blok->sph > 0) {
            $identitasScore += 7;
        }
        if ($blok->tahun_tanam) {
            $identitasScore += 6;
        } else {
            $saran[] = 'Lengkapi tahun tanam blok lahan.';
        }

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
            $saran[] = 'Lengkapi fase tanaman pada data blok.';
        }
        $rincian['fase_terverifikasi'] = ['skor' => $faseScore, 'max' => $weights['fase_terverifikasi']];
        $totalScore += $faseScore;

        // 3. Data curah hujan (max 30). Musim hanya konteks dan tidak
        // menambah nilai kelengkapan karena bukan hasil pengukuran.
        $hujanScore = 0;
        if ($kondisi->curah_hujan_mm_bulanan !== null) {
            $hujanScore += 20;
            if ($kondisi->periode_curah_hujan !== null) {
                $hujanScore += 5;
            }
            if ($kondisi->sumber_curah_hujan !== null) {
                $hujanScore += 5;
            }
        } elseif ($kondisi->curah_hujan_kategori !== null) {
            $hujanScore += 10;
            $saran[] = 'Gunakan data curah hujan dalam mm jika catatan pengukuran tersedia.';
        } else {
            $saran[] = 'Lengkapi data curah hujan untuk menilai waktu pemupukan.';
        }

        $hujanScore = min($hujanScore, $weights['curah_hujan']);
        $rincian['curah_hujan'] = ['skor' => $hujanScore, 'max' => $weights['curah_hujan']];
        $totalScore += $hujanScore;

        // 4. Tanggal pemupukan terakhir (max 15)
        $tglScore = 0;
        $hasFertilizationHistory = $kondisi->tanggal_pemupukan_terakhir !== null
            || ($blok->exists && RealisasiPemupukan::query()
                ->where('blok_lahan_id', $blok->id)
                ->aktif()
                ->exists());
        if ($hasFertilizationHistory) {
            $tglScore = $weights['tgl_pemupukan'];
        } else {
            $saran[] = 'Catat tanggal pemupukan terakhir untuk pemeriksaan jarak waktu.';
        }
        $rincian['tgl_pemupukan'] = ['skor' => $tglScore, 'max' => $weights['tgl_pemupukan']];
        $totalScore += $tglScore;

        // 5. Fakta daun (max 15). Foto hanya dokumentasi dan tidak
        // menentukan kelengkapan fakta untuk diagnosis.
        $visualScore = 0;
        if ($kondisi->warna_daun !== null) {
            $visualScore = $weights['data_visual'];
        } else {
            $saran[] = 'Periksa kondisi daun tanaman.';
        }
        $visualScore = min($visualScore, $weights['data_visual']);
        $rincian['data_visual'] = ['skor' => $visualScore, 'max' => $weights['data_visual']];
        $totalScore += $visualScore;

        // 6. Kondisi lapangan yang memengaruhi kesiapan aplikasi (max 10)
        $lingkunganScore = 0;
        if ($kondisi->kelembaban_tanah !== null) {
            $lingkunganScore += 5;
        } else {
            $saran[] = 'Catat kelembapan tanah saat observasi.';
        }
        if ($kondisi->kondisi_drainase !== null) {
            $lingkunganScore += 5;
        } else {
            $saran[] = 'Periksa kondisi drainase blok.';
        }
        $lingkunganScore = min($lingkunganScore, $weights['kondisi_lapangan']);
        $rincian['kondisi_lapangan'] = ['skor' => $lingkunganScore, 'max' => $weights['kondisi_lapangan']];
        $totalScore += $lingkunganScore;
        // Clamp
        $totalScore = max(0, min(100, $totalScore));

        // Kategori
        $kategori = $this->getKategori($totalScore);

        return [
            'score' => $totalScore,
            'kategori' => $kategori,
            'rincian' => $rincian,
            'saran_peningkatan' => $saran,
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

        return 'Perlu Dilengkapi';
    }
}
