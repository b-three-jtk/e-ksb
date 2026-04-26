<?php

namespace Database\Factories;

use App\Enums\HeirEnum;
use App\Models\Member;
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
            'heir_nik' => $this->faker->unique()->numerify('################'),
            'heir_name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(HeirEnum::cases())->value,
            'heir_contact' => $this->faker->phoneNumber(),
            'member_id' => Member::factory(),
        ];
    }
}
