<?php

namespace Database\Factories;

use App\Enums\ConditionEnum;
use App\Models\FinancingItem;
use App\Models\FinancingProduct;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingItemFactory extends Factory
{
    protected $model = FinancingItem::class;

    public function definition(): array
    {
        return [
            'request_description' => $this->faker->sentence(),
            'qty' => $this->faker->numberBetween(1, 10),
            'condition' => $this->faker->randomElement(ConditionEnum::cases())->value,
            'cost_price' => $this->faker->numberBetween(100000, 50000000),
            'margin_amount' => $this->faker->numberBetween(10000, 5000000),
            'product_id' => FinancingProduct::factory(),
            'purchase_receipt' => $this->faker->optional()->filePath(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
