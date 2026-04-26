<?php

namespace Database\Factories;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Installment;
use App\Models\InstallmentPaymentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentPaymentScheduleFactory extends Factory
{
    protected $model = InstallmentPaymentSchedule::class;

    public function definition(): array
    {
        return [
            'due_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'installment_number' => $this->faker->numberBetween(1, 60),
            'installment_schedule_status' => $this->faker->randomElement(InstallmentPaymentScheduleStatusEnum::cases())->value,
            'installment_id' => Installment::factory(),
        ];
    }
}
