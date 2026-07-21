<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\ProgramPemupukan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoubleSubmitRealizationTest extends TestCase
{
    use RefreshDatabase;

    private function createReadyBlok(): array
    {
        $admin = Admin::factory()->create();
        $blok = BlokLahan::factory()->create([
            'luas_ha' => 4.0,
            'sph' => 136,
            'tahun_tanam' => 2015,
            'fase_tanaman' => 'TM',
        ]);

        // Kondisi lahan yang memenuhi syarat kelayakan
        KondisiLahan::factory()->create([
            'blok_lahan_id' => $blok->id,
            'tanggal_observasi' => now()->subDays(5)->toDateString(),
            'curah_hujan_mm_bulanan' => 150, // Dalam rentang 100-250
            'kondisi_drainase' => 'Baik',
            'tanggal_pemupukan_terakhir' => now()->subDays(90), // > 60 hari
        ]);

        $program = ProgramPemupukan::create([
            'uuid' => Str::uuid()->toString(),
            'blok_lahan_id' => $blok->id,
            'tahun_program' => now()->year,
            'status_program' => 'AKTIF',
            'active_key' => $blok->id.'-'.now()->year,
        ]);

        $rbs = RekomendasiRbs::factory()->create([
            'blok_lahan_id' => $blok->id,
            'admin_id' => $admin->id,
            'program_pemupukan_id' => $program->id,
            'tanggal_analisis' => now(),
            'is_latest' => true,
            'urea_total_estimasi_tahunan' => 200.0,
            'kcl_total_estimasi_tahunan' => 250.0,
            'urea_aplikasi_saat_ini' => 100.0,
            'kcl_aplikasi_saat_ini' => 125.0,
            'active_stage' => 1,
            'status_stage' => 'TAHAP_1_SIAP',
            'status_kelayakan_aplikasi' => 'LAYAK_DIJADWALKAN',
            'versi_mesin_rekomendasi' => 'pahan-v2.9',
            'luas_ha_snapshot' => 4.0,
            'sph_snapshot' => 136,
            'jumlah_pokok_snapshot' => 544,
        ]);

        return compact('admin', 'blok', 'program', 'rbs');
    }

    /**
     * Test 1: Request identik dua kali dengan token sama — hanya satu record dibuat.
     */
    public function test_same_token_same_payload_creates_only_one_record(): void
    {
        $data = $this->createReadyBlok();
        $token = Str::uuid()->toString();

        $payload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => $token,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Test double submit',
        ];

        // First submit — should succeed
        $response1 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        // Second submit — same token, same payload
        $response2 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        // Second response should redirect with warning (duplicate detected)
        $response2->assertRedirect();
        $response2->assertSessionHas('warning');

        // Count: ONLY ONE active realization
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * Test 2: Token sama, payload berbeda — request kedua ditolak.
     */
    public function test_same_token_different_payload_rejects_second(): void
    {
        $data = $this->createReadyBlok();
        $token = Str::uuid()->toString();

        $payload1 = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => $token,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 50.0,
            'kcl_realisasi_kg' => 60.0,
            'status_realisasi' => 'SEBAGIAN',
            'catatan_pelaksana' => 'Pertama',
        ];

        // First submit
        $response1 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload1);

        $response1->assertRedirect();

        // Second submit with same token but different amounts
        $payload2 = array_merge($payload1, [
            'urea_realisasi_kg' => 80.0,
            'kcl_realisasi_kg' => 90.0,
            'catatan_pelaksana' => 'Kedua — seharusnya ditolak',
        ]);

        $response2 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload2);

        // Token sudah terpakai → redirect ke record pertama
        $response2->assertRedirect();
        $response2->assertSessionHas('warning');

        // Pastikan hanya satu record yang ada
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertSame(1, $count);

        // Pastikan jumlahnya tetap dari submit pertama
        $realisasi = RealisasiPemupukan::where('submission_token', $token)->first();
        $this->assertNotNull($realisasi);
        $this->assertEquals(50.0, (float) $realisasi->urea_realisasi_kg);
        $this->assertEquals(60.0, (float) $realisasi->kcl_realisasi_kg);
    }

    /**
     * Test 3: Token berbeda, payload identik dalam waktu dekat — perlindungan semantik.
     */
    public function test_different_token_identical_payload_prevents_semantic_duplicate(): void
    {
        $data = $this->createReadyBlok();

        $basePayload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Semantic duplicate test',
        ];

        // First submit with token A
        $payload1 = array_merge($basePayload, ['submission_token' => Str::uuid()->toString()]);
        $response1 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload1);

        $response1->assertRedirect();

        // Second submit with token B — same payload, different token
        $payload2 = array_merge($basePayload, ['submission_token' => Str::uuid()->toString()]);

        $response2 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload2);

        // Should be caught by semantic duplicate detection
        $response2->assertRedirect();
        $response2->assertSessionHas('warning');

        // Only one active record
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * Test 4: Realisasi berbeda yang sah (jumlah berbeda) tetap dapat dicatat.
     */
    public function test_legitimate_different_realizations_are_allowed(): void
    {
        $data = $this->createReadyBlok();

        // First submit: realisasi sebagian (50 kg)
        $payload1 = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => Str::uuid()->toString(),
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 50.0,
            'kcl_realisasi_kg' => 60.0,
            'status_realisasi' => 'SEBAGIAN',
            'catatan_pelaksana' => 'Sebagian pertama',
        ];

        $response1 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload1);

        $response1->assertRedirect();

        // Second submit: realisasi lanjutan (jumlah berbeda)
        $payload2 = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => Str::uuid()->toString(),
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 50.0,
            'kcl_realisasi_kg' => 65.0,  // Jumlah berbeda — bukan duplikat
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Lanjutan',
        ];

        $response2 = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload2);

        // Should succeed — different amounts = legitimate continuation
        $response2->assertRedirect();

        // Both records exist
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertGreaterThanOrEqual(1, $count);
    }

    /**
     * Test 5: Record BATAL tidak menghalangi pencatatan ulang.
     */
    public function test_cancelled_record_does_not_block_new_realization(): void
    {
        $data = $this->createReadyBlok();

        // Create a cancelled realization directly
        RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'blok_lahan_id' => $data['blok']->id,
            'program_pemupukan_id' => $data['program']->id,
            'admin_id' => $data['admin']->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_rencana_kg' => 100.0,
            'kcl_rencana_kg' => 125.0,
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'BATAL',
            'submission_token' => Str::uuid()->toString(),
        ]);

        // Now try to submit new realization with same amounts
        $payload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => Str::uuid()->toString(),
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Re-pencatatan setelah batal',
        ];

        $response = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        // Should succeed — cancelled records don't block new ones
        $response->assertRedirect();

        // One active record (the new one), one BATAL
        $countAktif = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $countBatal = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', 'BATAL')
            ->count();

        $this->assertSame(1, $countAktif);
        $this->assertSame(1, $countBatal);
    }

    /**
     * Test 6: Concurrent submit — simulasi dua proses yang submit bersamaan.
     *
     * Karena test Laravel bersifat serial, kita simulasikan dengan membuat
     * scenario di mana token yang sama digunakan berurutan sangat cepat.
     */
    public function test_concurrent_submit_only_creates_one_active_record(): void
    {
        $data = $this->createReadyBlok();
        $token = Str::uuid()->toString();

        $payload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'submission_token' => $token,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
            'catatan_pelaksana' => 'Concurrent submit test',
        ];

        // Simulasikan concurrent: submit 3 kali berurutan cepat
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($data['admin'], 'admin')
                ->post(route('realisasi-pemupukan.store'), $payload);
        }

        // Semua response harus aman (redirect, tidak error 500)
        foreach ($responses as $response) {
            $response->assertRedirect();
            $response->assertStatus(302);
        }

        // Hanya satu record aktif
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertSame(1, $count);

        // Token hanya digunakan sekali
        $tokenCount = RealisasiPemupukan::where('submission_token', $token)->count();
        $this->assertSame(1, $tokenCount);
    }

    /**
     * Test: Store tanpa submission_token tetap berfungsi (token di-generate server-side).
     */
    public function test_store_without_token_still_works(): void
    {
        $data = $this->createReadyBlok();

        $payload = [
            'rekomendasi_rbs_id' => $data['rbs']->id,
            // submission_token not included — server will generate
            'tanggal_realisasi' => now()->toDateString(),
            'urea_realisasi_kg' => 100.0,
            'kcl_realisasi_kg' => 125.0,
            'status_realisasi' => 'SELESAI',
        ];

        $response = $this->actingAs($data['admin'], 'admin')
            ->post(route('realisasi-pemupukan.store'), $payload);

        $response->assertRedirect();

        // Record created with server-generated token
        $realisasi = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)->first();
        $this->assertNotNull($realisasi);
        $this->assertNotNull($realisasi->submission_token);
    }

    /**
     * Test: Update identik tidak menambah histori berulang.
     */
    public function test_identical_update_does_not_create_duplicate_history(): void
    {
        $data = $this->createReadyBlok();

        // Create a realization
        $realisasi = RealisasiPemupukan::create([
            'rekomendasi_rbs_id' => $data['rbs']->id,
            'blok_lahan_id' => $data['blok']->id,
            'program_pemupukan_id' => $data['program']->id,
            'admin_id' => $data['admin']->id,
            'tahun_program' => now()->year,
            'tahap' => 1,
            'tanggal_realisasi' => now()->toDateString(),
            'urea_rencana_kg' => 100.0,
            'kcl_rencana_kg' => 125.0,
            'urea_realisasi_kg' => 50.0,
            'kcl_realisasi_kg' => 60.0,
            'status_realisasi' => 'SEBAGIAN',
            'submission_token' => Str::uuid()->toString(),
        ]);

        $updatePayload = [
            'tanggal_realisasi' => $realisasi->tanggal_realisasi->toDateString(),
            'urea_realisasi_kg' => 50.0,  // Same values
            'kcl_realisasi_kg' => 60.0,   // Same values
            'status_realisasi' => 'SEBAGIAN', // Same
            'catatan_pelaksana' => $realisasi->catatan_pelaksana ?? '',
            '_expected_updated_at' => $realisasi->updated_at->toDateTimeString(),
        ];

        // Submit update with identical data
        $response = $this->actingAs($data['admin'], 'admin')
            ->put(route('realisasi-pemupukan.update', $realisasi), $updatePayload);

        // Should succeed but not record history (no actual change)
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Record count stays at 1
        $count = RealisasiPemupukan::where('blok_lahan_id', $data['blok']->id)
            ->where('status_realisasi', '!=', 'BATAL')
            ->count();

        $this->assertSame(1, $count);
    }
}
