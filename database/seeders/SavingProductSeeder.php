<?php

namespace Database\Seeders;

use App\Enums\SavingTypeEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SavingProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();

        if ($members->isEmpty()) {
            return; // Skip jika tidak ada member
        }

        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Administrator Sistem'))->first() ?? Pengguna::first();

        foreach ($members as $index => $member) {
            // Semua member punya Simpanan Pokok dan Wajib
            $this->seedSimpananPokok($member, $admin);
            $this->seedSimpananWajib($member, $admin);

            if ($index < 50) {
                // 50 members * 2M = 100M
                $this->seedTabunganAnggota($member, $admin, 2000000);
            }
            if ($index >= 50 && $index < 60) {
                // 10 members * 5M = 50M
                $this->seedTabunganBerjangka($member, $admin, 5000000);
            }
            if ($index >= 60 && $index < 65) {
                // 5 members * 10M = 50M
                $this->seedTabunganIbadah($member, $admin, 10000000);
            }
        }
    }

    private function seedSimpananPokok(Member $member, Pengguna $admin): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'SP-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'balance' => 100000,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(12),
        ]);

        // Transaksi awal (setor simpanan pokok)
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'SP' . str_pad($member->id, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 100000,
            'balance_after_transaction' => 100000,
            'transaction_date' => now()->subMonths(12),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Setor Awal Simpanan Pokok',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedSimpananWajib(Member $member, Pengguna $admin): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'SW-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'balance' => 600000,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(12),
        ]);

        // Transaksi bulanan selama 12 bulan
        $balance = 0;
        for ($i = 1; $i <= 12; $i++) {
            $balance += 50000;
            SavingTransaction::create([
                'saving_account_id' => $account->id,
                'saving_transaction_code' => 'SW' . str_pad($member->id, 4, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'Penyetoran',
                'saving_amount' => 50000,
                'balance_after_transaction' => $balance,
                'transaction_date' => now()->subMonths(13 - $i),
                'saving_payment_method' => 'Tunai',
                'saving_description' => 'Setoran Simpanan Wajib Bulan ke-' . $i,
                'updated_by' => $admin->id,
            ]);
        }
    }

    private function seedTabunganAnggota(Member $member, Pengguna $admin, $amount): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TA-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => $amount,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(8),
        ]);

        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TA' . str_pad($member->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'saving_payment_method' => 'Tunai',
            'balance_after_transaction' => $amount,
            'transaction_date' => now()->subMonths(8),
            'saving_description' => 'Setor Awal Tabungan Anggota',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganBerjangka(Member $member, Pengguna $admin, $amount): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TB-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'balance' => $amount,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(6),
        ]);

        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TB' . str_pad($member->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'balance_after_transaction' => $amount,
            'saving_payment_method' => 'Tunai',
            'transaction_date' => now()->subMonths(6),
            'saving_description' => 'Setor Tabungan Berjangka',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganIbadah(Member $member, Pengguna $admin, $amount): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TI-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'balance' => $amount,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(10),
        ]);

        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad($member->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'balance_after_transaction' => $amount,
            'transaction_date' => now()->subMonths(10),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Setor Awal Tabungan Ibadah',
            'updated_by' => $admin->id,
        ]);
    }
}
