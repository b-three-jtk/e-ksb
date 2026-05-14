<?php

use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingProduct;
use App\Models\SavingTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SavingProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(SavingProductSeeder::class);
});

// FR 1
test('Aplikasi harus memungkinkan hanya pengguna dengan peran Penanggung Jawab Anggota yang dapat mengelola seluruh data dan transaksi simpanan anggota', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response->assertStatus(302);
});

test('Aplikasi harus mencegah pengguna non-Penanggung Jawab Anggota mengelola data simpanan', function () {
    $anggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $anggota->assignRole('Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($anggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
        ]);

    $response->assertStatus(403);
});

// FR 2
test('Aplikasi harus mencegah anggota untuk melakukan perubahan atau pengelolaan data simpanan terhadap data simpanan mereka sendiri maupun anggota lain', function () {
    $anggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $anggota->assignRole('Anggota');

    $member = Member::factory()->create();

    // Anggota tidak bisa akses halaman deposit
    $response = $this->actingAs($anggota)
        ->get('/admin/saving/deposit');

    $response->assertStatus(403);
});

// FR 3
test('Aplikasi harus memastikan bahwa hanya anggota koperasi yang terdaftar dan berstatus Aktif yang dapat memiliki produk simpanan', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $inactiveMember = Member::factory()->create();
    $inactiveMember->user->update(['status' => UserStatusEnum::INACTIVE->value]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $inactiveMember->user_id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
        ]);

    $response->assertSessionHasErrors();
});

// FR 4, 5, 8
test('Aplikasi harus memungkinkan anggota untuk melihat saldo masing-masing produk simpanan yang mereka miliki', function () {
    $member = Member::factory()->create();
    $user = User::where('id', $member->user_id)->first();
    $user->assignRole('Anggota');

    $simpananPokok = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_POKOK->value)->first();
    $simpananWajib = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_WAJIB->value)->first();
    $tabunganAnggota = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_ANGGOTA->value)->first();
    $tabunganBerjangka = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_BERJANGKA->value)->first();
    $tabunganIbadah = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $simpananPokok->id,
        'balance' => 500000,
    ]);
    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $simpananWajib->id,
        'balance' => 300000,
    ]);
    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $tabunganAnggota->id,
        'balance' => 200000,
    ]);
    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $tabunganBerjangka->id,
        'balance' => 1000000,
    ]);
    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $tabunganIbadah->id,
        'balance' => 5000000,
    ]);

    // check bukan di ledger tapi di db
    $this->assertDatabaseHas('saving_accounts', [
        'member_id' => $member->id,
        'saving_product_id' => $simpananPokok->id,
        'balance' => 500000,
    ]);
    $this->assertDatabaseHas('saving_accounts', [
        'member_id' => $member->id,
        'saving_product_id' => $simpananWajib->id,
        'balance' => 300000,
    ]);
    $this->assertDatabaseHas('saving_accounts', [
        'member_id' => $member->id,
        'saving_product_id' => $tabunganAnggota->id,
        'balance' => 200000,
    ]);
    $this->assertDatabaseHas('saving_accounts', [
        'member_id' => $member->id,
        'saving_product_id' => $tabunganBerjangka->id,
        'balance' => 1000000,
    ]);
    $this->assertDatabaseHas('saving_accounts', [
        'member_id' => $member->id,
        'saving_product_id' => $tabunganIbadah->id,
        'balance' => 5000000,
    ]);
});

test('Aplikasi harus menyediakan riwayat transaksi berupa ledger pribadi untuk anggota', function () {
    $member = Member::factory()->create();
    $user = User::where('id', $member->user_id)->first();
    $user->assignRole('Anggota');

    SavingTransaction::factory()->create([
        'saving_account_id' => SavingAccount::factory()->create([
            'member_id' => $member->id,
        ])->id,
    ]);

    $response = $this->actingAs($user)
        ->get('/user/ledger');

    $response->assertStatus(200);
});

// FR 6, 7
test('Aplikasi harus memastikan bahwa setiap transaksi simpanan dicatat dengan lengkap oleh Penanggung Jawab Anggota', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
            'notes' => 'Setoran pokok anggota baru',
        ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('saving_transactions', [
        'saving_amount' => 500000,
        'saving_description' => 'Setoran pokok anggota baru',
    ]);
});

test('Aplikasi harus menghasilkan bukti transaksi untuk setiap setoran dan penarikan', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('saving_transactions', [
        'saving_amount' => 500000,
    ]);
});

// FR 9, 10, 11
test('Aplikasi harus memastikan pencatatan setoran Simpanan Pokok hanya dapat dilakukan satu kali pada saat pendaftaran anggota baru', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    // First deposit
    $response1 = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response1->assertStatus(302);

    // Second deposit should be rejected
    $response2 = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->user_id,
            'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response2->assertSessionHasErrors();
});

