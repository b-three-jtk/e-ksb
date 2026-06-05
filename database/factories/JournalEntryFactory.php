<?php

namespace Database\Factories;

use App\Enums\PositionEnum;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'no_ref_account' => Account::factory(),
            'position' => $this->faker->randomElement(PositionEnum::cases())->value,
            'nominal' => $this->faker->numberBetween(10000, 100000000),
            'updated_by' => User::factory(),
            'transaction_date' => $this->faker->dateTime(),
        ];
    }
}
