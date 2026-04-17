<?php

namespace Database\Seeders;

use App\Enums\FinancialCategoryEnum;
use App\Enums\FinancialTypeEnum;
use App\Models\Financial;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $types = FinancialTypeEnum::cases();

        foreach ($users as $user) {
            foreach ($types as $type) {
                Financial::create([
                    'user_id' => $user->id,
                    'financial_type' => $type->value,
                    'category' => fake()->randomElement(FinancialCategoryEnum::cases())->value,
                    'amount' => fake()->numberBetween(500000, 20000000),
                ]);
            }
        }
    }
}
