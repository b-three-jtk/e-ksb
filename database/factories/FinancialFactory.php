<?php

namespace Database\Factories;

use App\Enums\FinancialCategoryEnum;
use App\Enums\FinancialTypeEnum;
use App\Models\Financial;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialFactory extends Factory
{
    protected $model = Financial::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'financial_type' => $this->faker->randomElement(FinancialTypeEnum::cases())->value,
            'amount' => $this->faker->numberBetween(100000, 50000000),
            'category' => FinancialCategoryEnum::INCOME->value,
        ];
    }
}
