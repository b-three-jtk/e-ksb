<?php

namespace Database\Seeders;

use App\Enums\AccountCategoryEnum;
use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
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
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '102',
            'account_name' => 'Pembiayaan Dalam Proses',
            'account_category' => AccountCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '104',
            'account_name' => 'Piutang Murabahah',
            'account_category' => AccountCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '201',
            'account_name' => 'Tabungan Anggota',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '202',
            'account_name' => 'Tabungan Berjangka',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '203',
            'account_name' => 'Tabungan Ibadah',
            'account_category' => AccountCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '301',
            'account_name' => 'Simpanan Pokok',
            'account_category' => AccountCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '302',
            'account_name' => 'Simpanan Wajib',
            'account_category' => AccountCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);

        Account::factory()->create([
            'no_ref_account' => '401',
            'account_name' => 'Pendapatan Margin Murabahah',
            'account_category' => AccountCategoryEnum::REVENUE->value,
            'status' => 'Aktif',
            'balance' => 0,
        ]);
    }
}
