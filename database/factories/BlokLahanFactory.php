<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\BlokLahan;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlokLahanFactory extends Factory
{
    protected $model = BlokLahan::class;

    public function definition(): array
    {
        return [
            'anggota_id' => Anggota::factory(),
            'nama_blok' => 'Blok '.$this->faker->unique()->word(),
            'luas_ha' => $this->faker->randomFloat(2, 0.5, 5.0),
            'sph' => $this->faker->numberBetween(130, 160),
            'tahun_tanam' => $this->faker->numberBetween(2010, 2022),
            'jenis_tanah' => 'Tanah Lempung',
            'topografi' => 'Datar 0-15°',
            'fase_tanaman' => 'TM',
            'koordinat_geojson' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[[101.0, 0.5], [101.1, 0.5], [101.1, 0.6], [101.0, 0.6], [101.0, 0.5]]],
            ]),
        ];
    }
}
