<?php

namespace Database\Seeders;

use App\Models\Financial;
use App\Models\User;
use App\Enums\FinancialType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $types = FinancialType::cases();

        foreach ($users as $user) {
            foreach ($types as $type) {
                Financial::create([
                    'user_id' => $user->id,
                    'financial_type' => $type->value,
                    'amount' => fake()->numberBetween(500000, 20000000),
                ]);
            }
        }
    }
}
