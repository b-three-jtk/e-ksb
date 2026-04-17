<?php

namespace Database\Factories;

use App\Models\ProductType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_code' => 'PRD' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'product_name' => fake()->word(),
            'brand' => fake()->company(),
            'specification' => fake()->sentence(),
            'type_id' => ProductType::factory(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
