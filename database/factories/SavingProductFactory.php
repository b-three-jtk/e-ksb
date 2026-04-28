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
            'name' => $this->faker->randomElement(SavingTypeEnum::cases())->value,
            'nominal' => $this->faker->numberBetween(100000, 10000000),
            'due_date' => $this->faker->numberBetween(1, 31),
        ];
    }
}
