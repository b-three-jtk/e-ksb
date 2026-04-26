<?php

namespace Database\Factories;

use App\Models\Financing;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return [
            'tenor' => $this->faker->numberBetween(6, 60),
            'financing_id' => Financing::factory(),
        ];
    }
}
