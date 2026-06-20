<?php

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\BerjangkaAccount;
use App\Models\GlobalSetting;
use App\Models\IbadahAccount;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
});

describe('Aplikasi harus menyediakan pencatatan transaksi penyetoran simpanan anggota oleh penanggung jawab.', function () {
    it('PJ dapat mencatat transaksi penyetoran simpanan anggota dengan data yang valid', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('saving_transactions', [
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penyetoran simpanan pokok lebih dari satu kali untuk anggota yang sama', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        // Simpanan pokok pertama
        $response1 = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran pokok anggota baru',
            ]);

        $response1->assertSessionHasNoErrors();

        $member->update([
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ]);
        Log::info('Member status after first deposit: ' . $member->status);

        // Simpanan pokok kedua
        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran pokok anggota kedua',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.'
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penyetoran simpanan pokok untuk selain anggota tanggung jawabnya', function () {
        $pjanggota1 = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota1->assignRole('Penanggung Jawab Anggota');
        $member1 = Member::factory([
            'pj_user_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $pjanggota2 = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota2->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->post('/admin/savings/deposit', [
                'member_id' => $member1->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran pokok anggota oleh PJ lain',
            ]);

            $res->assertStatus(403);
    });

    it('Transaksi tabungan ibadah yang sudah mencapai target tidak bisa menerima setoran tambahan', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $user = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $user->assignRole('Anggota');
        $member = Member::factory([
            'user_id' => $user->id,
            'status' => 'Aktif',
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'balance' => 5000000,
        ]);

        $ia = IbadahAccount::create([
            'target_amount' => 5000000,
            'purpose' => 'Tabungan untuk Haji 2026',
            'saving_account_id' => $sa->id,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_account_id' => $sa->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
                'amount' => 100000,
                'target_amount' => $ia->target_amount,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'purpose' => 'Tabungan untuk Haji 2026',
                'notes' => 'Setoran tambahan tabungan ibadah',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.'
        ]);
    });

    it('PJ tidak dapat memproses penyetoran simpanan pokok untuk anggota yang berstatus aktif', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'status' => 'Aktif',
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran pokok anggota baru',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Simpanan Pokok hanya untuk anggota Menunggu Pembayaran.'
        ]);
    });
});

describe('Aplikasi harus menyediakan pencatatan transaksi penarikan simpanan anggota oleh penanggung jawab.', function () {
    it('PJ dapat mencatat transaksi penarikan simpanan anggota dengan data yang valid', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('saving_transactions', [
            'saving_amount' => 100000,
            'transaction_type' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);
    });

    it('Nominal penarikan tidak boleh melebihi saldo tabungan', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => 'Tabungan Anggota',
            'balance' => 200000, // Saldo hanya 200rb
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
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
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $waktuBuat = now();
        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'balance' => 1000000,
            'created_at' => $waktuBuat,
        ]);

        $tenorBulan = 6;
        BerjangkaAccount::create([
            'saving_account_id' => $savingAccount->id,
            'tenor' => $tenorBulan,
            'purpose' => 'Tabungan Berjangka 6 bulan',
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 500000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $expectedMaturityDate = $waktuBuat->copy()->addMonths($tenorBulan)->startOfDay();
        $expectedMessage = 'Tabungan berjangka belum jatuh tempo. Pencairan dapat dilakukan mulai ' . $expectedMaturityDate->format('d/m/Y');

        $response->assertSessionHasErrors([
            'saving_account_id' => $expectedMessage
        ]);
    });

    it('Simpanan Pokok tidak dapat ditarik oleh Anggota Koperasi selama status keanggotaannya masih aktif.', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'balance' => 500000,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'saving_account_id' => 'Simpanan Pokok tidak dapat ditarik selama status keanggotaan masih aktif.'
        ]);
    });

    it('Simpanan Wajib tidak dapat ditarik oleh Anggota Koperasi selama status keanggotaannya masih aktif.', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'balance' => 500000,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'saving_account_id' => 'Simpanan Wajib tidak dapat ditarik selama status keanggotaan masih aktif.'
        ]);
    });

    it('Dana Tabungan Ibadah tidak dapat dicairkan sebelum target nominal tercapai.', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'balance' => 4000000,
        ]);

        IbadahAccount::create([
            'target_amount' => 5000000,
            'purpose' => 'Tabungan untuk Haji 2026',
            'saving_account_id' => $savingAccount->id,
        ]);

        $response = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 1000000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $response->assertSessionHasErrors([
            'saving_account_id' => 'Tabungan ibadah belum mencapai target minimal Rp 5.000.000'
        ]);
    });

    it('PJ tidak dapat mencatat transaksi penarikan simpanan pokok untuk selain anggota tanggung jawabnya', function () {
        $pjanggota1 = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota1->assignRole('Penanggung Jawab Anggota');
        $member1 = Member::factory([
            'pj_user_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member1->id,
            'saving_type' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'balance' => 500000,
        ]);

        $pjanggota2 = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota2->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member1->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

            $res->assertStatus(403);
    });
});

