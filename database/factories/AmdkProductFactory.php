<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AmdkFactory>
 */
class AmdkProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amdk_product_code' => fake()->unique()->numerify('AMDK-#####'),
            'amdk_product_name' => fake()->word(),
            'stock' => fake()->numberBetween(1, 100),
            'unit_measure' => fake()->randomElement(['pcs', 'box', 'liter']),
            'purchase_price' => fake()->numberBetween(10000, 1000000),
            'stokist_price' => fake()->numberBetween(10000, 1000000),
            'member_price' => fake()->numberBetween(10000, 1000000),
            'amdk_brand' => fake()->company(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
