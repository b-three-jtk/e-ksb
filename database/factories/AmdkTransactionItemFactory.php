<?php

namespace Database\Factories;

use App\Models\AmdkProduct;
use App\Models\AmdkTransaction;
use App\Models\AmdkTransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmdkTransactionItemFactory extends Factory
{
    protected $model = AmdkTransactionItem::class;

    public function definition(): array
    {
        return [
            'invoice_id' => AmdkTransaction::factory(),
            'amdk_product_id' => AmdkProduct::factory(),
            'price_per_item' => $this->faker->numberBetween(10000, 500000),
            'qty' => $this->faker->numberBetween(1, 50),
        ];
    }
}
