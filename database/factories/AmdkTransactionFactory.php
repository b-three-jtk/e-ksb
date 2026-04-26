<?php

namespace Database\Factories;

use App\Models\AmdkTransaction;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmdkTransactionFactory extends Factory
{
    protected $model = AmdkTransaction::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('AMK-#########'),
            'point_id' => PointTransaction::factory(),
            'member_id' => Member::inRandomOrder()->first()?->member_id ?? Member::factory(),
            'payment_method' => $this->faker->randomElement(['Cash', 'Transfer', 'Check']),
            'buyer_type' => $this->faker->randomElement(['Member', 'Non-Member', 'Stokist']),
            'invoice_receipt' => $this->faker->optional()->filePath(),
            'stokist_id' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
