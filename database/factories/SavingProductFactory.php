<?php

namespace Database\Factories;

use App\Enums\SavingTypeEnum;
use App\Models\SavingProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingProductFactory extends Factory
{
    protected $model = SavingProduct::class;

    public function definition(): array
    {
        return [
            'saving_product_name' => $this->faker->randomElement(SavingTypeEnum::cases())->value,
            'amount' => $this->faker->numberBetween(100000, 10000000),
            'due_date' => $this->faker->numberBetween(12, 60),
        ];
    }
}
