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

        // simulasi margin 12 bulan
        for ($i = 1; $i <= 12; $i++) {
            JournalEntry::factory()->create([
                'no_ref_account' => '401',
                'position' => 'Kredit',
                'nominal' => 50000000 / 12,
                'transaction_date' => now()->subMonths(12 - $i),
            ]);
        }
    }
}
