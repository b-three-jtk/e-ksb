<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed journal entries for all accounts
        $accounts = Account::all();

        foreach ($accounts as $account) {
            // Create a journal entry for each account
            $account->journalEntries()->create([
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