test('Aplikasi harus menonaktifkan penarikan Simpanan Pokok selama status keanggotaan masih Aktif', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();
    $savingProduct = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_POKOK->value)->first();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 500000,
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 500000,
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors();
});

test('Aplikasi harus mengizinkan pengembalian Simpanan Pokok kepada anggota hanya setelah pengunduran diri disetujui', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_POKOK->value)->first();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 500000,
    ]);

    // Update member status to resigned
    $member->update(['status' => 'Pengunduran Diri Disetujui']);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 500000,
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertStatus(302);
});

// FR 12, 13
test('Aplikasi harus mendukung pencatatan setoran Simpanan Wajib secara rutin sesuai periode yang telah ditetapkan', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'amount' => 100000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response->assertStatus(302);
    $this->assertDatabaseHas('saving_transactions', [
        'saving_amount' => 100000,
    ]);
});

test('Aplikasi harus menonaktifkan penarikan Simpanan Wajib selama anggota masih berstatus aktif', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_WAJIB->value)->first();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 500000,
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 100000,
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors();
});

// FR 14
test('Aplikasi harus memastikan bahwa nominal penarikan tabungan anggota tidak melebihi saldo tabungan yang tersedia', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
        'balance' => 200000,
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 500000, // Lebih dari saldo
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors('amount');
});

// FR 15, 16
test('Aplikasi harus mewajibkan anggota untuk menyertakan tujuan tabungan berjangka dan memilih tenor', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->user_id,
            'saving_category' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'amount' => 1000000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
            'tenor_months' => 12,
        ]);

    $response->assertStatus(302);
});

test('Aplikasi harus menonaktifkan penarikan dana Tabungan Berjangka sebelum jatuh tempo', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_BERJANGKA->value)->first();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 1000000,
        'saving_tenor' => 12,
        'created_at' => now(), // Baru dibuat
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 1000000,
            'withdrawal_date' => now()->format('Y-m-d'), // Belum jatuh tempo
            'method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors('saving_account_id');
});

// FR 17, 18, 19, 20, 21
test('Aplikasi harus mewajibkan anggota untuk menyertakan tujuan tabungan ibadah dan menetapkan target nominal', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $member = Member::factory()->create();

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->user_id,
            'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'amount' => 500000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
            'target_amount' => 5000000,
        ]);

    $response->assertStatus(302);
});

test('Aplikasi harus menonaktifkan penarikan dana Tabungan Ibadah secara bebas sebelum target nominal tercapai', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

    $member = Member::factory()->create();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 2000000,
        'target_amount' => 5000000,
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 2000000,
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors('saving_account_id');
});

test('Aplikasi harus mengizinkan pencairan dana Tabungan Ibadah hanya setelah target nominal tercapai', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

    $member = Member::factory()->create();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 5000000,
        'target_amount' => 5000000, // Target sudah tercapai
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/withdrawal', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => 5000000,
            'withdrawal_date' => now()->format('Y-m-d'),
            'method' => 'Tunai',
        ]);

    $response->assertStatus(302);
});

test('Aplikasi harus menghentikan penerimaan setoran tambahan pada rekening Tabungan Ibadah yang sudah mencapai target', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

    $member = Member::factory()->create();

    $savingAccount = SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 5000000,
        'target_amount' => 5000000, // Target sudah tercapai
    ]);

    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'member_id' => $member->id,
            'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'amount' => 100000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
        ]);

    $response->assertSessionHasErrors([
        'target_reached' => 'Tabungan Ibadah sudah mencapai target, tidak bisa melakukan penyetoran.',
    ]);
});

test('Aplikasi harus mewajibkan anggota mencairkan seluruh dana Tabungan Ibadah sebelum membuka yang baru', function () {
    $pjanggota = User::factory()->create([
        'status' => UserStatusEnum::ACTIVE->value,
    ]);
    $pjanggota->assignRole('Penanggung Jawab Anggota');

    $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

    $member = Member::factory()->create();

    SavingAccount::factory()->create([
        'member_id' => $member->id,
        'saving_product_id' => $savingProduct->id,
        'balance' => 5000000,
        'target_amount' => 5000000,
    ]);

    // Try to open another Tabungan Ibadah
    $response = $this->actingAs($pjanggota)
        ->post('/admin/saving/deposit', [
            'user_id' => $member->user_id,
            'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
            'amount' => 1000000,
            'date' => now()->format('Y-m-d'),
            'saving_payment_method' => 'Tunai',
            'target_amount' => 10000000,
        ]);

    $response->assertSessionHasErrors([
        'existing_ibadah' => 'Anda harus mencairkan Tabungan Ibadah yang lama sebelum membuka yang baru.',
    ]);
});
