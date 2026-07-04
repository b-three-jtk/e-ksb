<?php

namespace Database\Factories;

use App\Enums\ConditionEnum;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\JenisBarang;
use App\Models\Pemasok;
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
            'jenis_barang_id' => JenisBarang::inRandomOrder()->first()?->id ?? JenisBarang::factory(),
            'pemasok_id' => Pemasok::inRandomOrder()->first()?->id ?? Pemasok::factory(),
            'financing_id' => Financing::factory(),
        ];
    }
}

