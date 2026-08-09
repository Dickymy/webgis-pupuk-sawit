<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RuleBaseLanjutan;
use App\Services\RbsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardChainingFixpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_rule_masalah_menghasilkan_normal_dengan_nol_temuan(): void
    {
        $admin = Admin::factory()->create();
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'tahun_tanam' => now()->year - 8,
            'fase_tanaman' => 'TM',
            'luas_ha' => 1,
            'sph' => 136,
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now(),
            'warna_daun' => 'Hijau Normal',
            'gejala_defisiensi' => [],
            'kelembaban_tanah' => 'Lembab',
            'curah_hujan_mm_bulanan' => 180,
            'curah_hujan_kategori' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $this->actingAs($admin, 'admin');
        $result = app(RbsService::class)->analisis($blok);
        $rekomendasi = $result['rekomendasi'];

        $this->assertSame('NORMAL_VISUAL', $rekomendasi->status_kondisi_tanaman);
        $this->assertSame(0, $rekomendasi->jumlah_rule_terpicu);
        $this->assertSame([], $rekomendasi->masalah_teridentifikasi);
    }

    public function test_gejala_tanpa_rule_tidak_pernah_dianggap_normal(): void
    {
        $admin = Admin::factory()->create();
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'tahun_tanam' => now()->year - 8,
            'fase_tanaman' => 'TM',
            'luas_ha' => 1,
            'sph' => 136,
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now(),
            'warna_daun' => 'Oranye/Kemerahan',
            'gejala_defisiensi' => [],
            'kelembaban_tanah' => 'Lembab',
            'curah_hujan_mm_bulanan' => 180,
            'curah_hujan_kategori' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        $this->actingAs($admin, 'admin');
        $result = app(RbsService::class)->analisis($blok);
        $rekomendasi = $result['rekomendasi'];

        $this->assertSame('PERLU_VERIFIKASI', $rekomendasi->status_kondisi_tanaman);
        $this->assertSame(0, $rekomendasi->jumlah_rule_terpicu);
        $this->assertContains('Gejala daun perlu pemeriksaan lapangan lanjutan', $rekomendasi->masalah_teridentifikasi);
        $this->assertSame(0, $rekomendasi->active_stage);
    }

    public function test_rule_dependent_dipicu_meski_diperiksa_sebelum_rule_penghasil_fakta(): void
    {
        $admin = Admin::factory()->create();
        $anggota = Anggota::factory()->create();
        $blok = BlokLahan::factory()->create([
            'anggota_id' => $anggota->id,
            'tahun_tanam' => now()->year - 8,
            'fase_tanaman' => 'TM',
            'luas_ha' => 1,
            'sph' => 136,
        ]);

        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now(),
            'warna_daun' => 'Kuning Merata',
            'gejala_defisiensi' => ['N'],
            'kelembaban_tanah' => 'Lembab',
            'curah_hujan_mm_bulanan' => 180,
            'curah_hujan_kategori' => 'Normal',
            'kondisi_drainase' => 'Baik',
        ]);

        // Rule kesimpulan sengaja memiliki prioritas lebih tinggi sehingga diperiksa lebih dulu.
        RuleBaseLanjutan::create([
            'kode_rule' => 'REC-N-01',
            'prasyarat_intermediate' => ['diagnosis_n' => true],
            'indikasi_masalah' => 'Rekomendasi Urea Berdasarkan Diagnosis N',
            'jenis_pupuk_utama' => 'Urea',
            'saran_tindakan' => 'Gunakan dosis referensi berdasarkan umur dan fase tanaman.',
            'status_kebutuhan' => 'Segera',
            'prioritas' => 1,
            'aktif' => true,
            'jenis_rule' => 'SARAN_PENDUKUNG',
            'tingkat_keparahan' => 'NORMAL',
        ]);

        RuleBaseLanjutan::create([
            'kode_rule' => 'VIS-N-TEST',
            'kondisi_warna_daun' => 'Kuning Merata',
            'kondisi_defisiensi' => 'N',
            'kondisi_intermediate' => ['diagnosis_n' => true],
            'indikasi_masalah' => 'Defisiensi Nitrogen',
            'jenis_pupuk_utama' => 'Urea',
            'saran_tindakan' => 'Verifikasi gejala dan gunakan dosis referensi.',
            'status_kebutuhan' => 'Segera',
            'prioritas' => 10,
            'aktif' => true,
            'jenis_rule' => 'DIAGNOSIS_VISUAL',
            'tingkat_keparahan' => 'SEDANG',
        ]);

        $this->actingAs($admin, 'admin');
        $result = app(RbsService::class)->analisis($blok);
        $indications = collect($result['rekomendasi']->rules_terpicu)->pluck('indikasi');

        $this->assertSame(2, $result['rekomendasi']->jumlah_rule_terpicu);
        $this->assertTrue($indications->contains('Defisiensi Nitrogen'));
        $this->assertTrue($indications->contains('Rekomendasi Urea Berdasarkan Diagnosis N'));
    }
}
