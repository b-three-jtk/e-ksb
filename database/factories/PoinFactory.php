<?php

namespace Database\Factories;

use App\Models\Poin;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoinFactory extends Factory
{
    protected $model = Poin::class;

    public function definition(): array
    {
        return [
            'jml_perolehan' => $this->faker->numberBetween(10, 1000),
            'deskripsi' => $this->faker->sentence(),
            'pengguna_id' => Pengguna::factory(),
        ];
    }
}
