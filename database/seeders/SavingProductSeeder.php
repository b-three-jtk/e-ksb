<?php

namespace Database\Seeders;

use App\Enums\SavingTypeEnum;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
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
        $anggota = Anggota::all();

        if ($anggota->isEmpty()) {
            return; // Skip jika tidak ada anggota
        }

        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Administrator Sistem'))->first() ?? Pengguna::first();

        foreach ($anggota as $index => $anggota) {
            // Semua anggota punya Simpanan Pokok dan Wajib
            $this->seedSimpananPokok($anggota, $admin);
            $this->seedSimpananWajib($anggota, $admin);

            if ($index < 50) {
                // 50 anggota * 2M = 100M
                $this->seedTabunganAnggota($anggota, $admin, 2000000);
            }
            if ($index >= 50 && $index < 60) {
                // 10 anggota * 5M = 50M
                $this->seedTabunganBerjangka($anggota, $admin, 5000000);
            }
            if ($index >= 60 && $index < 65) {
                // 5 anggota * 10M = 50M
                $this->seedTabunganIbadah($anggota, $admin, 10000000);
            }
        }
    }

    private function seedSimpananPokok(Anggota $anggota, Pengguna $admin): void
    {
        $account = AkunSimpanan::create([
            'kode_akun_simpanan' => 'SP-' . str_pad($anggota->id, 6, '0', STR_PAD_LEFT),
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 100000,
            'anggota_id' => $anggota->id,
            'created_at' => now()->subMonths(12),
        ]);

        // Transaksi awal (setor simpanan pokok)
        SavingTransaction::create([
            'akun_simpanan_id' => $account->id,
            'saving_transaction_code' => 'SP' . str_pad($anggota->id, 8, '0', STR_PAD_LEFT),
            'transaction_type' => 'Penyetoran',
            'saving_amount' => 100000,
            'balance_after_transaction' => 100000,
            'transaction_date' => now()->subMonths(12),
            'saving_metode_pembayaran' => 'Tunai',
            'saving_description' => 'Setor Awal Simpanan Pokok',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedSimpananWajib(Anggota $anggota, Pengguna $admin): void
    {
        $account = AkunSimpanan::create([
            'kode_akun_simpanan' => 'SW-' . str_pad($anggota->id, 6, '0', STR_PAD_LEFT),
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'saldo' => 600000,
            'anggota_id' => $anggota->id,
            'created_at' => now()->subMonths(12),
        ]);

        // Transaksi bulanan selama 12 bulan
        $saldo = 0;
        for ($i = 1; $i <= 12; $i++) {
            $saldo += 50000;
            SavingTransaction::create([
                'akun_simpanan_id' => $account->id,
                'saving_transaction_code' => 'SW' . str_pad($anggota->id, 4, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT),
                'transaction_type' => 'Penyetoran',
                'saving_amount' => 50000,
                'balance_after_transaction' => $saldo,
                'transaction_date' => now()->subMonths(13 - $i),
                'saving_metode_pembayaran' => 'Tunai',
                'saving_description' => 'Setoran Simpanan Wajib Bulan ke-' . $i,
                'updated_by' => $admin->id,
            ]);
        }
    }

    private function seedTabunganAnggota(Anggota $anggota, Pengguna $admin, $amount): void
    {
        $account = AkunSimpanan::create([
            'kode_akun_simpanan' => 'TA-' . str_pad($anggota->id, 6, '0', STR_PAD_LEFT),
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => $amount,
            'anggota_id' => $anggota->id,
            'created_at' => now()->subMonths(8),
        ]);

        SavingTransaction::create([
            'akun_simpanan_id' => $account->id,
            'saving_transaction_code' => 'TA' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'saving_metode_pembayaran' => 'Tunai',
            'balance_after_transaction' => $amount,
            'transaction_date' => now()->subMonths(8),
            'saving_description' => 'Setor Awal Tabungan Anggota',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganBerjangka(Anggota $anggota, Pengguna $admin, $amount): void
    {
        $account = AkunSimpanan::create([
            'kode_akun_simpanan' => 'TB-' . str_pad($anggota->id, 6, '0', STR_PAD_LEFT),
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'saldo' => $amount,
            'anggota_id' => $anggota->id,
            'created_at' => now()->subMonths(6),
        ]);

        SavingTransaction::create([
            'akun_simpanan_id' => $account->id,
            'saving_transaction_code' => 'TB' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'balance_after_transaction' => $amount,
            'saving_metode_pembayaran' => 'Tunai',
            'transaction_date' => now()->subMonths(6),
            'saving_description' => 'Setor Tabungan Berjangka',
            'updated_by' => $admin->id,
        ]);
    }

    private function seedTabunganIbadah(Anggota $anggota, Pengguna $admin, $amount): void
    {
        $account = AkunSimpanan::create([
            'kode_akun_simpanan' => 'TI-' . str_pad($anggota->id, 6, '0', STR_PAD_LEFT),
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'saldo' => $amount,
            'anggota_id' => $anggota->id,
            'created_at' => now()->subMonths(10),
        ]);

        SavingTransaction::create([
            'akun_simpanan_id' => $account->id,
            'saving_transaction_code' => 'TI' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'transaction_type' => 'Penyetoran',
            'saving_amount' => $amount,
            'balance_after_transaction' => $amount,
            'transaction_date' => now()->subMonths(10),
            'saving_metode_pembayaran' => 'Tunai',
            'saving_description' => 'Setor Awal Tabungan Ibadah',
            'updated_by' => $admin->id,
        ]);
    }
}
