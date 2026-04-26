<?php

namespace Database\Factories;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointTransactionFactory extends Factory
{
    protected $model = PointTransaction::class;

    public function definition(): array
    {
        return [
            'amount_earned' => $this->faker->numberBetween(10, 1000),
            'activity_description' => $this->faker->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
