<?php

namespace Database\Factories;

use App\Enums\LoanStatusEnum;
use App\Models\AmdkProduct;
use App\Models\GallonLoan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GallonLoanFactory extends Factory
{
    protected $model = GallonLoan::class;

    public function definition(): array
    {
        return [
            'amdk_product_id' => AmdkProduct::factory(),
            'member_id' => Member::factory(),
            'return_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'loan_status' => $this->faker->randomElement(LoanStatusEnum::cases())->value,
            'updated_by' => User::factory(),
        ];
    }
}
