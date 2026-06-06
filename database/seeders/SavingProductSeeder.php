<?php

namespace Database\Seeders;

use App\Enums\SavingTypeEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
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
        $members = Member::take(15)->get();

        if ($members->isEmpty()) {
            return; // Skip jika tidak ada member
        }

        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? User::first();

        // Seeder 1: Simpanan Pokok (Pastikan index 0 ada)
        if ($member1 = $members->get(0)) $this->seedSimpananPokok($member1, $admin);

        // Seeder 2: Simpanan Wajib (Pastikan index 1 ada)
        if ($member2 = $members->get(1)) $this->seedSimpananWajib($member2, $admin);

        // Seeder 3: Tabungan Anggota (Pastikan index 2 ada)
        if ($member3 = $members->get(2)) $this->seedTabunganAnggota($member3, $admin);

        // Seeder 4: Tabungan Berjangka (Pastikan index 3 ada)
        if ($member4 = $members->get(3)) $this->seedTabunganBerjangka($member4, $admin);

        // Seeder 5: Tabungan Ibadah (Pastikan index 4 ada)
        if ($member5 = $members->get(4)) $this->seedTabunganIbadah($member5, $admin);
    }

    private function seedSimpananPokok(Member $member, User $admin): void
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
            'saving_transaction_code' => 'SP' . str_pad(1, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 100000,
            'balance_after_transaction' => 100000,
            'transaction_date' => now()->subMonths(12),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Setor Awal Simpanan Pokok',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedSimpananWajib(Member $member, User $admin): void
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
                'saving_transaction_code' => 'SW' . str_pad($i, 8, '0', STR_PAD_LEFT),
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

    private function seedTabunganAnggota(Member $member, User $admin): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TA-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 5000000,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(8),
        ]);

        // Setor awal
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TA' . str_pad(1, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 3000000,
            'saving_payment_method' => 'Tunai',
            'balance_after_transaction' => 3000000,
            'transaction_date' => now()->subMonths(8),
            'saving_description' => 'Setor Awal Tabungan Anggota',
            'updated_by' => $admin->id,
        ]);

        // Setor tambahan
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TA' . str_pad(2, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 1500000,
            'balance_after_transaction' => 4500000,
            'saving_payment_method' => 'Tunai',
            'transaction_date' => now()->subMonths(5),
            'saving_description' => 'Setor Tabungan Anggota',
            'updated_by' => $admin->id,
        ]);

        // Penarikan
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TA' . str_pad(3, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penarikan',
            'saving_amount' => 500000,
            'saving_payment_method' => 'Tunai',
            'balance_after_transaction' => 4000000,
            'transaction_date' => now()->subMonths(2),
            'saving_description' => 'Penarikan Tabungan Anggota',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganBerjangka(Member $member, User $admin): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TB-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'balance' => 10500000,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(6),
        ]);

        // Setor awal 6 bulan yang lalu
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TB' . str_pad(1, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 10000000,
            'balance_after_transaction' => 10000000,
            'saving_payment_method' => 'Tunai',
            'transaction_date' => now()->subMonths(6),
            'saving_description' => 'Setor Tabungan Berjangka 12 Bulan',
            'updated_by' => $admin->id,
        ]);

        // Bunga (jika ada sistem bunga)
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TB' . str_pad(2, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 500000,
            'saving_payment_method' => 'Tunai',
            'balance_after_transaction' => 10500000,
            'transaction_date' => now()->subMonths(3),
            'saving_description' => 'Bunga Tabungan Berjangka 3 Bulan',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganIbadah(Member $member, User $admin): void
    {
        $account = SavingAccount::create([
            'saving_account_code' => 'TI-' . str_pad($member->id, 6, '0', STR_PAD_LEFT),
            'saving_type' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'balance' => 15000000,
            'member_id' => $member->id,
            'created_at' => now()->subMonths(10),
        ]);

        // Setor awal
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad(1, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 5000000,
            'balance_after_transaction' => 5000000,
            'transaction_date' => now()->subMonths(10),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Setor Awal Tabungan Ibadah (Haji)',
            'updated_by' => $admin->id,
        ]);

        // Setor berkala
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad(2, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 3000000,
            'balance_after_transaction' => 8000000,
            'transaction_date' => now()->subMonths(8),
            'saving_description' => 'Setor Tabungan Ibadah',
            'saving_payment_method' => 'Tunai',
            'updated_by' => $admin->id,
        ]);

        // Setor lagi
        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad(3, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 4000000,
            'balance_after_transaction' => 12000000,
            'transaction_date' => now()->subMonths(5),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Setor Tabungan Ibadah',
            'updated_by' => $admin->id,
        ]);

        SavingTransaction::create([
            'saving_account_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad(4, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 3000000,
            'balance_after_transaction' => 15000000,
            'transaction_date' => now()->subMonths(2),
            'saving_payment_method' => 'Tunai',
            'saving_description' => 'Bunga Tabungan Ibadah',
            'updated_by' => $admin->id,
        ]);
    }
}
