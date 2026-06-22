<?php

use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
});

describe('Aplikasi harus menyediakan pengelolaan akun koperasi oleh bendahara.', function () {
    it('Bendahara dapat menambahkan akun koperasi.', function () {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('Bendahara');

        $response = $this->actingAs($bendahara)->post('/admin/accounts/create', [
            'nama_akun' => 'Akun Baru',
            'nomor_akun' => '123',
            'jenis_akun' => 'Aset',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('accounts', [
            'account_name' => 'Akun Baru',
            'no_ref_account' => '123',
            'account_category' => 'Aset',
        ]);
    });

    it('Selain bendahara, pengguna lain tidak dapat menambahkan akun koperasi.', function () {
        $user = User::factory()->create();
        $user->assignRole('Pengawas');
        $response = $this->actingAs($user)->post('/admin/accounts/create', [
            'nama_akun' => 'Akun Baru',
            'nomor_akun' => '123',
            'jenis_akun' => 'Aset',
        ]);
        $response->assertStatus(403);
    });

    it('Bendahara dapat memperbarui status akun koperasi.', function () {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('Bendahara');

        $account = Account::factory()->create([
            'no_ref_account' => '123',
            'account_name' => 'Akun Lama',
            'account_category' => 'Aset',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($bendahara)->patch("/admin/accounts/{$account->no_ref_account}/status", [
            'status' => 'Non-Aktif',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('accounts', [
            'no_ref_account' => '123',
            'status' => 'Non-Aktif',
        ]);
    });
});

describe('Aplikasi harus menyediakan ekspor laporan arus kas dengan format excel bagi pengawas, DPS, ketua koperasi, dan bendahara.', function () {
    it('Pengawas dapat mengekspor laporan arus kas dengan format excel.', function () {
        $pengawas = User::factory()->create();
        $pengawas->assignRole('Pengawas');

        $response = $this->actingAs($pengawas)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('DPS dapat mengekspor laporan arus kas dengan format excel.', function () {
        $dps = User::factory()->create();
        $dps->assignRole('Dewan Pengawas Syariah');

        $response = $this->actingAs($dps)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('Ketua koperasi dapat mengekspor laporan arus kas dengan format excel.', function () {
        $ketua = User::factory()->create();
        $ketua->assignRole('Ketua');

        $response = $this->actingAs($ketua)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('Bendahara dapat mengekspor laporan arus kas dengan format excel.', function () {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('Bendahara');

        $response = $this->actingAs($bendahara)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});

describe('Aplikasi harus menyediakan pencatatan alokasi kas koperasi untuk setiap produk oleh bendahara.', function () {
    it('Bendahara dapat mencatat alokasi kas koperasi untuk setiap produk.', function () {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('Bendahara');

        $akunDebit = Account::factory()->create([
            'no_ref_account' => '111',
            'account_name' => 'Akun Debit',
            'account_category' => 'Aset',
            'status' => 'Aktif',
        ]);

        $akunKredit = Account::factory()->create([
            'no_ref_account' => '222',
            'account_name' => 'Akun Kredit',
            'account_category' => 'Liabilitas',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($bendahara)->post('/admin/kas/store', [
            'nominal' => 100000,
            'akun_debit' => $akunDebit->no_ref_account,
            'akun_kredit' => $akunKredit->no_ref_account,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('journal_entries', [
            'no_ref_account' => $akunDebit->no_ref_account,
            'position' => 'Debit',
            'nominal' => 100000.00,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'no_ref_account' => $akunKredit->no_ref_account,
            'position' => 'Credit',
            'nominal' => 100000.00,
        ]);
    });

    it('Selain bendahara, pengguna lain tidak dapat mencatat alokasi kas koperasi untuk setiap produk.', function () {
        $user = User::factory()->create();
        $user->assignRole('Pengawas');

        $akunDebit = Account::factory()->create([
            'no_ref_account' => '111',
            'account_name' => 'Akun Debit',
            'account_category' => 'Aset',
            'status' => 'Aktif',
        ]);

        $akunKredit = Account::factory()->create([
            'no_ref_account' => '222',
            'account_name' => 'Akun Kredit',
            'account_category' => 'Liabilitas',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($user)->post('/admin/kas/store', [
            'nominal' => 100000,
            'akun_debit' => $akunDebit->no_ref_account,
            'akun_kredit' => $akunKredit->no_ref_account,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('journal_entries', [
            'no_ref_account' => $akunDebit->no_ref_account,
            'position' => 'Debit',
            'nominal' => 100000.00,
        ]);
        $this->assertDatabaseMissing('journal_entries', [
            'no_ref_account' => $akunKredit->no_ref_account,
            'position' => 'Credit',
            'nominal' => 100000.00,
        ]);
    });
});
