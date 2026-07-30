<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReliabilityWeightTotalTest extends TestCase
{
    public function test_reliability_weights_total_100(): void
    {
        $weights = config('fertilization.reliability_weights');

        $this->assertNotEmpty($weights);
        $this->assertEquals(100, array_sum($weights), 'Total bobot kelengkapan data harus berjumlah 100');
    }

    public function test_no_validasi_ahli_weight(): void
    {
        $weights = config('fertilization.reliability_weights');

        $this->assertArrayNotHasKey('validasi_ahli', $weights);
    }

    public function test_all_weights_positive(): void
    {
        $weights = config('fertilization.reliability_weights');

        foreach ($weights as $key => $value) {
            $this->assertGreaterThan(0, $value, "Bobot '{$key}' harus positif");
        }
    }

    public function test_weight_keys_match_expected(): void
    {
        $weights = config('fertilization.reliability_weights');
        $expected = [
            'identitas_blok',
            'fase_terverifikasi',
            'curah_hujan',
            'tgl_pemupukan',
            'data_visual',
            'kondisi_lapangan',
        ];

        $this->assertEquals($expected, array_keys($weights));
    }
}
