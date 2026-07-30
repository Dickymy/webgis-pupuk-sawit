<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\RuleBaseLanjutan;
use Database\Seeders\RuleBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RuleBaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_admin_can_create_visual_rule_as_not_yet_used(): void
    {
        $payload = $this->visualPayload();
        $payload['dosis_anjuran'] = '99 kg per pokok';

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('rule-base.store'), $payload);

        $rule = RuleBaseLanjutan::where('kode_rule', 'VIS-CUSTOM-001')->firstOrFail();
        $response->assertRedirect(route('rule-base.index'));
        $this->assertFalse($rule->aktif);
        $this->assertSame('PERLU_VALIDASI_AHLI', $rule->status_validasi);
        $this->assertSame('1.0', $rule->versi_rule);
        $this->assertSame('JURNAL', $rule->tingkat_bukti);
        $this->assertSame('Tidak ada dosis otomatis dari pengamatan visual.', $rule->dosis_anjuran);
        $this->assertStringNotContainsString('99 kg', $rule->dosis_anjuran);
    }

    public function test_rule_can_be_activated_after_source_and_conflict_checks(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('rule-base.store'), $this->visualPayload());
        $rule = RuleBaseLanjutan::latest('id')->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('rule-base.status', $rule), ['action' => 'activate'])
            ->assertRedirect(route('rule-base.index'));

        $rule->refresh();
        $this->assertTrue($rule->aktif);
        $this->assertSame('TERVERIFIKASI_SUMBER', $rule->status_validasi);
        $this->assertSame($this->admin->nama_lengkap, $rule->divalidasi_oleh);
    }

    public function test_editing_active_rule_creates_new_pending_version(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('rule-base.store'), $this->visualPayload());
        $rule = RuleBaseLanjutan::latest('id')->firstOrFail();
        $this->actingAs($this->admin, 'admin')->patch(route('rule-base.status', $rule), ['action' => 'activate']);

        $changed = $this->visualPayload([
            'indikasi_masalah' => 'Gejala daun perlu pemeriksaan lapangan',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('rule-base.update', $rule), $changed)
            ->assertRedirect(route('rule-base.index'));

        $rule->refresh();
        $this->assertFalse($rule->aktif);
        $this->assertSame('1.1', $rule->versi_rule);
        $this->assertSame('PERLU_VALIDASI_AHLI', $rule->status_validasi);
        $this->assertSame('Gejala daun perlu pemeriksaan lapangan', $rule->indikasi_masalah);
    }

    public function test_duplicate_active_visual_condition_is_rejected(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $this->actingAs($this->admin, 'admin')->post(route('rule-base.store'), $this->visualPayload([
            'kondisi_warna_daun' => 'Daun Bawah Menguning',
            'jenis_pupuk_utama' => 'Urea',
        ]));
        $rule = RuleBaseLanjutan::where('kode_rule', 'VIS-CUSTOM-001')->firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')
            ->patch(route('rule-base.status', $rule), ['action' => 'activate']);

        $response->assertRedirect(route('rule-base.index'));
        $response->assertSessionHas('error');
        $this->assertFalse($rule->fresh()->aktif);
    }

    public function test_overlapping_active_rainfall_range_is_rejected(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $this->actingAs($this->admin, 'admin')->post(route('rule-base.store'), $this->timingPayload());
        $rule = RuleBaseLanjutan::where('kode_rule', 'WAKTU-CUSTOM-001')->firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')
            ->patch(route('rule-base.status', $rule), ['action' => 'activate']);

        $response->assertRedirect(route('rule-base.index'));
        $response->assertSessionHas('error');
        $this->assertFalse($rule->fresh()->aktif);
    }

    public function test_only_source_type_and_reference_are_required(): void
    {
        $payload = $this->visualPayload([
            'sumber_judul' => 'Iyung Pahan (2013)',
            'sumber_penulis' => null,
            'sumber_tahun' => null,
            'sumber_halaman' => null,
            'sumber_tabel' => null,
            'catatan_validasi' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('rule-base.store'), $payload)
            ->assertSessionHasNoErrors();

        $rule = RuleBaseLanjutan::latest('id')->firstOrFail();
        $this->actingAs($this->admin, 'admin')
            ->patch(route('rule-base.status', $rule), ['action' => 'activate'])
            ->assertRedirect(route('rule-base.index'));

        $this->assertTrue($rule->fresh()->aktif);
        $this->assertNull($rule->fresh()->sumber_halaman);
    }

    public function test_rule_and_observation_use_the_same_supported_leaf_conditions(): void
    {
        $observationConditions = config('observation.leaf_conditions');
        $diagnosticConditions = config('observation.diagnostic_leaf_conditions');
        $formView = file_get_contents(resource_path('views/kondisi_lahan/_form.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/KondisiLahanController.php'));

        $this->assertCount(5, $observationConditions);
        $this->assertCount(4, $diagnosticConditions);
        $this->assertContains('Hijau Normal', $observationConditions);
        foreach ($diagnosticConditions as $condition) {
            $this->assertContains($condition, $observationConditions);
        }
        foreach (['Hijau Pucat', 'Kuning Antar Tulang', 'Oranye/Kemerahan', 'Coklat Ujung', 'Bercak Nekrotik'] as $unsupported) {
            $this->assertNotContains($unsupported, $observationConditions);
        }
        $this->assertStringContainsString('$leafConditions as $condition', $formView);
        $this->assertStringContainsString("config('observation.diagnostic_leaf_conditions'", $controller);
    }

    public function test_rule_page_has_no_test_feature_or_destroy_route(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('rule-base.index'))
            ->assertOk()
            ->assertDontSee('Uji Rule');

        $this->assertFalse(Route::has('rule-base.test'));
        $this->assertFalse(Route::has('rule-base.test.run'));
        $this->assertFalse(Route::has('rule-base.destroy'));
    }

    public function test_obsolete_system_rules_are_absent(): void
    {
        $this->seed(RuleBaseSeeder::class);

        $this->assertDatabaseMissing('rule_bases_lanjutan', ['kode_rule' => 'VIS-P-01']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('rule-base.index'))
            ->assertOk()
            ->assertDontSee('VIS-P-01');
    }

    private function visualPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis_rule' => 'DIAGNOSIS_VISUAL',
            'kondisi_warna_daun' => 'Daun Bawah Menguning',
            'indikasi_masalah' => 'Gejala daun perlu diperiksa',
            'jenis_pupuk_utama' => 'Tidak ditentukan otomatis',
            'saran_tindakan' => 'Periksa kondisi daun dan riwayat pemupukan sebelum menentukan tindakan.',
            'status_kebutuhan' => 'Segera',
            'tingkat_keparahan' => 'RINGAN',
            'prioritas' => 4,
            'tingkat_bukti' => 'JURNAL',
            'sumber_judul' => 'Pengelolaan hara tanaman kelapa sawit',
            'sumber_penulis' => 'Peneliti Contoh',
            'sumber_tahun' => 2025,
            'sumber_halaman' => '10-15',
            'sumber_tabel' => 'Tabel 2',
            'catatan_validasi' => 'Sumber menjelaskan hubungan gejala daun dengan perlunya pemeriksaan lanjutan.',
        ], $overrides);
    }

    private function timingPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis_rule' => 'PEMBATAS_APLIKASI',
            'kondisi_curah_hujan_min_mm' => 50,
            'kondisi_curah_hujan_max_mm' => 120,
            'indikasi_masalah' => 'Curah hujan perlu diperiksa sebelum pemupukan',
            'saran_tindakan' => 'Tunda sampai kondisi curah hujan berada pada rentang yang disarankan.',
            'status_kebutuhan' => 'Tunda',
            'tingkat_keparahan' => 'NORMAL',
            'prioritas' => 2,
            'tingkat_bukti' => 'JURNAL',
            'sumber_judul' => 'Waktu pemupukan kelapa sawit berdasarkan curah hujan',
            'sumber_penulis' => 'Peneliti Contoh',
            'sumber_tahun' => 2025,
            'sumber_halaman' => '20-24',
            'catatan_validasi' => 'Sumber menjelaskan rentang curah hujan yang menjadi pertimbangan waktu pemupukan.',
        ], $overrides);
    }
}
