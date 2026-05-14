<?php

use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('FR-12 Aplikasi harus menyediakan pencatatan transaksi simpanan anggota oleh penanggung jawab.', function () {

    it('PJA dapat mencatat transaksi penyetoran simpanan anggota dengan data yang valid', function () {
        $pjanggota = User::factory(['status' => UserStatusEnum::ACTIVE->value])->create();
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


});

// describe('TC-14: Hak Akses, Saldo, dan Ledger Simpanan', function () {

//     it('[REQ-F-36] PJA dapat mengelola simpanan, sedangkan Anggota/Role lain ditolak', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');

//         $anggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $anggota->assignRole('Anggota');

//         $member = Member::factory()->create();

//         // PJA Berhasil
//         $responsePJA = $this->actingAs($pjanggota)
//             ->post('/admin/saving/deposit', [
//                 'member_id' => $member->id,
//                 'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//                 'amount' => 500000,
//                 'date' => now()->format('Y-m-d'),
//                 'saving_payment_method' => 'Tunai',
//             ]);
//         $responsePJA->assertStatus(302);

//         // Anggota Ditolak (403 Forbidden)
//         $responseAnggota = $this->actingAs($anggota)
//             ->post('/admin/saving/deposit', [
//                 'user_id' => $member->user_id,
//                 'member_id' => $member->id,
//                 'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//                 'saving_payment_method' => 'Tunai',
//                 'amount' => 500000,
//                 'date' => now()->format('Y-m-d'),
//             ]);
//         $responseAnggota->assertStatus(403);
//     });

//     it('[REQ-F-37] Anggota dicegah mengakses halaman pengelolaan data simpanan', function () {
//         $anggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $anggota->assignRole('Anggota');

//         $response = $this->actingAs($anggota)->get('/admin/saving/deposit');
//         $response->assertStatus(403);
//     });

//     it('[REQ-F-39, REQ-F-43] Anggota dapat melihat saldo dan riwayat (ledger) pribadi', function () {
//         $member = Member::factory()->create();
//         $user = User::where('id', $member->user_id)->first();
//         $user->assignRole('Anggota');

//         $simpananPokok = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_POKOK->value)->first();

//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $simpananPokok->id,
//             'balance' => 500000,
//         ]);

//         SavingTransaction::factory()->create([
//             'saving_account_id' => $savingAccount->id,
//         ]);

//         // Cek Saldo & Database
//         $this->assertDatabaseHas('saving_accounts', [
//             'member_id' => $member->id,
//             'balance' => 500000,
//         ]);

//         // Cek Ledger
//         $responseLedger = $this->actingAs($user)->get('/user/ledger');
//         $responseLedger->assertStatus(200);
//     });
// });

// describe('TC-15: Proses Pencatatan Transaksi & Batas Saldo', function () {

//     it('[REQ-F-38] Hanya anggota berstatus Aktif yang dapat memiliki transaksi simpanan', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');

//         $inactiveMember = Member::factory()->create();
//         $inactiveMember->user->update(['status' => UserStatusEnum::INACTIVE->value]);

//         $response = $this->actingAs($pjanggota)
//             ->post('/admin/saving/deposit', [
//                 'member_id' => $inactiveMember->user_id,
//                 'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//                 'amount' => 500000,
//                 'date' => now()->format('Y-m-d'),
//             ]);

//         $response->assertSessionHasErrors();
//     });

//     it('[REQ-F-41, REQ-F-42] Transaksi dicatat lengkap beserta notes dan dapat dicetak struknya', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         $response = $this->actingAs($pjanggota)
//             ->post('/admin/saving/deposit', [
//                 'member_id' => $member->id,
//                 'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//                 'amount' => 500000,
//                 'date' => now()->format('Y-m-d'),
//                 'saving_payment_method' => 'Tunai',
//                 'notes' => 'Setoran pokok anggota baru',
//             ]);

//         $response->assertStatus(302);
//         $this->assertDatabaseHas('saving_transactions', [
//             'saving_amount' => 500000,
//             'saving_description' => 'Setoran pokok anggota baru',
//         ]);
//     });

//     it('[REQ-F-50] Nominal penarikan tidak boleh melebihi saldo tabungan', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
//             'balance' => 200000, // Saldo hanya 200rb
//         ]);

//         $response = $this->actingAs($pjanggota)
//             ->post('/admin/saving/withdrawal', [
//                 'member_id' => $member->id,
//                 'saving_account_id' => $savingAccount->id,
//                 'amount' => 500000, // Ditarik 500rb
//                 'withdrawal_date' => now()->format('Y-m-d'),
//                 'method' => 'Tunai',
//             ]);

//         $response->assertSessionHasErrors('amount');
//     });
// });

// describe('TC-16: Aturan Simpanan Pokok dan Wajib', function () {

//     it('[REQ-F-44] Simpanan Pokok hanya dapat dicatat 1 kali (menolak duplikasi)', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         // Setoran Pertama Berhasil
//         $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'member_id' => $member->id,
//             'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//             'amount' => 500000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//         ])->assertStatus(302);

//         // Setoran Kedua Ditolak
//         $response2 = $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'member_id' => $member->user_id, // As per original code
//             'saving_category' => SavingTypeEnum::SIMPANAN_POKOK->value,
//             'amount' => 500000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//         ]);
//         $response2->assertSessionHasErrors();
//     });

//     it('[REQ-F-45, REQ-F-46] Simpanan Pokok tidak bisa ditarik saat Aktif, tapi bisa setelah Pengunduran Diri disetujui', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);

//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_POKOK->value)->first();
//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 500000,
//         ]);

//         // Coba Tarik Saat Aktif (Gagal)
//         $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $savingAccount->id,
//             'amount' => 500000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ])->assertSessionHasErrors();

//         // Update status ke resign
//         $member->update(['status' => 'Pengunduran Diri Disetujui']);

//         // Tarik Saat Resign (Berhasil)
//         $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $savingAccount->id,
//             'amount' => 500000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ])->assertStatus(302);
//     });

//     it('[REQ-F-47, REQ-F-49] Mencatat setoran rutin Simpanan Wajib dan blokir penarikannya saat aktif', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         // Setoran Sukses
//         $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'member_id' => $member->id,
//             'saving_category' => SavingTypeEnum::SIMPANAN_WAJIB->value,
//             'amount' => 100000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//         ])->assertStatus(302);

//         // Penarikan Diblokir
//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::SIMPANAN_WAJIB->value)->first();
//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 500000,
//         ]);

//         $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $savingAccount->id,
//             'amount' => 100000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ])->assertSessionHasErrors();
//     });
// });

// describe('TC-17: Aturan Tabungan Berjangka', function () {

//     it('[REQ-F-51] Wajib menyertakan tujuan dan memilih tenor', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         $response = $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'member_id' => $member->user_id,
//             'saving_category' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
//             'amount' => 1000000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//             'tenor_months' => 12,
//         ]);
//         $response->assertStatus(302);
//     });

//     it('[REQ-F-52] Nonaktif penarikan sebelum waktu jatuh tempo', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_BERJANGKA->value)->first();
//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 1000000,
//             'saving_tenor' => 12,
//             'created_at' => now(), // Baru dibuat, belum jatuh tempo
//         ]);

//         $response = $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $savingAccount->id,
//             'amount' => 1000000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ]);
//         $response->assertSessionHasErrors('saving_account_id');
//     });
// });

// describe('TC-18: Siklus Tabungan Ibadah', function () {

//     it('[REQ-F-53] Wajib menetapkan target nominal saat pembuatan', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();

//         $response = $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'user_id' => $member->user_id,
//             'member_id' => $member->user_id,
//             'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
//             'amount' => 500000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//             'target_amount' => 5000000,
//         ]);
//         $response->assertStatus(302);
//     });

//     it('[REQ-F-54, REQ-F-55] Tarik bebas dinonaktifkan sebelum target, diizinkan setelah target tercapai', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();
//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

//         // Skenario 1: Belum Capai Target
//         $accBelumTarget = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 2000000,
//             'target_amount' => 5000000,
//         ]);
//         $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $accBelumTarget->id,
//             'amount' => 2000000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ])->assertSessionHasErrors('saving_account_id');

//         // Skenario 2: Sudah Capai Target
//         $accSudahTarget = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 5000000,
//             'target_amount' => 5000000,
//         ]);
//         $this->actingAs($pjanggota)->post('/admin/saving/withdrawal', [
//             'member_id' => $member->id,
//             'saving_account_id' => $accSudahTarget->id,
//             'amount' => 5000000,
//             'withdrawal_date' => now()->format('Y-m-d'),
//             'method' => 'Tunai',
//         ])->assertStatus(302);
//     });

//     it('[REQ-F-56] Menghentikan setoran tambahan pada rekening yang sudah mencapai target', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();
//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

//         $savingAccount = SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 5000000,
//             'target_amount' => 5000000,
//         ]);

//         $response = $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'member_id' => $member->id,
//             'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
//             'amount' => 100000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//         ]);

//         $response->assertSessionHasErrors(['target_reached']);
//     });

//     it('[REQ-F-57] Wajib mencairkan dana target sebelum membuka rekening ibadah baru', function () {
//         $pjanggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
//         $pjanggota->assignRole('Penanggung Jawab Anggota');
//         $member = Member::factory()->create();
//         $savingProduct = SavingProduct::where('name', SavingTypeEnum::TABUNGAN_IBADAH->value)->first();

//         SavingAccount::factory()->create([
//             'member_id' => $member->id,
//             'saving_product_id' => $savingProduct->id,
//             'balance' => 5000000,
//             'target_amount' => 5000000,
//         ]);

//         $response = $this->actingAs($pjanggota)->post('/admin/saving/deposit', [
//             'user_id' => $member->user_id,
//             'saving_category' => SavingTypeEnum::TABUNGAN_IBADAH->value,
//             'amount' => 1000000,
//             'date' => now()->format('Y-m-d'),
//             'saving_payment_method' => 'Tunai',
//             'target_amount' => 10000000,
//         ]);

//         $response->assertSessionHasErrors(['existing_ibadah']);
//     });
// });
