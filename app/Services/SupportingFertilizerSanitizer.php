<?php

namespace App\Services;

use App\Models\RuleBaseLanjutan;

/**
 * SupportingFertilizerSanitizer — Menyaring dosis pupuk pendukung.
 *
 * Dosis kuantitatif Urea dan MOP/KCl hanya berasal dari PahanDoseReferenceService.
 *
 * Untuk pupuk pendukung (Kieserit, Boraks, Dolomit, FeSO4, ZnSO4, KNO3, NPK,
 * silika, kompos, pupuk daun, pupuk mikro):
 * - Angka hanya boleh tampil jika status_validasi = TERVERIFIKASI_SUMBER atau TERVERIFIKASI_AHLI
 * - Jika belum valid, tampilkan pesan rekomendasi umum tanpa angka
 *
 * Referensi: Pahan, 2013. Bab 9.
 */
class SupportingFertilizerSanitizer
{
    /**
     * Pupuk utama yang dosisnya berasal dari PahanDoseReferenceService.
     */
    private const PUPUK_UTAMA = [
        'Urea',
        'KCl',
        'MOP',
    ];

    /**
     * Status validasi yang membolehkan angka tampil.
     */
    private const VALID_STATUSES = [
        'TERVERIFIKASI_SUMBER',
        'TERVERIFIKASI_AHLI',
    ];

    /**
     * Pesan default untuk pupuk pendukung yang belum tervalidasi.
     */
    private const PESAN_BELUM_VALID = 'Pupuk pendukung dapat dipertimbangkan berdasarkan indikasi yang ditemukan. Besaran dosis perlu ditentukan melalui analisis tanah/daun atau validasi ahli.';

    /**
     * Sanitasi daftar rekomendasi pupuk dari rule yang terpicu.
     *
     * @param  array  $rulesTerpicu  Collection of RuleBaseLanjutan
     * @return array Daftar pupuk yang sudah disanitasi
     */
    public function sanitize(array $rulesTerpicu): array
    {
        $result = [];

        foreach ($rulesTerpicu as $rule) {
            if (! $rule instanceof RuleBaseLanjutan) {
                continue;
            }

            $pupukUtama = $rule->jenis_pupuk_utama ?? '';
            $isPupukUtama = $this->isPupukUtama($pupukUtama);

            if ($isPupukUtama) {
                // Pupuk utama (Urea/KCl): dosis dari PahanDoseReferenceService
                $result[] = [
                    'jenis_utama' => $pupukUtama,
                    'jenis_pendukung' => $rule->jenis_pupuk_pendukung,
                    'dosis' => 'Sesuai kebutuhan tahunan Pahan 2013',
                    'metode' => $rule->metode_aplikasi,
                    'waktu' => $rule->waktu_aplikasi,
                    'status_validasi' => 'REFERENSI_PAHAN',
                    'angka_boleh_tampil' => true,
                ];
            } else {
                // Pupuk pendukung: cek status validasi
                $isValid = $this->isValidated($rule);

                $result[] = [
                    'jenis_utama' => $pupukUtama,
                    'jenis_pendukung' => $rule->jenis_pupuk_pendukung,
                    'dosis' => $isValid ? $rule->dosis_anjuran : self::PESAN_BELUM_VALID,
                    'metode' => $rule->metode_aplikasi,
                    'waktu' => $rule->waktu_aplikasi,
                    'status_validasi' => $rule->status_validasi ?? 'BELUM_TERVALIDASI',
                    'angka_boleh_tampil' => $isValid,
                    'sumber' => $isValid ? $this->getSumberMetadata($rule) : null,
                ];
            }
        }

        return $result;
    }

    /**
     * Cek apakah pupuk termasuk pupuk utama.
     */
    private function isPupukUtama(string $namaPupuk): bool
    {
        foreach (self::PUPUK_UTAMA as $utama) {
            if (stripos($namaPupuk, $utama) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah rule memiliki validasi yang memadai.
     */
    private function isValidated(RuleBaseLanjutan $rule): bool
    {
        $status = $rule->status_validasi ?? null;

        if (! in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        if ($status === 'TERVERIFIKASI_SUMBER') {
            return $rule->sumber_judul !== null
                && $rule->sumber_penulis !== null
                && $rule->sumber_tahun !== null;
        }

        if ($status === 'TERVERIFIKASI_AHLI') {
            return $rule->divalidasi_oleh !== null
                && $rule->tanggal_validasi !== null;
        }

        return false;
    }

    /**
     * Ambil metadata sumber untuk pupuk yang tervalidasi.
     */
    private function getSumberMetadata(RuleBaseLanjutan $rule): ?array
    {
        if ($rule->status_validasi === 'TERVERIFIKASI_SUMBER') {
            return [
                'judul' => $rule->sumber_judul,
                'penulis' => $rule->sumber_penulis,
                'tahun' => $rule->sumber_tahun,
                'halaman' => $rule->sumber_halaman,
                'tabel' => $rule->sumber_tabel,
            ];
        }

        if ($rule->status_validasi === 'TERVERIFIKASI_AHLI') {
            return [
                'divalidasi_oleh' => $rule->divalidasi_oleh,
                'tanggal_validasi' => $rule->tanggal_validasi,
                'catatan' => $rule->catatan_validasi,
            ];
        }

        return null;
    }
}
