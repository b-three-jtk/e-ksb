<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\SavingType;
use App\Models\SavingAccount;
use App\Enums\TransactionType;
use Illuminate\Database\Seeder;
use App\Enums\TransactionStatus;
use App\Enums\TransactionMethods;
use App\Models\SavingTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SavingAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $savingTypes = [
            SavingType::SIMPANAN_POKOK->value,
            SavingType::SIMPANAN_WAJIB->value,
            SavingType::SIMPANAN_SUKARELA->value,
        ];

        $transactionMethods = collect(TransactionMethods::cases())->map(fn($m) => $m->value)->all();
        $transactionTypes = collect(TransactionType::cases())->map(fn($t) => $t->value)->all();
        $transactionStatus = collect(TransactionStatus::cases())->map(fn($t) => $t->value)->all();

        foreach ($users as $user) {
            foreach ($savingTypes as $type) {
                $account = SavingAccount::create([
                    'account_number' => 'SA' . strtoupper(uniqid()),
                    'balance' => rand(100000, 1000000),
                    'type' => $type,
                    'user_id' => $user->id,
                ]);

                SavingTransaction::create([
                    'transaction_code' => 'ST' . strtoupper(uniqid()),
                    'saving_account_id' => $account->id,
                    'amount' => $account->balance,
                    'method' => $transactionMethods[array_rand($transactionMethods)],
                    'type' => $transactionTypes[array_rand($transactionTypes)],
                    'status' => $transactionStatus[array_rand($transactionStatus)],
                    'transaction_date' => now(),
                    'description' => 'Initial deposit for ' . $type,
                    'updated_by' => User::inRandomOrder()->first()->id,
                    'account_number' => null,
                ]);
            }
        }
    }
}