<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\Condition;
use App\Models\Supplier;
use App\Enums\FinancingReqStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Financing>
 */
class FinancingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_code' => $this->faker->unique()->numerify('PM########'),
            'product_name' => $this->faker->word(),
            'product_type' => $this->faker->randomElement(['Electronics', 'Furniture', 'Vehicle', 'Appliance']),
            'brand' => $this->faker->company(),
            'color' => $this->faker->safeColorName(),
            'condition' => $this->faker->randomElement(Condition::cases())->value,
            'description' => $this->faker->sentence(),
            'cost_price' => $this->faker->numberBetween(100000, 10000000),
            'qty' => $this->faker->numberBetween(1, 10),
            'margin' => $this->faker->numberBetween(10000, 500000),
            'tsaman_naqdy' => $this->faker->numberBetween(10000, 500000),
            'status' => $this->faker->randomElement(FinancingReqStatus::cases())->value,
            'isWakalah' => $this->faker->boolean(),
            'down_payment' => $this->faker->numberBetween(50000, 5000000),
            'market_price' => $this->faker->numberBetween(150000, 12000000),
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
