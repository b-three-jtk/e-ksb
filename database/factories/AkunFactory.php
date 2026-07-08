<?php

namespace Database\Factories;

use App\Models\Akun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Akun>
 */
class AkunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_ref_akun' => $this->faker->unique()->numerify('###'),
            'nama_akun' => $this->faker->word(),
            'kategori_akun' => $this->faker->randomElement(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'saldo' => $this->faker->randomFloat(2, 1000, 100000), // Random balance between 1,000
        ];
    }
}
