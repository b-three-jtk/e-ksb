<?php

namespace Database\Factories;

use App\Models\PengaturanUmum;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengaturanUmum>
 */
class PengaturanUmumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
            'tgl_diberlakukan' => $this->faker->date(),
            'deskripsi' => $this->faker->paragraph(),
            'updated_by' => Pengguna::factory(),
        ];
    }
}
