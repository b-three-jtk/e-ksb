<?php

namespace Database\Factories;

use App\Enums\PositionEnum;
use App\Models\Account;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'fin_trans_id' => FinancialTransaction::factory(),
            'account_code' => Account::factory(),
            'user_id' => User::factory(),
            'position' => $this->faker->randomElement(PositionEnum::cases())->value,
            'nominal' => $this->faker->numberBetween(10000, 100000000),
            'updated_by' => User::factory(),
        ];
    }
}
