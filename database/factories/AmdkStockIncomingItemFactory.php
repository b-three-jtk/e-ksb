<?php

namespace Database\Factories;

use App\Models\AmdkProduct;
use App\Models\AmdkStockIncomingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AmdkStockIncomingItem>
 */
class AmdkStockIncomingItemFactory extends Factory
{
    protected $model = AmdkStockIncomingItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amdk_product_id' => AmdkProduct::factory(),
            'quantity' => $this->faker->numberBetween(5, 100),
            'unit_measure' => $this->faker->randomElement(['PCS', 'BOX', 'DUS', 'KG', 'LITER']),
        ];
    }
}
