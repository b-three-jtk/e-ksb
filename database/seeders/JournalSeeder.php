<?php

namespace Database\Seeders;

use App\Enums\AccountCategoryEnum;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::factory()->create([
            'no_ref_account' => '101',
            'account_name' => 'Kas',
            'account_category' => AccountCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'balance' => 5000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '102',
            'account_name' => 'Dana Alokasi Pembiayaan Murabahah',
            'account_category' => AccountCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '103',
            'account_name' => 'Piutang Murabahah',
            'account_category' => AccountCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '201',
            'account_name' => 'Tabungan Anggota',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '202',
            'account_name' => 'Tabungan Berjangka',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '203',
            'account_name' => 'Tabungan Ibadah',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '204',
            'account_name' => 'Uang Muka Murabahah',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '301',
            'account_name' => 'Simpanan Pokok',
            'account_category' => AccountCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '302',
            'account_name' => 'Simpanan Wajib',
            'account_category' => AccountCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        Account::factory()->create([
            'no_ref_account' => '401',
            'account_name' => 'Pendapatan Margin Murabahah',
            'account_category' => AccountCategoryEnum::REVENUE->value,
            'status' => 'Aktif',
            'balance' => 50000000,
        ]);

        // simulasi margin 12 bulan, dengan bulan terbaru tiap harinya diisi data
        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                for ($j = 1; $j <= 10; $j++) {
                    JournalEntry::factory()->create([
                        'no_ref_account' => '401',
                        'position' => 'Credit',
                        'nominal' => 50000000 / 12,
                        'transaction_date' => now()->subMonths(12 - $i)->addDays($j),
                    ]);
                }
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '401',
                    'position' => 'Credit',
                    'nominal' => 500000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }

        // simulasi kas 12 bulan terakhir
        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                JournalEntry::factory()->create([
                    'no_ref_account' => '101',
                    'position' => 'Debit',
                    'nominal' => 50000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '101',
                    'position' => 'Debit',
                    'nominal' => 500000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }


        // simulasi akun 201, 202, 203 dengan nominal 50000000 tiap bulan selama 12 bulan terakhir
        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                JournalEntry::factory()->create([
                    'no_ref_account' => '201',
                    'position' => 'Credit',
                    'nominal' => 50000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '201',
                    'position' => 'Credit',
                    'nominal' => 500000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }

        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                JournalEntry::factory()->create([
                    'no_ref_account' => '202',
                    'position' => 'Credit',
                    'nominal' => 50000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '202',
                    'position' => 'Credit',
                    'nominal' => 500000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }

        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                JournalEntry::factory()->create([
                    'no_ref_account' => '203',
                    'position' => 'Credit',
                    'nominal' => 50000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '203',
                    'position' => 'Credit',
                    'nominal' => 500000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }

        // simulasi akun 103
        for ($i = 1; $i <= 12; $i++) {
            if ($i == 11) {
                JournalEntry::factory()->create([
                    'no_ref_account' => '103',
                    'position' => 'Debit',
                    'nominal' => 40000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            } else {
                JournalEntry::factory()->create([
                    'no_ref_account' => '103',
                    'position' => 'Debit',
                    'nominal' => 400000000 / 12,
                    'transaction_date' => now()->subMonths(12 - $i),
                ]);
            }
        }

    }
}
