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
        $costPrice = $this->faker->numberBetween(5000000, 50000000);
        $marginAmount = $this->faker->numberBetween(1000000, 10000000);
        $downPayment = $this->faker->numberBetween(500000, min(5000000, $costPrice));

        return [
            'financing_transaction_code' => 'PM' . strtoupper(uniqid()),
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'cost_price' => $costPrice,
            'margin_amount' => $marginAmount,
            'down_payment' => $downPayment,
            'requested_date' => $this->faker->dateTimeBetween('-6 months', '-3 months')->format('Y-m-d'),
            'akad_date' => $this->faker->dateTimeBetween('-3 months', '-1 month')->format('Y-m-d'),
            'paid_date' => null,
            'payment_method' => $this->faker->randomElement(FinancingPaymentMethodEnum::cases())->value,
            'signed_akad_document' => null,

            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'member_id' => Member::inRandomOrder()->first()?->id ?? Member::factory()->create()->id,
        ];
    }

    /**
     * Active financing with installments.
     */
    public function activeInstallments(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);
    }

    /**
     * Paid/completed financing.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::PAID->value,
            'paid_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Waiting for documents.
     */
    public function waitingDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
        ]);
    }
}

