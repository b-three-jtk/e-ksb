<?php

use App\Enums\MemberStatusEnum;
use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\BerjangkaAccount;
use App\Models\GlobalSetting;
use App\Models\IbadahAccount;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Models\Pengguna;
use Database\Seeders\AkunSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
});

describe('Aplikasi harus menyediakan pencatatan transaksi penyetoran simpanan anggota oleh penanggung jawab.', function () {
    it('PJ dapat mencatat transaksi penyetoran simpanan anggota dengan data yang valid', function () {
        $pjanggota = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('transaksi_simpanan', [
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penyetoran simpanan pokok lebih dari satu kali untuk anggota yang sama', function () {
        $pjanggota = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        // Simpanan pokok pertama
        $response1 = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 100000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran pokok anggota baru',
            ]);

        $response1->assertSessionHasNoErrors();

        $anggota->update([
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ]);

        // Simpanan pokok kedua
        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 100000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran pokok anggota kedua',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.'
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penyetoran simpanan pokok untuk selain anggota tanggung jawabnya', function () {
        $pjanggota1 = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota1->syncRoles('Penanggung Jawab Anggota');
        $member1 = Anggota::factory([
            'pj_anggota_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $pjanggota2 = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota2->syncRoles('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $member1->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran pokok anggota oleh PJ lain',
            ]);

            $res->assertStatus(403);
    });

    it('Transaksi tabungan ibadah yang sudah mencapai target tidak bisa menerima setoran tambahan', function () {
        $pjanggota = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $user = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory([
            'pengguna_id' => $user->id,
            'status' => 'Aktif',
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $sa = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'saldo' => 5000000,
        ]);

        $ia = IbadahAccount::create([
            'target_amount' => 5000000,
            'purpose' => 'Tabungan untuk Haji 2026',
            'akun_simpanan_id' => $sa->id,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $sa->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
                'amount' => 100000,
                'target_amount' => $ia->target_amount,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'purpose' => 'Tabungan untuk Haji 2026',
                'notes' => 'Setoran tambahan tabungan ibadah',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.'
        ]);
    });

    it('PJ tidak dapat memproses penyetoran simpanan pokok untuk anggota yang berstatus aktif', function () {
        $pjanggota = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'status' => 'Aktif',
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 100000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran pokok anggota baru',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Simpanan Pokok hanya untuk anggota Menunggu Pembayaran.'
        ]);
    });
});

describe('Aplikasi harus menyediakan pencatatan transaksi penarikan simpanan anggota oleh penanggung jawab.', function () {
    it('PJ dapat mencatat transaksi penarikan simpanan anggota dengan data yang valid', function () {
        $pjanggota = Pengguna::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('transaksi_simpanan', [
            'nominal_simpanan' => 100000,
            'tipe_transaksi' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);
    });

    it('Nominal penarikan tidak boleh melebihi saldo tabungan', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => 'Tabungan Anggota',
            'saldo' => 200000, // Saldo hanya 200rb
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 500000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors('amount');
        $response->assertSessionHasErrors([
            'amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp 500,000'
        ]);
    });

    it('Dana Tabungan Berjangka tidak dapat ditarik sebelum jatuh tempo.', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $waktuBuat = now();
        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'saldo' => 1000000,
            'created_at' => $waktuBuat,
        ]);

        $tenorBulan = 6;
        BerjangkaAccount::create([
            'akun_simpanan_id' => $akunSimpanan->id,
            'tenor' => $tenorBulan,
            'purpose' => 'Tabungan Berjangka 6 bulan',
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 500000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $expectedMaturityDate = $waktuBuat->copy()->addMonths($tenorBulan)->startOfDay();
        $expectedMessage = 'Tabungan berjangka belum jatuh tempo. Pencairan dapat dilakukan mulai ' . $expectedMaturityDate->format('d/m/Y');

        $response->assertSessionHasErrors([
            'akun_simpanan_id' => $expectedMessage
        ]);
    });

    it('Simpanan Pokok tidak dapat ditarik oleh Anggota Koperasi selama status keanggotaannya masih aktif.', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 500000,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'akun_simpanan_id' => 'Simpanan Pokok tidak dapat ditarik selama status keanggotaan masih aktif.'
        ]);
    });

    it('Simpanan Wajib tidak dapat ditarik oleh Anggota Koperasi selama status keanggotaannya masih aktif.', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'saldo' => 500000,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'akun_simpanan_id' => 'Simpanan Wajib tidak dapat ditarik selama status keanggotaan masih aktif.'
        ]);
    });

    it('Dana Tabungan Ibadah tidak dapat dicairkan sebelum target nominal tercapai.', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'saldo' => 4000000,
        ]);

        IbadahAccount::create([
            'target_amount' => 5000000,
            'purpose' => 'Tabungan untuk Haji 2026',
            'akun_simpanan_id' => $akunSimpanan->id,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 1000000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'akun_simpanan_id' => 'Tabungan ibadah belum mencapai target minimal Rp 5.000.000'
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penarikan simpanan pokok untuk selain anggota tanggung jawabnya', function () {
        $pjanggota1 = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota1->syncRoles('Penanggung Jawab Anggota');
        $member1 = Anggota::factory([
            'pj_anggota_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $member1->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 500000,
        ]);

        $pjanggota2 = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota2->syncRoles('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $member1->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

            $res->assertSessionHasErrors([
                'error' => 'Terjadi kesalahan: Anda tidak berhak melakukan transaksi untuk anggota ini.'
            ]);
    });
});

