<?php

use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\IbadahAccount;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('FR-12 Aplikasi harus menyediakan pencatatan transaksi simpanan anggota oleh penanggung jawab.', function () {
    it('PJ dapat mencatat transaksi penyetoran simpanan anggota dengan data yang valid', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

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

    it('PJ tidak dapat memproses transaksi simpanan pokok untuk anggota yang berstatus aktif', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'status' => 'Aktif'
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
            'saving_category' => 'Simpanan Pokok hanya untuk anggota dengan status Menunggu Pembayaran.'
        ]);
    });

    it('PJ tidak dapat memproses transaksi simpanan pokok lebih dari satu kali', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory([
            'status' => 'Menunggu Pembayaran'
        ])->create();

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'balance' => 500000,
        ]);

        SavingTransaction::factory()->create([
            'saving_account_id' => $sa->id,
            'saving_amount' => 500000,
            'saving_description' => 'Setoran pokok anggota baru',
        ]);

        $member->update([
            'status' => 'Menunggu Pembayaran'
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran pokok anggota lagi',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.'
        ]);
    });

    it('Transaksi tabungan ibadah yang sudah mencapai target tidak bisa menerima setoran tambahan', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
        $pjanggota->assignRole('Penanggung Jawab Anggota');
        $member = Member::factory()->create();

        $sa = SavingAccount::factory()->create([
            'member_id' => $member->id,
            'saving_type' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'balance' => 5000000,
        ]);

        $ia = IbadahAccount::create([
            'tenor' => 12,
            'target_amount' => 5000000,
            'saving_account_id' => $sa->id,
        ]);

        $res = $this->actingAs($pjanggota)
            ->post('/admin/savings/deposit', [
                'member_id' => $member->id,
                'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
                'amount' => 100000,
                'tenor_months' => $ia->tenor,
                'target_amount' => $ia->target_amount,
                'date' => now()->format('Y-m-d'),
                'saving_payment_method' => 'Tunai',
                'notes' => 'Setoran tambahan tabungan ibadah',
            ]);

        $res->assertSessionHasErrors([
            'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.'
        ]);
    });
});

test('FR-13 Aplikasi harus menyediakan detail transaksi simpanan beserta perolehan poinnya.', function () {
    $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
    $pjanggota->assignRole('Penanggung Jawab Anggota');
    $member = Member::factory()->create();

    $sa = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_type' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
        'balance' => 500000,
    ]);

    $st = SavingTransaction::factory()->create([
        'saving_account_id' => $sa->id,
        'saving_amount' => 500000,
        'saving_description' => 'Setoran tabungan anggota baru',
    ]);

    $res = $this->actingAs($pjanggota)
        ->get("/admin/savings/show/{$st->id}");

    $res->assertStatus(200);
    $res->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Admin/Savings/Show')
        ->has('data')
    );
});

test('FR-14 Aplikasi harus menyediakan riwayat transaksi simpanan dan pergerakan saldo setiap produk simpanan berupa ledger pribadi untuk anggota.', function () {
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
        ->get('/user/ledger');

    $res->assertStatus(200);
});

test('FR-15 Aplikasi harus menyediakan ekspor ledger pribadi untuk anggota.', function () {
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
        ->get('/user/ledger/export');

    $res->assertStatus(200);
    $res->assertHeader('Content-Type', 'application/pdf');
});

describe('FR-16 Aplikasi harus menyediakan daftar transaksi terbaru anggota koperasi untuk sekretaris dan ketua koperasi.', function () {
    it('Sekretaris dan Ketua dapat mengakses halaman daftar transaksi simpanan anggota', function () {
        $sekretaris = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $sekretaris->assignRole('Sekretaris');

        $ketua = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');

        $responseSekretaris = $this->actingAs($sekretaris)->get('/admin/savings/list');
        $responseSekretaris->assertStatus(200);

        $responseKetua = $this->actingAs($ketua)->get('/admin/savings/list');
        $responseKetua->assertStatus(200);
    });

    it('Anggota dicegah mengakses halaman pengelolaan data simpanan', function () {
        $anggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get('/admin/savings/list');
        $response->assertStatus(403);
    });
});

test('FR-18 Aplikasi harus dapat menghitung poin simpanan anggota berdasarkan total saldo simpanan yang dimiliki pada bulan berjalan secara otomatis.', function () {
    $member = Member::factory()->create();
    $user = User::where('id', $member->user_id)->first();
    $user->assignRole('Anggota');
    $pj = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
    $pj->assignRole('Penanggung Jawab Anggota');

    $res = $this->actingAs($pj)
    ->post('/admin/savings/deposit', [
        'member_id' => $member->id,
        'saving_category' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
        'amount' => 500000,
        'date' => now()->format('Y-m-d'),
        'saving_payment_method' => 'Tunai',
        'notes' => 'Setoran tabungan anggota baru',
    ]);

    $this->assertDatabaseHas('point_transactions', [
        'amount_earned' => 5,
        'activity_description' => 'Mendapatkan 5 poin dari transaksi sebesar 500000.00',
        'user_id' => $user->id,
    ]);
});
