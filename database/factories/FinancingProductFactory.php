<?php

namespace Database\Factories;

use App\Models\FinancingProduct;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancingProduct>
 */
class FinancingProductFactory extends Factory
{
    protected $model = FinancingProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_code' => 'FP' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => $this->faker->word(),
            'brand' => $this->faker->company(),
            'specification' => $this->faker->sentence(),
            'type_id' => ProductType::factory(),
        ];
    }
}