describe('Aplikasi harus menghasilkan bukti transaksi untuk setiap transaksi setoran dan penarikan simpanan oleh penanggung jawab anggota.', function () {
    it('Bukti transaksi berupa file PDF dihasilkan setelah transaksi setoran simpanan berhasil dicatat', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('transaksi_simpanan', [
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $transaction = TransaksiSimpanan::where('nominal_simpanan', 500000)
            ->where('deskripsi_simpanan', 'Setoran tabungan anggota baru')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->struk_simpanan);
        $this->assertStringContainsString('struk-deposit-', $transaction->struk_simpanan);
        $this->assertStringEndsWith('.pdf', $transaction->struk_simpanan);
    });

    it('Bukti transaksi berupa file PDF dihasilkan setelah transaksi penarikan simpanan berhasil dicatat', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('transaksi_simpanan', [
            'nominal_simpanan' => 100000,
            'tipe_transaksi' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);

        $transaction = TransaksiSimpanan::where('nominal_simpanan', 100000)
            ->where('tipe_transaksi', TransactionTypeEnum::WITHDRAWAL->value)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->struk_simpanan);
        $this->assertStringContainsString('struk-withdrawal-', $transaction->struk_simpanan);
        $this->assertStringEndsWith('.pdf', $transaction->struk_simpanan);
    });

    // negatif
    it('Bukti transaksi tidak dihasilkan jika pencatatan transaksi setoran simpanan gagal karena data tidak valid', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $anggota->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => -500000, // Nominal tidak valid
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('transaksi_simpanan', [
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);
    });

    it('Bukti transaksi tidak dihasilkan jika pencatatan transaksi penarikan simpanan gagal karena data tidak valid', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $anggota = Anggota::factory()->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'anggota_id' => $anggota->id,
                'akun_simpanan_id' => $akunSimpanan->id,
                'amount' => -100000, // Nominal tidak valid
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('transaksi_simpanan', [
            'tipe_transaksi' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);
    });
});

