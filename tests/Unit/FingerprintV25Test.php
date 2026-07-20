<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test fingerprint berubah saat komponen berubah (Pahan v2.5).
 */
class FingerprintV25Test extends TestCase
{
    private function generateFingerprint(array $data): string
    {
        $rulesCodes = collect($data['rules_terpicu'] ?? [])
            ->pluck('indikasi')
            ->sort()
            ->values()
            ->toArray();

        $fingerprintData = [
            'kondisi_lahan_id' => $data['kondisi_lahan_id'] ?? null,
            'versi_mesin' => $data['versi_mesin_rekomendasi'] ?? null,
            'fase' => $data['fase_tanaman_snapshot'] ?? null,
            'umur' => $data['umur_tanaman_snapshot'] ?? null,
            'strategi_estimasi' => $data['strategi_estimasi_dosis'] ?? null,
            'urea_estimasi' => $data['urea_estimasi_kg_per_pokok_tahun'] ?? null,
            'kcl_estimasi' => $data['kcl_estimasi_kg_per_pokok_tahun'] ?? null,
            'status_kondisi' => $data['status_kondisi_tanaman'] ?? null,
            'status_kelayakan' => $data['status_kelayakan_aplikasi'] ?? null,
            'rules_terpicu' => $rulesCodes,
            'jumlah_jadwal' => count($data['jadwal_pemupukan'] ?? []),
            'kelengkapan_data_score' => $data['kelengkapan_data_score'] ?? null,
            'luas_ha_snapshot' => $data['luas_ha_snapshot'] ?? null,
            'sph_snapshot' => $data['sph_snapshot'] ?? null,
            'jumlah_pokok_snapshot' => $data['jumlah_pokok_snapshot'] ?? null,
            'urea_total_estimasi_tahunan' => $data['urea_total_estimasi_tahunan'] ?? null,
            'kcl_total_estimasi_tahunan' => $data['kcl_total_estimasi_tahunan'] ?? null,
            'urea_aplikasi_saat_ini' => $data['urea_aplikasi_saat_ini'] ?? null,
            'kcl_aplikasi_saat_ini' => $data['kcl_aplikasi_saat_ini'] ?? null,
            'urea_sisa_tahunan' => $data['urea_sisa_tahunan'] ?? null,
            'kcl_sisa_tahunan' => $data['kcl_sisa_tahunan'] ?? null,
            'active_stage' => $data['active_stage'] ?? null,
            'status_stage' => $data['status_stage'] ?? null,
        ];

        ksort($fingerprintData);

        return hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE));
    }

    private function baseData(): array
    {
        return [
            'kondisi_lahan_id' => 1,
            'versi_mesin_rekomendasi' => 'pahan-v2.5',
            'fase_tanaman_snapshot' => 'TM',
            'umur_tanaman_snapshot' => 5,
            'strategi_estimasi_dosis' => 'midpoint',
            'urea_estimasi_kg_per_pokok_tahun' => 1.325,
            'kcl_estimasi_kg_per_pokok_tahun' => 1.85,
            'status_kondisi_tanaman' => 'NORMAL_VISUAL',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'rules_terpicu' => [],
            'jadwal_pemupukan' => [['tahap' => 1]],
            'kelengkapan_data_score' => 75,
            'luas_ha_snapshot' => 2.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 272,
            'urea_total_estimasi_tahunan' => 360.4,
            'kcl_total_estimasi_tahunan' => 503.2,
            'urea_aplikasi_saat_ini' => 180.2,
            'kcl_aplikasi_saat_ini' => 251.6,
            'urea_sisa_tahunan' => 360.4,
            'kcl_sisa_tahunan' => 503.2,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
        ];
    }

    public function test_luas_berubah_fingerprint_berubah(): void
    {
        $base = $this->baseData();
        $changed = array_merge($base, ['luas_ha_snapshot' => 3.5]);

        $this->assertNotEquals($this->generateFingerprint($base), $this->generateFingerprint($changed));
    }

    public function test_sph_berubah_fingerprint_berubah(): void
    {
        $base = $this->baseData();
        $changed = array_merge($base, ['sph_snapshot' => 143]);

        $this->assertNotEquals($this->generateFingerprint($base), $this->generateFingerprint($changed));
    }

    public function test_realisasi_berubah_fingerprint_berubah(): void
    {
        $base = $this->baseData();
        // Setelah realisasi, urea_sisa dan aplikasi_saat_ini berubah
        $changed = array_merge($base, [
            'urea_sisa_tahunan' => 110.4,
            'urea_aplikasi_saat_ini' => 110.4,
            'active_stage' => 2,
            'status_stage' => 'TAHAP_2_SIAP',
        ]);

        $this->assertNotEquals($this->generateFingerprint($base), $this->generateFingerprint($changed));
    }

    public function test_aplikasi_saat_ini_berubah_fingerprint_berubah(): void
    {
        $base = $this->baseData();
        $changed = array_merge($base, ['urea_aplikasi_saat_ini' => 0.0, 'kcl_aplikasi_saat_ini' => 0.0]);

        $this->assertNotEquals($this->generateFingerprint($base), $this->generateFingerprint($changed));
    }

    public function test_same_data_same_fingerprint(): void
    {
        $base = $this->baseData();
        $this->assertEquals($this->generateFingerprint($base), $this->generateFingerprint($base));
    }
}
