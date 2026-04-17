<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodsEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class SavingAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $savingTypes = [
            SavingTypeEnum::SIMPANAN_POKOK->value,
            SavingTypeEnum::SIMPANAN_WAJIB->value,
            SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            SavingTypeEnum::TABUNGAN_IBADAH->value,
        ];

        $transactionMethods = collect(PaymentMethodsEnum::cases())->map(fn($m) => $m->value)->all();
        $transactionTypes = collect(TransactionTypeEnum::cases())->map(fn($t) => $t->value)->all();

        foreach ($users as $user) {
            foreach ($savingTypes as $type) {
                $account = SavingAccount::create([
                    'saving_account_code' => 'SA' . random_int(100000, 999999),
                    'saving_tenor' => $type === SavingTypeEnum::TABUNGAN_BERJANGKA->value ? fake()->numberBetween(6, 24) : null,
                    'target_amount' => $type === SavingTypeEnum::TABUNGAN_IBADAH->value ? fake()->numberBetween(1000000, 10000000) : null,
                    'saving_type' => $type,
                    'user_id' => $user->id,
                ]);

                SavingTransaction::create([
                    'saving_transaction_code' => 'ST' . random_int(100000, 939999),
                    'saving_account_id' => $account->id,
                    'saving_amount' => fake()->numberBetween(50000, 500000),
                    'saving_payment_method' => $transactionMethods[array_rand($transactionMethods)],
                    'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
                    'transaction_date' => now(),
                    'saving_description' => 'Initial deposit for ' . $type,
                    'updated_by' => User::inRandomOrder()->first()->id,
                    'account_number' => null,
                ]);
            }
        }
    }
}
