<?php

namespace Database\Factories;

use App\Enums\ConditionEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancingProduct>
 */
class FinancingProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'request_description' => fake()->sentence(),
            'qty' => fake()->numberBetween(1, 100),
            'condition' => fake()->randomElement(ConditionEnum::cases())->value,
            'cost_price' => fake()->numberBetween(10000, 1000000),
            'purchase_receipt' => fake()->optional()->imageUrl(),
        ];
    }
}
