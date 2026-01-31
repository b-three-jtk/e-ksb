<?php

namespace Database\Factories;

use App\Enums\Heir;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Heir>
 */
class HeirFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik' => $this->faker->unique()->numerify('##################'),
            'name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(Heir::cases())->value,
            'contact' => $this->faker->phoneNumber(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
