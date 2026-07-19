<?php

use App\Models\AkunSimpanan;
use App\Models\Anggota;
use App\Models\PembayaranAngsuran;
use App\Models\Pembiayaan;
use App\Models\PengaturanUmum;
use App\Models\Pengguna;
use App\Models\Poin;
use Symfony\Component\Console\Command\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('Command CalculateMonthlySavingPoints', function () {
    it('Menghitung poin dengan benar berdasarkan pengaturan poin simpanan', function () {
        // Buat Anggota dan Akun Simpanan
        $anggota = Anggota::factory()->create();
        $user = $anggota->user;

        // Setup Pengaturan
        PengaturanUmum::create(['key' => 'saving_point_amount', 'value' => '100000', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'saving_point_reward', 'value' => '1', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        
        AkunSimpanan::create([
            'anggota_id' => $anggota->id,
            'saldo' => 250000,
            'jenis_simpanan' => 'Tabungan Anggota',
            'kode_akun_simpanan' => 'SIMP001'
        ]); 
        // Perhitungan: floor(250000 / 100000) * 1 = 2 poin

        $exitCode = Artisan::call('points:calculate-monthly-savings');

        expect($exitCode)->toBe(Command::SUCCESS);
        expect(Poin::count())->toBe(1);
        
        $poin = Poin::first();
        expect($poin->pengguna_id)->toBe($user->id);
        expect((int)$poin->jml_perolehan)->toBe(2);
        expect((float)$poin->sisa_tabungan_snapshot)->toBe(250000.0);
    });
});

describe('Menghitung poin murabahah setelah tutup buku', function () {
    it('Menghitung poin dengan benar berdasarkan total margin yang dibayar', function () {
        $anggota = Anggota::factory()->create();
        $user = $anggota->user;

        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();
        
        PengaturanUmum::create(['key' => 'tanggal_awal_periode', 'value' => $startDate, 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'tanggal_akhir_periode', 'value' => $endDate, 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'status_tutup_buku', 'value' => 'closed', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'murabaha_point_amount', 'value' => '50000', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'murabaha_point_reward', 'value' => '2', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        
        $pembiayaan = Pembiayaan::create([
            'anggota_id' => $anggota->id,
            'status' => \App\Enums\FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now(),
            'tgl_permohonan' => now(),
            'tenor' => 12
        ]);
        
        $angsuran = $pembiayaan->angsuran()->create([
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1000,
            'tgl_jatuh_tempo' => now(),
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::PAID->value
        ]);
        
        PembayaranAngsuran::create([
            'angsuran_id' => $angsuran->id,
            'margin_dibayar' => 125000,
            'tgl_pembayaran' => now()->toDateString(),
            'pokok_dibayar' => 1000,
            'jumlah_angsuran_dibayar' => 126000,
            'metode_pembayaran' => 'Tunai',
            'status' => 'Diverifikasi',
            'kode_transaksi_pembayaran' => 'TRX001',
            'updated_by' => $user->id,
            'created_by' => $user->id
        ]); 
        // Perhitungan: floor(125000 / 50000) * 2 = 4 poin

        $exitCode = Artisan::call('points:calculate-murabahah-points');

        expect($exitCode)->toBe(Command::SUCCESS);
        expect(Poin::count())->toBe(1);
        
        $poin = Poin::first();
        expect($poin->pengguna_id)->toBe($user->id);
        expect((int)$poin->jml_perolehan)->toBe(4);
    });

    it('Membatalkan perhitungan poin murabahah jika status tutup buku belum closed', function () {
        $user = Pengguna::factory()->create();
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();
        
        PengaturanUmum::create(['key' => 'tanggal_awal_periode', 'value' => $startDate, 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'tanggal_akhir_periode', 'value' => $endDate, 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        PengaturanUmum::create(['key' => 'status_tutup_buku', 'value' => 'open', 'tgl_diberlakukan' => now()->subDay(), 'created_by' => $user->id, 'updated_by' => $user->id]);
        
        $exitCode = Artisan::call('points:calculate-murabahah-points');
        
        expect($exitCode)->toBe(Command::FAILURE);
        expect(Poin::count())->toBe(0);
    });
});
