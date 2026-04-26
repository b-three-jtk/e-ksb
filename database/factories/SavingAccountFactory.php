<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingAccountFactory extends Factory
{
    protected $model = SavingAccount::class;

    public function definition(): array
    {
        return [
            'saving_account_code' => $this->faker->unique()->numerify('SAV-######'),
            'saving_product_id' => SavingProduct::factory(),
            'saving_tenor' => $this->faker->numberBetween(12, 60),
            'target_amount' => $this->faker->numberBetween(1000000, 100000000),
            'member_id' => Member::factory(),
        ];
    }
}
