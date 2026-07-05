<?php

use App\Models\Akun;
use App\Models\Pengguna;
use Database\Seeders\AkunSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
});

describe('Aplikasi harus menyediakan pengelolaan akun koperasi oleh bendahara.', function () {
    it('Bendahara dapat menambahkan akun koperasi.', function () {
        $bendahara = Pengguna::factory()->create();
        $bendahara->assignRole('Bendahara');

        $response = $this->actingAs($bendahara)->post('/admin/akun/create', [
            'nama_akun' => 'Akun Baru',
            'nomor_akun' => '123',
            'jenis_akun' => 'Aset',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('akun', [
            'nama_akun' => 'Akun Baru',
            'no_ref_akun' => '123',
            'kategori_akun' => 'Aset',
        ]);
    });

    it('Selain bendahara, pengguna lain tidak dapat menambahkan akun koperasi.', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Pengawas');
        $response = $this->actingAs($user)->post('/admin/akun/create', [
            'nama_akun' => 'Akun Baru',
            'nomor_akun' => '123',
            'jenis_akun' => 'Aset',
        ]);
        $response->assertStatus(403);
    });

    it('Bendahara dapat memperbarui status akun koperasi.', function () {
        $bendahara = Pengguna::factory()->create();
        $bendahara->assignRole('Bendahara');

        $akun = Akun::factory()->create([
            'no_ref_akun' => '123',
            'nama_akun' => 'Akun Lama',
            'kategori_akun' => 'Aset',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($bendahara)->patch("/admin/akun/{$akun->no_ref_akun}/status", [
            'status' => 'Non-Aktif',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('akun', [
            'no_ref_akun' => '123',
            'status' => 'Non-Aktif',
        ]);
    });
});

describe('Aplikasi harus menyediakan ekspor laporan arus kas dengan format excel bagi pengawas, DPS, ketua koperasi, dan bendahara.', function () {
    it('Pengawas dapat mengekspor laporan arus kas dengan format excel.', function () {
        $pengawas = Pengguna::factory()->create();
        $pengawas->assignRole('Pengawas');

        $response = $this->actingAs($pengawas)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('DPS dapat mengekspor laporan arus kas dengan format excel.', function () {
        $dps = Pengguna::factory()->create();
        $dps->assignRole('Dewan Pengawas Syariah');

        $response = $this->actingAs($dps)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('Ketua koperasi dapat mengekspor laporan arus kas dengan format excel.', function () {
        $ketua = Pengguna::factory()->create();
        $ketua->assignRole('Ketua');

        $response = $this->actingAs($ketua)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('Bendahara dapat mengekspor laporan arus kas dengan format excel.', function () {
        $bendahara = Pengguna::factory()->create();
        $bendahara->assignRole('Bendahara');

        $response = $this->actingAs($bendahara)->get('/admin/kas/export/excel');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});

describe('Aplikasi harus menyediakan pencatatan alokasi kas koperasi untuk setiap produk oleh bendahara.', function () {
    it('Bendahara dapat mencatat alokasi kas koperasi untuk setiap produk.', function () {
        $bendahara = Pengguna::factory()->create();
        $bendahara->assignRole('Bendahara');

        $akunDebit = Akun::factory()->create([
            'no_ref_akun' => '111',
            'nama_akun' => 'Akun Debit',
            'kategori_akun' => 'Aset',
            'status' => 'Aktif',
        ]);

        $akunKredit = Akun::factory()->create([
            'no_ref_akun' => '222',
            'nama_akun' => 'Akun Kredit',
            'kategori_akun' => 'Liabilitas',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($bendahara)->post('/admin/kas/store', [
            'nominal' => 100000,
            'akun_debit' => $akunDebit->no_ref_akun,
            'akun_kredit' => $akunKredit->no_ref_akun,
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('detail_jurnal', [
            'no_ref_akun' => $akunDebit->no_ref_akun,
            'posisi_akun' => 'Debit',
            'nominal' => 100000.00,
        ]);
        $this->assertDatabaseHas('detail_jurnal', [
            'no_ref_akun' => $akunKredit->no_ref_akun,
            'posisi_akun' => 'Credit',
            'nominal' => 100000.00,
        ]);
    });

    it('Selain bendahara, pengguna lain tidak dapat mencatat alokasi kas koperasi untuk setiap produk.', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Pengawas');

        $akunDebit = Akun::factory()->create([
            'no_ref_akun' => '111',
            'nama_akun' => 'Akun Debit',
            'kategori_akun' => 'Aset',
            'status' => 'Aktif',
        ]);

        $akunKredit = Akun::factory()->create([
            'no_ref_akun' => '222',
            'nama_akun' => 'Akun Kredit',
            'kategori_akun' => 'Liabilitas',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($user)->post('/admin/kas/store', [
            'nominal' => 100000,
            'akun_debit' => $akunDebit->no_ref_akun,
            'akun_kredit' => $akunKredit->no_ref_akun,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('detail_jurnal', [
            'no_ref_akun' => $akunDebit->no_ref_akun,
            'posisi_akun' => 'Debit',
            'nominal' => 100000.00,
        ]);
        $this->assertDatabaseMissing('detail_jurnal', [
            'no_ref_akun' => $akunKredit->no_ref_akun,
            'posisi_akun' => 'Credit',
            'nominal' => 100000.00,
        ]);
    });
});
