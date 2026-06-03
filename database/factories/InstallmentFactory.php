<?php

namespace Database\Factories;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financing;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'installment_no' => $this->faker->numberBetween(1, 36),
            'amount' => $this->faker->randomFloat(2, 100000, 1000000),
            'status' => $this->faker->randomElement(array_column(InstallmentPaymentScheduleStatusEnum::cases(), 'value')),
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