describe('Aplikasi harus menghasilkan bukti transaksi untuk setiap transaksi setoran dan penarikan simpanan oleh penanggung jawab anggota.', function () {
    it('Bukti transaksi berupa file PDF dihasilkan setelah transaksi setoran simpanan berhasil dicatat', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('saving_transactions', [
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);

        $transaction = SavingTransaction::where('saving_amount', 500000)
            ->where('saving_description', 'Setoran tabungan anggota baru')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->saving_transaction_receipt);
        $this->assertStringContainsString('struk-deposit-', $transaction->saving_transaction_receipt);
        $this->assertStringEndsWith('.pdf', $transaction->saving_transaction_receipt);
    });

    it('Bukti transaksi berupa file PDF dihasilkan setelah transaksi penarikan simpanan berhasil dicatat', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => 100000,
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('saving_transactions', [
            'saving_amount' => 100000,
            'transaction_type' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);

        $transaction = SavingTransaction::where('saving_amount', 100000)
            ->where('transaction_type', TransactionTypeEnum::WITHDRAWAL->value)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertNotNull($transaction->saving_transaction_receipt);
        $this->assertStringContainsString('struk-withdrawal-', $transaction->saving_transaction_receipt);
        $this->assertStringEndsWith('.pdf', $transaction->saving_transaction_receipt);
    });

    // negatif
    it('Bukti transaksi tidak dihasilkan jika pencatatan transaksi setoran simpanan gagal karena data tidak valid', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                'amount' => -500000, // Nominal tidak valid
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran tabungan anggota baru',
            ]);

        $res->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('saving_transactions', [
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);
    });

    it('Bukti transaksi tidak dihasilkan jika pencatatan transaksi penarikan simpanan gagal karena data tidak valid', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/withdrawal', [
                'member_id' => $member->id,
                'saving_account_id' => $savingAccount->id,
                'amount' => -100000, // Nominal tidak valid
                'withdrawal_date' => now()->format('Y-m-d'),
                'method' => 'Tunai',
            ]);

        $res->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('saving_transactions', [
            'transaction_type' => TransactionTypeEnum::WITHDRAWAL->value,
        ]);
    });
});

describe('Aplikasi harus menyediakan detail transaksi simpanan.', function () {
    it('DPS, Pengawas, Ketua, Bendahara, dan PJ Anggota dapat mengakses halaman detail transaksi simpanan anggota', function () {
        $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $dps = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $dps->assignRole('Dewan Pengawas Syariah');
        $ketua = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');
        $pengawas = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pengawas->assignRole('Pengawas');
        $bendahara = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->assignRole('Bendahara');

        $member = Member::factory([
            'pj_user_id' => $pjanggota->id,
        ])->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        $transaction = SavingTransaction::factory()->create([
            'saving_account_id' => $savingAccount->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
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
        $pjanggota1 = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota1->assignRole('Penanggung Jawab Anggota');
        $member1 = Member::factory([
            'pj_user_id' => $pjanggota1->id,
            'status' => MemberStatusEnum::PAYMENT_PENDING->value
        ])->create();

        $savingAccount = SavingAccount::factory()->create([
            'member_id' => $member1->id,
            'saving_type' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'balance' => 500000,
        ]);

        $transaction = SavingTransaction::factory()->create([
            'saving_account_id' => $savingAccount->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran pokok anggota baru',
        ]);

        $pjanggota2 = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjanggota2->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($pjanggota2)
            ->get("/admin/savings/show/{$transaction->id}");

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan daftar transaksi terbaru anggota koperasi.', function () {
    it('Pengurus terkait dapat mengakses halaman daftar transaksi simpanan anggota', function () {
        $bendahara = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->assignRole('Bendahara');

        $ketua = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');

        $DPS = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $DPS->assignRole('Dewan Pengawas Syariah');

        $pengawas = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pengawas->assignRole('Pengawas');

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
        $anggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get('/admin/savings');
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan riwayat transaksi simpanan dan pergerakan saldo setiap produk simpanan berupa buku tabungan untuk anggota.', function () {
    it('Anggota dapat mengakses halaman riwayat transaksi simpanan dan pergerakan saldo tabungan pribadi', function () {
        $member = Member::factory()->create();
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        SavingTransaction::factory()->create([
            'saving_account_id' => $sa->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
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
        $member = Member::factory()->create();
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        SavingTransaction::factory()->create([
            'saving_account_id' => $sa->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($otherUser)
            ->get('/user/tabungan');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan ekspor tabungan pribadi untuk anggota.', function () {
    it('Anggota dapat mengekspor tabungan pribadi dalam format PDF', function () {
        $member = Member::factory()->create();
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        SavingTransaction::factory()->create([
            'saving_account_id' => $sa->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);

        $res = $this->actingAs($user)
            ->get('/user/tabungan/export');

        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'application/pdf');
    });

    it('Selain anggota, pengguna lain tidak dapat mengekspor tabungan pribadi anggota lain', function () {
        $member = Member::factory()->create();
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'balance' => 500000,
        ]);

        SavingTransaction::factory()->create([
            'saving_account_id' => $sa->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran tabungan anggota baru',
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($otherUser)
            ->get('/user/tabungan/export');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus dapat menghitung poin simpanan anggota berdasarkan total saldo simpanan yang dimiliki pada bulan berjalan secara otomatis.', function () {
    it('menghitung poin simpanan anggota berdasarkan total saldo bulan berjalan secara otomatis', function () {
        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');
        $member = Member::factory([
            'user_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 2000000,
        ]);

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 3000000,
        ]);

        $this->travelTo(now()->endOfMonth());

        $this->artisan('points:calculate-monthly-savings')
            ->assertSuccessful();

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'amount_earned' => 50, // 1 poin per 100.000 saldo, total saldo 5.000.000 = 50 poin
        ]);

        $this->travelBack();
    });

    it('Tidak menghitung poin simpanan untuk anggota yang memiliki saldo simpanan kurang dari threshold', function () {
        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');
        $member = Member::factory([
            'user_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        $threshold = GlobalSetting::where('key', 'saving_point_amount')->first()->value;
        $dummyBalance = ($threshold - 10000); // Saldo di bawah threshold

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => $dummyBalance,
        ]);

        $this->travelTo(now()->endOfMonth());

        $this->artisan('points:calculate-monthly-savings')
            ->assertSuccessful();

        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $user->id,
        ]);

        $this->travelBack();
    });
});
