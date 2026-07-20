<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyDatabaseFixture;
use Tests\TestCase;

class MigrationDataPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_data_survives_migration(): void
    {
        $ids = LegacyDatabaseFixture::seed();

        $issues = LegacyDatabaseFixture::verify($ids);

        $this->assertEmpty($issues, 'Data preservation issues: '.implode(', ', $issues));
    }

    public function test_user_rule_preserved(): void
    {
        $ids = LegacyDatabaseFixture::seed();

        if ($ids['rule_id']) {
            $this->assertDatabaseHas('rule_bases_lanjutan', [
                'id' => $ids['rule_id'],
                'indikasi_masalah' => 'Rule Custom Pengguna',
            ]);
        } else {
            $this->markTestSkipped('rule_bases_lanjutan table not available');
        }
    }

    public function test_history_preserved(): void
    {
        $ids = LegacyDatabaseFixture::seed();

        $this->assertDatabaseHas('rekomendasi_rbs', [
            'id' => $ids['rbs_id'],
            'is_latest' => true,
            'versi_mesin_rekomendasi' => 'pahan-v2.4',
        ]);
    }

    public function test_v25_fields_nullable_for_old_records(): void
    {
        $ids = LegacyDatabaseFixture::seed();

        $rbs = DB::table('rekomendasi_rbs')
            ->where('id', $ids['rbs_id'])
            ->first();

        // v2.5 fields harus null karena data lama
        $this->assertNull($rbs->luas_ha_snapshot);
        $this->assertNull($rbs->sph_snapshot);
        $this->assertNull($rbs->active_stage);
        $this->assertNull($rbs->status_stage);
    }
}