describe('Aplikasi harus menyediakan detail transaksi simpanan.', function () {
    it('DPS, Pengawas, Ketua, Bendahara, dan PJ Anggota dapat mengakses halaman detail transaksi simpanan anggota', function () {
        $pjanggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->syncRoles('Penanggung Jawab Anggota');
        $dps = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $dps->syncRoles('Dewan Pengawas Syariah');
        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->syncRoles('Ketua');
        $pengawas = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pengawas->syncRoles('Pengawas');
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $anggota = Anggota::factory([
            'pj_anggota_id' => $pjanggota->id,
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        $transaction = TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $akunSimpanan->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $res = $this->actingAs($pjanggota)
            ->get("/admin/savings/show/{$transaction->id}");
        $resDPS = $this->actingAs($dps)
            ->get("/admin/savings/show/{$transaction->id}");
        $resKetua = $this->actingAs($ketua)
            ->get("/admin/savings/show/{$transaction->id}");
        $resPengawas = $this->actingAs($pengawas)
            ->get("/admin/savings/show/{$transaction->id}");
        $resBendahara = $this->actingAs($bendahara)
            ->get("/admin/savings/show/{$transaction->id}");

        $resDPS->assertStatus(200);
        $resDPS->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Savings/Show')
            ->has('data')
        );

        $resKetua->assertStatus(200);
        $resKetua->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Savings/Show')
            ->has('data')
        );

        $resPengawas->assertStatus(200);
        $resPengawas->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Savings/Show')
            ->has('data')
        );

        $resBendahara->assertStatus(200);
        $resBendahara->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Savings/Show')
            ->has('data')
        );

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Savings/Show')
            ->has('data')
        );
    });

    it('PJ Anggota tidak dapat mengakses detail transaksi simpanan anggota lain yang bukan tanggung jawabnya', function () {
        $pjanggota1 = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota1->syncRoles('Penanggung Jawab Anggota');
        $member1 = Anggota::factory([
            'pj_anggota_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $member1->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 500000,
        ]);

        $transaction = TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $akunSimpanan->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran pokok anggota baru',
        ]);

        $pjanggota2 = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota2->syncRoles('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->get("/admin/savings/show/{$transaction->id}");

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan daftar transaksi terbaru anggota koperasi.', function () {
    it('Pengurus terkait dapat mengakses halaman daftar transaksi simpanan anggota', function () {
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->syncRoles('Ketua');

        $DPS = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $DPS->syncRoles('Dewan Pengawas Syariah');

        $pengawas = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pengawas->syncRoles('Pengawas');

        $responseBendahara = $this->actingAs($bendahara)->get('/admin/savings');
        $responseBendahara->assertStatus(200);

        $responseKetua = $this->actingAs($ketua)->get('/admin/savings');
        $responseKetua->assertStatus(200);

        $responseDPS = $this->actingAs($DPS)->get('/admin/savings');
        $responseDPS->assertStatus(200);

        $responsePengawas = $this->actingAs($pengawas)->get('/admin/savings');
        $responsePengawas->assertStatus(200);
    });

    it('Anggota dicegah mengakses halaman pengelolaan data simpanan', function () {
        $anggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $anggota->syncRoles('Anggota');

        $response = $this->actingAs($anggota)->get('/admin/savings');
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan riwayat transaksi simpanan dan pergerakan saldo setiap produk simpanan berupa buku tabungan untuk anggota.', function () {
    it('Anggota dapat mengakses halaman riwayat transaksi simpanan dan pergerakan saldo tabungan pribadi', function () {
        $anggota = Anggota::factory()->create();
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $sa = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $sa->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $res = $this->actingAs($user)
            ->get('/user/tabungan');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) => $page
            ->component('User/Tabungan/List')
            ->has('transactions')
        );
    });

    it('Selain anggota, pengguna lain tidak dapat mengakses halaman riwayat transaksi simpanan dan pergerakan saldo tabungan pribadi', function () {
        $anggota = Anggota::factory()->create();
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $sa = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $sa->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $otherUser = Pengguna::factory()->create();
        $otherUser->syncRoles('Penanggung Jawab Anggota');

        $res = $this->actingAs($otherUser)
            ->get('/user/tabungan');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan ekspor tabungan pribadi untuk anggota.', function () {
    it('Anggota dapat mengekspor tabungan pribadi dalam format PDF', function () {
        $anggota = Anggota::factory()->create();
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $sa = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $sa->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $res = $this->actingAs($user)
            ->get('/user/tabungan/export');

        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'application/pdf');
    });

    it('Selain anggota, pengguna lain tidak dapat mengekspor tabungan pribadi anggota lain', function () {
        $anggota = Anggota::factory()->create();
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $sa = AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 500000,
        ]);

        TransaksiSimpanan::factory()->create([
            'akun_simpanan_id' => $sa->id,
            'nominal_simpanan' => 500000,
            'deskripsi_simpanan' => 'Setoran tabungan anggota baru',
        ]);

        $otherUser = Pengguna::factory()->create();
        $otherUser->syncRoles('Penanggung Jawab Anggota');

        $res = $this->actingAs($otherUser)
            ->get('/user/tabungan/export');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus dapat menghitung poin simpanan anggota berdasarkan total saldo simpanan yang dimiliki pada bulan berjalan secara otomatis.', function () {
    it('menghitung poin simpanan anggota berdasarkan total saldo bulan berjalan secara otomatis', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'saldo' => 2000000,
        ]);

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'saldo' => 3000000,
        ]);

        $this->travelTo(now()->endOfMonth());

        $this->artisan('points:calculate-monthly-savings')
            ->assertSuccessful();

        $this->assertDatabaseHas('poin', [
            'pengguna_id' => $user->id,
            'jml_perolehan' => 50, // 1 poin per 100.000 saldo, total saldo 5.000.000 = 50 poin
        ]);

        $this->travelBack();
    });

    it('Tidak menghitung poin simpanan untuk anggota yang memiliki saldo simpanan kurang dari threshold', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $threshold = GlobalSetting::where('key', 'saving_point_amount')->first()->value;
        $dummyBalance = ($threshold - 10000); // Saldo di bawah threshold

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'saldo' => $dummyBalance,
        ]);

        $this->travelTo(now()->endOfMonth());

        $this->artisan('points:calculate-monthly-savings')
            ->assertSuccessful();

        $this->assertDatabaseMissing('poin', [
            'pengguna_id' => $user->id,
        ]);

        $this->travelBack();
    });
});

describe('Sistem mengirimkan notifikasi H-7 sebelum jatuh tempo pembayaran simpanan', function () {
    it('Sistem mengirimkan notifikasi H-7 sebelum jatuh tempo', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);
        
        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 0,
        ]);

        // Simulasikan waktu ke H-7 sebelum akhir bulan (jatuh tempo simpanan wajib)
        $this->travelTo(now()->endOfMonth()->subDays(7)->startOfDay());

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'anggota_id' => $anggota->id,
            'notification_type' => NotificationTypeEnum::MANDATORY_SAVING->value,
            'reminder_type' => NotificationReminderTypeEnum::H_7->value,
            'status' => NotificationStatusEnum::SENT->value,
        ]);

        $this->travelBack();
    });
});