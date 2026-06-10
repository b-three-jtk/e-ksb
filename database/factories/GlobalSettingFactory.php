<?php

namespace Database\Factories;

use App\Models\GlobalSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlobalSetting>
 */
class GlobalSettingFactory extends Factory
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
            'effective_date' => $this->faker->date(),
            'description' => $this->faker->paragraph(),
            'updated_by' => User::factory(),
        ];
    }
}
