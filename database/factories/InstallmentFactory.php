<?php

namespace Database\Factories;

use App\Models\Financing;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'tenor' => $this->faker->numberBetween(6, 60),
            'due_day' => $this->faker->numberBetween(1, 28),
            'financing_id' => Financing::factory(),
        ];
    }

    /**
     * 12 months tenor
     */
    public function tenor12(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 12,
        ]);
    }

    /**
     * 24 months tenor
     */
    public function tenor24(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 24,
        ]);
    }

    /**
     * 36 months tenor
     */
    public function tenor36(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 36,
        ]);
    }
}

