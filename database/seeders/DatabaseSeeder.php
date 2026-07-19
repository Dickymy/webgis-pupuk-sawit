<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeding:
     * 1. AdminSeeder         — akun admin awal (dari env variable)
     * 2. RuleBaseLanjutanSeeder — rule dasar RBS
     * 3. PahanRuleBaseV2Seeder — metadata provenance Pahan-v2
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RuleBaseLanjutanSeeder::class,
            PahanRuleBaseV2Seeder::class,
        ]);
    }
}
