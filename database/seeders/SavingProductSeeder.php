<?php

namespace Database\Seeders;

use App\Enums\SavingTypeEnum;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Models\Pengguna;
use App\Models\Jurnal;
use App\Models\DetailJurnal;
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
        $anggotas = Anggota::all();

        if ($anggotas->isEmpty()) {
            return; // Skip jika tidak ada anggota
        }

        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Administrator Sistem'))->first() ?? Pengguna::first();

        foreach ($anggotas as $index => $anggota) {
            // Semua anggota punya Simpanan Pokok dan Wajib
            $this->seedSimpananPokok($anggota, $admin);
            $this->seedSimpananWajib($anggota, $admin);

            if ($index < 50) {
                // 50 anggota * 2M = 100M
                $this->seedTabunganAnggota($anggota, $admin, 2000000);
            } else {
                // Semua anggota butuh Tabungan Anggota > 1 bulan untuk syarat Murabahah
                $this->seedTabunganAnggota($anggota, $admin, 50000);
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
        $trx = TransaksiSimpanan::create([
            'akun_simpanan_id' => $account->id,
            'kode_transaksi_simpanan' => 'SP' . str_pad($anggota->id, 8, '0', STR_PAD_LEFT),
            'tipe_transaksi' => 'Penyetoran',
            'nominal_simpanan' => 100000,
            'saldo_setelah_transaksi' => 100000,
            'tgl_transaksi' => now()->subMonths(12),
            'metode_pembayaran_simpanan' => 'Tunai',
            'deskripsi_simpanan' => 'Setor Awal Simpanan Pokok',
            'status' => 'Diverifikasi',
            'verified_by' => $admin->id,
            'verified_at' => now()->subMonths(12),
            'updated_by' => $admin->id,
        ]);

        $this->createJurnal($trx, $admin->id, SavingTypeEnum::SIMPANAN_POKOK->value);
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

        // Transaksi bulanan selama 12 bulan (atau kurang jika bermasalah)
        $saldo = 0;
        $isProblematic = rand(1, 100) <= 20; // 20% chance to be problematic
        $maxMonths = $isProblematic ? rand(5, 8) : 12; // Stop early if problematic

        for ($i = 1; $i <= $maxMonths; $i++) {
            $saldo += 50000;
            $trx = TransaksiSimpanan::create([
                'akun_simpanan_id' => $account->id,
                'kode_transaksi_simpanan' => 'SW' . str_pad($anggota->id, 4, '0', STR_PAD_LEFT) . str_pad($i, 4, '0', STR_PAD_LEFT),
                'tipe_transaksi' => 'Penyetoran',
                'nominal_simpanan' => 50000,
                'saldo_setelah_transaksi' => $saldo,
                'tgl_transaksi' => now()->subMonths(13 - $i),
                'metode_pembayaran_simpanan' => 'Tunai',
                'deskripsi_simpanan' => 'Setoran Simpanan Wajib Bulan ke-' . $i,
                'status' => 'Diverifikasi',
                'verified_by' => $admin->id,
                'verified_at' => now()->subMonths(13 - $i),
                'updated_by' => $admin->id,
            ]);
            $this->createJurnal($trx, $admin->id, SavingTypeEnum::SIMPANAN_WAJIB->value);
        }
        
        $account->update(['saldo' => $saldo]);
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

        $trx = TransaksiSimpanan::create([
            'akun_simpanan_id' => $account->id,
            'kode_transaksi_simpanan' => 'TA' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'tipe_transaksi' => 'Penyetoran',
            'nominal_simpanan' => $amount,
            'metode_pembayaran_simpanan' => 'Tunai',
            'saldo_setelah_transaksi' => $amount,
            'tgl_transaksi' => now()->subMonths(8),
            'deskripsi_simpanan' => 'Setor Awal Tabungan Anggota',
            'status' => 'Diverifikasi',
            'verified_by' => $admin->id,
            'verified_at' => now()->subMonths(8),
            'updated_by' => $admin->id,
        ]);
        $this->createJurnal($trx, $admin->id, SavingTypeEnum::TABUNGAN_ANGGOTA->value);
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

        $trx = TransaksiSimpanan::create([
            'akun_simpanan_id' => $account->id,
            'kode_transaksi_simpanan' => 'TB' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'tipe_transaksi' => 'Penyetoran',
            'nominal_simpanan' => $amount,
            'saldo_setelah_transaksi' => $amount,
            'metode_pembayaran_simpanan' => 'Tunai',
            'tgl_transaksi' => now()->subMonths(6),
            'deskripsi_simpanan' => 'Setor Tabungan Berjangka',
            'status' => 'Diverifikasi',
            'verified_by' => $admin->id,
            'verified_at' => now()->subMonths(6),
            'updated_by' => $admin->id,
        ]);
        $this->createJurnal($trx, $admin->id, SavingTypeEnum::TABUNGAN_BERJANGKA->value);
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

        $trx = TransaksiSimpanan::create([
            'akun_simpanan_id' => $account->id,
            'kode_transaksi_simpanan' => 'TI' . str_pad($anggota->id, 5, '0', STR_PAD_LEFT) . '1',
            'tipe_transaksi' => 'Penyetoran',
            'nominal_simpanan' => $amount,
            'saldo_setelah_transaksi' => $amount,
            'tgl_transaksi' => now()->subMonths(10),
            'metode_pembayaran_simpanan' => 'Tunai',
            'deskripsi_simpanan' => 'Setor Awal Tabungan Ibadah',
            'status' => 'Diverifikasi',
            'verified_by' => $admin->id,
            'verified_at' => now()->subMonths(10),
            'updated_by' => $admin->id,
        ]);
        $this->createJurnal($trx, $admin->id, SavingTypeEnum::TABUNGAN_IBADAH->value);
    }

    private function createJurnal(TransaksiSimpanan $transaksi, string $adminId, string $jenisSimpanan): void
    {
        $noRefAkun = match ($jenisSimpanan) {
            SavingTypeEnum::SIMPANAN_POKOK->value => '301',
            SavingTypeEnum::SIMPANAN_WAJIB->value => '302',
            SavingTypeEnum::TABUNGAN_ANGGOTA->value => '201',
            SavingTypeEnum::TABUNGAN_BERJANGKA->value => '202',
            SavingTypeEnum::TABUNGAN_IBADAH->value => '203',
            default => '201',
        };

        $jurnal = Jurnal::create([
            'tgl_transaksi' => $transaksi->tgl_transaksi,
            'created_by' => $adminId,
        ]);

        DetailJurnal::create([
            'jurnal_id' => $jurnal->id,
            'no_ref_akun' => '101', // Debit Kas
            'posisi_akun' => 'Debit',
            'nominal' => $transaksi->nominal_simpanan,
            'updated_by' => $adminId,
        ]);

        DetailJurnal::create([
            'jurnal_id' => $jurnal->id,
            'no_ref_akun' => $noRefAkun, // Credit Simpanan
            'posisi_akun' => 'Credit',
            'nominal' => $transaksi->nominal_simpanan,
            'updated_by' => $adminId,
        ]);
    }
}
