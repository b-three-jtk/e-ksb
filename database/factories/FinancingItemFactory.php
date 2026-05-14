<?php

namespace Database\Factories;

use App\Enums\ConditionEnum;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\ProductType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingItemFactory extends Factory
{
    protected $model = FinancingItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'specification' => $this->faker->sentence(),
            'qty' => $this->faker->numberBetween(1, 10),
            'condition' => $this->faker->randomElement(ConditionEnum::cases())->value,
            'price_per_unit' => $this->faker->numberBetween(100000, 50000000),
            'purchase_receipt' => null,
            'product_type_id' => ProductType::inRandomOrder()->first()?->id ?? ProductType::factory(),
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'financing_id' => Financing::factory(),
        ];
    }
}

