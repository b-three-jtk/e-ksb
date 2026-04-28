<?php

namespace Database\Seeders;

use App\Enums\FinancialCategoryEnum;
use App\Enums\FinancialTypeEnum;
use App\Models\Financial;
use App\Models\Member;
use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();
        $types = FinancialTypeEnum::cases();

        foreach ($members as $member) {
            foreach ($types as $type) {
                Financial::create([
                    'member_id' => $member->id,
                    'financial_type' => $type->value,
                    'amount' => fake()->numberBetween(500000, 20000000),
                    'category' => fake()->randomElement(FinancialCategoryEnum::cases())->value,
                ]);
            }
        }
    }
}
