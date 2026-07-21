<?php

namespace Database\Factories;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnggotaFactory extends Factory
{
    protected $model = Anggota::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'no_hp' => $this->faker->phoneNumber(),
            'alamat' => $this->faker->address(),
        ];
    }
}
