<?php

namespace Database\Factories;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use Illuminate\Database\Eloquent\Factories\Factory;

class KondisiLahanFactory extends Factory
{
    protected $model = KondisiLahan::class;

    public function definition(): array
    {
        return [
            'blok_lahan_id' => BlokLahan::factory(),
            'tanggal_observasi' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'warna_daun' => $this->faker->randomElement(config('observation.leaf_conditions')),
            'kelembaban_tanah' => $this->faker->randomElement(['Sangat Kering', 'Kering', 'Normal', 'Lembab', 'Sangat Lembab']),
            'curah_hujan_mm_bulanan' => $this->faker->numberBetween(50, 350),
            'curah_hujan_kategori' => $this->faker->randomElement(['Sangat Rendah', 'Rendah', 'Normal', 'Tinggi', 'Sangat Tinggi']),
            'kondisi_drainase' => $this->faker->randomElement(['Baik', 'Cukup', 'Buruk — Tergenang']),
        ];
    }
}
