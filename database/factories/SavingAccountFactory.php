<?php

namespace Database\Factories;

use App\Enums\SavingTypeEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingAccountFactory extends Factory
{
    protected $model = SavingAccount::class;

    public function definition(): array
    {
        return [
            'saving_account_code' => $this->faker->unique()->numerify('SAV-######'),
            'saving_type' => $this->faker->randomElement(SavingTypeEnum::cases())->value,
            'balance' => $this->faker->numberBetween(0, 100000000),
            'member_id' => Member::factory(),
        ];
    }
}
