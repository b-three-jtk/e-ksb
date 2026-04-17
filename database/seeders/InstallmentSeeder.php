<?php

namespace Database\Seeders;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\Financing;
use App\Models\Installment;
use App\Models\InstallmentPaymentSchedule;
use App\Models\InstallmentPaymentTransaction;
use Illuminate\Database\Seeder;

class InstallmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $financings = Financing::where('financing_status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS)
            ->where('financing_transaction_code', '!=', 'PM00000001')
            ->get();

        foreach ($financings as $financing) {
            $installment = Installment::create([
                'tenor' => fake()->numberBetween(6, 36),
                'financing_id' => $financing->id,
            ]);

            $installmentPaymentSchedule = InstallmentPaymentSchedule::create([
                'installment_schedule_status' => InstallmentPaymentScheduleStatusEnum::PAID,
                'installment_number' => 1,
                'due_date' => now()->addMonth(),
                'installment_id' => $installment->id,
            ]);

            InstallmentPaymentTransaction::create([
                'installment_trans_code' => 'LP' . str_pad(fake()->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
                'principal_paid' => fake()->numberBetween(50000, 300000),
                'margin_paid' => fake()->numberBetween(10000, 200000),
                'installment_payment_method' => PaymentMethodsEnum::CASH->value,
                'is_early_repayment' => false,
                'schedule_id' => $installmentPaymentSchedule->id,
                'payment_date' => now(),
            ]);
        }

        // SIMULATION
        $installment = Installment::create([
            'tenor' => 10,
            'financing_id' => Financing::where('financing_transaction_code', 'PM00000001')->first()?->id,
        ]);

        for ($i = 1; $i <= $installment->tenor; $i++) {
            $status = $i < 5 ? InstallmentPaymentScheduleStatusEnum::PAID : InstallmentPaymentScheduleStatusEnum::SCHEDULED;

            $installmentPaymentSchedule = InstallmentPaymentSchedule::create([
                'installment_schedule_status' => $status,
                'installment_number' => $i,
                'due_date' => now()->addMonths($i),
                'installment_id' => $installment->id,
            ]);

            if ($status === InstallmentPaymentScheduleStatusEnum::PAID) {
                InstallmentPaymentTransaction::create([
                    'installment_trans_code' => 'LP' . str_pad(fake()->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
                    'principal_paid' => fake()->numberBetween(50000, 300000),
                    'margin_paid' => fake()->numberBetween(10000, 200000),
                    'installment_payment_method' => PaymentMethodsEnum::CASH->value,
                    'is_early_repayment' => false,
                    'schedule_id' => $installmentPaymentSchedule->id,
                    'payment_date' => now(),
                ]);
            }
        }
    }
}
