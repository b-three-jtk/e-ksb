<?php

namespace Database\Factories;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Financing>
 */
class FinancingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financing_transaction_code' => $this->faker->unique()->numerify('PM########'),
            'financing_status' => $this->faker->randomElement(FinancingReqStatusEnum::cases())->value,
            'is_wakalah' => $this->faker->boolean(),
            'down_payment' => $this->faker->numberBetween(50000, 5000000),
            'akad_date' => $this->faker->date(),
            'paid_date' => $this->faker->date(),
            'payment_method' => $this->faker->randomElement(FinancingPaymentMethodEnum::cases())->value,
            'signed_akad_document' => $this->faker->optional()->url(),
            'notes' => $this->faker->optional()->sentence(),

            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'member_id' => Member::inRandomOrder()->first()?->id ?? Member::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
