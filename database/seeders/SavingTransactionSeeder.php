<?php

namespace Database\Seeders;

use App\Models\SavingTransaction;
use App\Models\SavingAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SavingTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $savingAccounts = SavingAccount::with('user.accounts')->get();

        foreach ($savingAccounts as $savingAccount) {

            $user = $savingAccount->user;
            $bankAccounts = $user?->accounts ?? collect();

            for ($i = 0; $i < 3; $i++) {

                $method = rand(0, 1) ? 'Tunai' : 'Non-Tunai';

                $accountNumber = null;

                // sumber dana hanya diambil dari Account (rekening bank)
                if ($method === 'Non-Tunai' && $bankAccounts->isNotEmpty()) {
                    $accountNumber = $bankAccounts->random()->account_number;
                }

                SavingTransaction::create([
                    'id' => Str::uuid(),
                    'saving_account_id' => $savingAccount->id, // akun simpanan koperasi
                    'amount' => rand(100_000, 500_000),
                    'type' => 'Penarikan',
                    'status' => 'Selesai',
                    'method' => $method,
                    'account_number' => $accountNumber, // sumber dana bank
                    'transaction_date' => now()->subDays(rand(1, 30)),
                    'updated_by' => $user->id,
                ]);
            }
        }
    }
}
