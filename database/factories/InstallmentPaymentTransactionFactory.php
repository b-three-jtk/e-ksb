<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Models\InstallmentPaymentSchedule;
use App\Models\InstallmentPaymentTransaction;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentPaymentTransactionFactory extends Factory
{
    protected $model = InstallmentPaymentTransaction::class;

    public function definition(): array
    {
        return [
            'installment_trans_code' => $this->faker->unique()->numerify('IPT-#########'),
            'installment_payment_method' => $this->faker->randomElement(PaymentMethodsEnum::cases())->value,
            'is_early_repayment' => $this->faker->boolean(),
            'principal_paid' => $this->faker->numberBetween(100000, 10000000),
            'margin_paid' => $this->faker->numberBetween(10000, 1000000),
            'payment_date' => $this->faker->dateTime(),
            'schedule_id' => InstallmentPaymentSchedule::factory(),
            'updated_by' => User::factory(),
            'point_id' => PointTransaction::factory(),
            'installment_payment_receipt' => $this->faker->optional()->filePath(),
        ];
    }
}
