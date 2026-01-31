<?php

namespace Database\Seeders;

use App\Enums\LoanStatus;
use App\Enums\TransactionMethods;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
use App\Models\Financing;
use Illuminate\Database\Seeder;
use App\Enums\FinancingReqStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $financings = Financing::where('status', FinancingReqStatus::ITEM_RECEIVED)->get();

        foreach ($financings as $financing) {
            $loan = Loan::create([
                'total_price' => fake()->numberBetween(1000000, 10000000),
                'tenor' => fake()->numberBetween(6, 36),
                'amount_ins' => fake()->numberBetween(100000, 500000),
                'financing_id' => $financing->id,
            ]);

            LoanPayment::create([
                'transaction_code' => 'LP' . strtoupper(fake()->unique()->bothify('########')),
                'status' => LoanStatus::PAID,
                'method' => fake()->randomElement(TransactionMethods::cases())->value,
                'attachment' => fake()->imageUrl(),
                'loan_id' => $loan->id,
                'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            ]);
        }
    }
}
