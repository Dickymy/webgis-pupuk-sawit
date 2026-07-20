<?php

namespace Tests\Unit;

use App\Models\RuleBaseLanjutan;
use App\Services\SupportingFertilizerSanitizer;
use Tests\TestCase;

class SupportingFertilizerSanitizerTest extends TestCase
{
    private SupportingFertilizerSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new SupportingFertilizerSanitizer;
    }

    public function test_dolomit_unvalidated_hides_numbers(): void
    {
        $rule = new RuleBaseLanjutan([
            'jenis_pupuk_utama' => 'Dolomit (Kapur Pertanian)',
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => '500-1000 kg/Ha',
            'metode_aplikasi' => 'Sebar merata',
            'waktu_aplikasi' => 'Awal musim hujan',
            'status_validasi' => null,
        ]);

        $result = $this->sanitizer->sanitize([$rule]);

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]['angka_boleh_tampil']);
        $this->assertStringContainsString('Pupuk pendukung dapat dipertimbangkan', $result[0]['dosis']);
    }

    public function test_boraks_unvalidated_hides_numbers(): void
    {
        $rule = new RuleBaseLanjutan([
            'jenis_pupuk_utama' => 'Borax (Na2B4O7)',
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => '50-100 g/pokok',
            'metode_aplikasi' => 'Tabur di piringan',
            'waktu_aplikasi' => 'Awal musim hujan',
            'status_validasi' => null,
        ]);

        $result = $this->sanitizer->sanitize([$rule]);

        $this->assertFalse($result[0]['angka_boleh_tampil']);
    }

    public function test_rule_validated_source_shows_numbers(): void
    {
        $rule = new RuleBaseLanjutan([
            'jenis_pupuk_utama' => 'Kieserit (27% MgO)',
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => '1.0-1.5 kg/pokok',
            'metode_aplikasi' => 'Sebar di piringan',
            'waktu_aplikasi' => 'Awal musim hujan',
            'status_validasi' => 'TERVERIFIKASI_SUMBER',
            'sumber_judul' => 'Panduan Lengkap Kelapa Sawit',
            'sumber_penulis' => 'Iyung Pahan',
            'sumber_tahun' => 2013,
            'sumber_halaman' => '163',
            'sumber_tabel' => '9.13',
        ]);

        $result = $this->sanitizer->sanitize([$rule]);

        $this->assertTrue($result[0]['angka_boleh_tampil']);
        $this->assertEquals('1.0-1.5 kg/pokok', $result[0]['dosis']);
        $this->assertNotNull($result[0]['sumber']);
    }

    public function test_rule_validated_expert_shows_numbers(): void
    {
        $rule = new RuleBaseLanjutan([
            'jenis_pupuk_utama' => 'FeSO4 (Ferrous Sulfate)',
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => '50-75 g/pokok',
            'metode_aplikasi' => 'Siram ke piringan',
            'waktu_aplikasi' => 'Saat gejala terlihat',
            'status_validasi' => 'TERVERIFIKASI_AHLI',
            'divalidasi_oleh' => 'Dr. Agro Expert',
            'tanggal_validasi' => '2026-06-01',
            'catatan_validasi' => 'Dosis sesuai standar.',
        ]);

        $result = $this->sanitizer->sanitize([$rule]);

        $this->assertTrue($result[0]['angka_boleh_tampil']);
    }

    public function test_urea_always_shows_pahan_reference(): void
    {
        $rule = new RuleBaseLanjutan([
            'jenis_pupuk_utama' => 'Urea (46% N)',
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => '1.5-2.0 kg/pokok',
            'metode_aplikasi' => 'Tabur melingkar',
            'waktu_aplikasi' => 'Awal musim hujan',
            'status_validasi' => null,
        ]);

        $result = $this->sanitizer->sanitize([$rule]);

        $this->assertTrue($result[0]['angka_boleh_tampil']);
        $this->assertEquals('REFERENSI_PAHAN', $result[0]['status_validasi']);
    }
}
