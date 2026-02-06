<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Enums\LoanStatus;
use App\Models\Financing;
use App\Models\LoanPayment;
use Illuminate\Database\Seeder;
use App\Enums\LoanPaymentStatus;
use App\Enums\FinancingReqStatus;
use App\Enums\TransactionMethods;
use App\Models\LoanPaymentSchedule;
use App\Enums\LoanPaymentScheduleStatus;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $financings = Financing::where('status', FinancingReqStatus::ACTIVE_INSTALLMENTS)
            ->where('transaction_code', '!=', 'PM00000001')
            ->get();

        foreach ($financings as $financing) {
            $loan = Loan::create([
                'total_loan' => fake()->numberBetween(1000000, 10000000),
                'remaining_principal' => fake()->numberBetween(500000, 9000000),
                'remaining_margin' => fake()->numberBetween(100000, 500000),
                'tenor' => fake()->numberBetween(6, 36),
                'monthly_installment' => fake()->numberBetween(100000, 500000),
                'financing_id' => $financing->id,
            ]);

            $loanPaymentSchedule = LoanPaymentSchedule::create([
                'total_amount' => fake()->numberBetween(100000, 500000),
                'principal_amount' => fake()->numberBetween(50000, 300000),
                'margin_amount' => fake()->numberBetween(10000, 200000),
                'status' => LoanPaymentScheduleStatus::PAID,
                'installment_number' => 1,
                'due_date' => now()->addMonth(),
                'loan_id' => $loan->id,
            ]);

            LoanPayment::create([
                'transaction_code' => 'LP' . str_pad(fake()->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
                'amount' => fake()->numberBetween(100000, 500000),
                'principal_paid' => fake()->numberBetween(50000, 300000),
                'margin_paid' => fake()->numberBetween(10000, 200000),
                'status' => LoanPaymentStatus::PAID,
                'method' => TransactionMethods::CASH->value,
                'attachment' => null,
                'is_early_repayment' => false,
                'loan_payment_schedule_id' => $loanPaymentSchedule->id,
                'payment_date' => now(),
            ]);
        }
        // SIMULATION
        $loan = Loan::create([
            'total_loan' => 20000000,
            'remaining_principal' => 10200000,
            'remaining_margin' => 1800000,
            'tenor' => 10,
            'monthly_installment' => 2000000,
            'financing_id' => Financing::where('transaction_code', 'PM00000001')->first()?->id,
        ]);

        for ($i = 1; $i <= $loan->tenor; $i++) {
            $status = $i < 5 ? LoanPaymentScheduleStatus::PAID : LoanPaymentScheduleStatus::SCHEDULED;

            $loanPaymentSchedule = LoanPaymentSchedule::create([
                'total_amount' => 2000000,
                'principal_amount' => 1700000,
                'margin_amount' => 300000,
                'status' => $status,
                'installment_number' => $i,
                'due_date' => now()->addMonths($i),
                'loan_id' => $loan->id,
            ]);

            if ($status === LoanPaymentScheduleStatus::PAID) {
                LoanPayment::create([
                    'transaction_code' => 'LP' . str_pad(fake()->unique()->numberBetween(1, 99999999), 8, '0', STR_PAD_LEFT),
                    'amount' => 2000000,
                    'principal_paid' => 1700000,
                    'margin_paid' => 300000,
                    'status' => LoanPaymentStatus::PAID,
                    'method' => TransactionMethods::CASH->value,
                    'attachment' => null,
                    'is_early_repayment' => false,
                    'loan_payment_schedule_id' => $loanPaymentSchedule->id,
                    'payment_date' => now()->addMonths($i),
                ]);
            }
        }
    }
}
