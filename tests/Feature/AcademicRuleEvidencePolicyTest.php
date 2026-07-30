<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\RuleBaseLanjutan;
use Database\Seeders\RuleBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicRuleEvidencePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_traceable_rules_are_active(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $activeCodes = RuleBaseLanjutan::query()
            ->where('aktif', true)
            ->orderBy('kode_rule')
            ->pluck('kode_rule')
            ->all();

        $this->assertSame([
            'VIS-B-01',
            'VIS-K-02',
            'VIS-MG-01',
            'VIS-N-01',
            'WAKTU-HUJAN-OPTIMAL',
            'WAKTU-HUJAN-RENDAH',
            'WAKTU-HUJAN-TINGGI',
        ], $activeCodes);

        $this->assertSame('Daun Bawah Menguning', RuleBaseLanjutan::where('kode_rule', 'VIS-N-01')->value('kondisi_warna_daun'));
        $this->assertSame('Bercak Kuning/Transparan pada Daun Tua', RuleBaseLanjutan::where('kode_rule', 'VIS-K-02')->value('kondisi_warna_daun'));
        $this->assertSame('Tepi Daun Tua Menguning pada Bagian Terbuka', RuleBaseLanjutan::where('kode_rule', 'VIS-MG-01')->value('kondisi_warna_daun'));
        $this->assertSame('Daun Muda Berbentuk Kait atau Memendek', RuleBaseLanjutan::where('kode_rule', 'VIS-B-01')->value('kondisi_warna_daun'));
        $this->assertEquals(59.9, RuleBaseLanjutan::where('kode_rule', 'WAKTU-HUJAN-RENDAH')->value('kondisi_curah_hujan_max_mm'));
        $this->assertEquals(100.0, RuleBaseLanjutan::where('kode_rule', 'WAKTU-HUJAN-OPTIMAL')->value('kondisi_curah_hujan_min_mm'));
        $this->assertEquals(250.0, RuleBaseLanjutan::where('kode_rule', 'WAKTU-HUJAN-OPTIMAL')->value('kondisi_curah_hujan_max_mm'));
        $this->assertEquals(300.1, RuleBaseLanjutan::where('kode_rule', 'WAKTU-HUJAN-TINGGI')->value('kondisi_curah_hujan_min_mm'));
        $this->assertSame(7, RuleBaseLanjutan::where('aktif', true)->where('status_validasi', 'TERVERIFIKASI_SUMBER')->count());
    }

    public function test_rule_page_exposes_controlled_management_without_permanent_delete(): void
    {
        $this->seed(RuleBaseSeeder::class);
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('rule-base.index'));

        $response->assertOk();
        $response->assertSee('Rule digunakan');
        $response->assertSee('Tambah Rule');
        $response->assertDontSee('Uji Rule');
        $response->assertSee('Edit');
        $response->assertDontSee('Draft nonaktif');
        $response->assertDontSee('VIS-P-01');
        $this->assertTrue(Route::has('rule-base.create'));
        $this->assertTrue(Route::has('rule-base.store'));
        $this->assertTrue(Route::has('rule-base.edit'));
        $this->assertTrue(Route::has('rule-base.update'));
        $this->assertFalse(Route::has('rule-base.test'));
        $this->assertFalse(Route::has('rule-base.test.run'));
        $this->assertTrue(Route::has('rule-base.status'));
        $this->assertFalse(Route::has('rule-base.destroy'));
    }

    public function test_obsolete_system_rules_are_not_seeded(): void
    {
        $this->seed(RuleBaseSeeder::class);

        foreach (['VIS-N-02', 'VIS-K-01', 'VIS-P-01', 'VIS-FE-01', 'VIS-ZN-01', 'TANAH-PH-01', 'TANAH-PH-02', 'LINGK-DR-01', 'LINGK-KER-01', 'UMUR-TBM-01', 'UMUR-TUA-01', 'NORMAL-01'] as $code) {
            $this->assertDatabaseMissing('rule_bases_lanjutan', ['kode_rule' => $code]);
        }

        $activeOutput = RuleBaseLanjutan::where('aktif', true)
            ->get(['jenis_pupuk_utama', 'jenis_pupuk_pendukung', 'dosis_anjuran'])
            ->toJson();

        foreach (['Borax', 'Dolomit', 'KNO3', 'FeSO4', 'TSP', 'ZnSO4'] as $unsupported) {
            $this->assertStringNotContainsStringIgnoringCase($unsupported, $activeOutput);
        }

        foreach (['VIS-MG-01', 'VIS-B-01'] as $code) {
            $rule = RuleBaseLanjutan::where('kode_rule', $code)->firstOrFail();
            $this->assertTrue($rule->aktif);
            $this->assertSame('Tidak ditentukan otomatis', $rule->jenis_pupuk_utama);
            $this->assertSame('RINGAN', $rule->tingkat_keparahan);
        }
    }

    public function test_observation_form_does_not_ask_user_to_diagnose_nutrient_codes(): void
    {
        foreach (['resources/views/kondisi_lahan/create.blade.php', 'resources/views/kondisi_lahan/edit.blade.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('name="gejala_defisiensi[]"', $source);
            $this->assertStringNotContainsString('Kode fakta internal', $source);
        }
    }

    public function test_observation_form_only_shows_supported_leaf_conditions(): void
    {
        $this->seed(RuleBaseSeeder::class);
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('kondisi-lahan.create'));

        $response->assertOk();
        foreach ([
            'Tidak ditemukan gejala yang diperiksa',
            'Daun bagian bawah menguning',
            'Bercak kuning atau transparan pada daun tua',
            'Tepi daun tua menguning pada bagian terbuka',
            'Daun muda berbentuk kait atau memendek',
            'Ada gejala lain',
            'Belum dapat dipastikan',
        ] as $label) {
            $response->assertSee($label);
        }
        foreach (['Hijau Pucat', 'Kuning Antar Tulang', 'Oranye/Kemerahan', 'Coklat Ujung', 'Bercak Nekrotik'] as $unsupported) {
            $response->assertDontSee($unsupported);
        }
    }

    public function test_knowledge_page_explains_sources_and_scientific_limits(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('rule-base.info'));

        $response->assertOk();
        $response->assertSee('7 rule aktif: 4 gejala daun + 3 waktu');
        $response->assertSee('Hasil visual adalah indikasi awal, bukan diagnosis pasti');
        $response->assertSee('Iyung Pahan (2013)');
        $response->assertDontSee('Tabel 9.13');
        $response->assertDontSee('Tabel 9.14');
        $response->assertDontSee('Panduan Lengkap Kelapa Sawit');
        $response->assertSee('10.22302/iopri.war.warta.v30i1.129', false);
        $response->assertSee('10.22302/iopri.war.warta.v26i2.48', false);
        $response->assertSee('VIS-MG-01');
        $response->assertSee('VIS-B-01');
        $response->assertSee('WAKTU-HUJAN-OPTIMAL');
        $response->assertDontSee('25 rule aktif');
        $response->assertDontSee('Mengapa rule lain tidak aktif?');
        $response->assertDontSee('Lihat katalog buku');
        $response->assertDontSee('Nomor tabel dan semua angka harus dicocokkan');
        $response->assertDontSee('umur tanaman × jenis tanah × topografi');
    }
}
