<?php

use App\Enums\AkunCategoryEnum;
use App\Enums\UserStatusEnum;
use App\Models\Akun;
use App\Models\Pengguna;
use Database\Seeders\AkunSeeder;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
    $this->seed(PengaturanUmumSeeder::class);
});

describe('Pengelolaan Akun', function () {
    it('Bendahara dapat melihat daftar akun', function () {
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $response = $this->actingAs($bendahara)->get('/admin/akun');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Accounts/List')
                ->has('akun.data')
        );
    });

    it('Bendahara dapat menyimpan akun baru', function () {
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $response = $this->actingAs($bendahara)->post('/admin/akun/create', [
            'nomor_akun' => '999999',
            'nama_akun' => 'Akun Test Bendahara',
            'jenis_akun' => AkunCategoryEnum::ASSET->value,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('akun', [
            'no_ref_akun' => '999999',
            'nama_akun' => 'Akun Test Bendahara',
        ]);
    });

    it('Staf Murabahah tidak dapat menyimpan atau melihat daftar akun', function () {
        $staf = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staf->syncRoles('Staf Murabahah');

        $response = $this->actingAs($staf)->get('/admin/akun');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($staf)->post('/admin/akun/create', [
            'nomor_akun' => '888888',
            'nama_akun' => 'Akun Test Staf',
            'jenis_akun' => AkunCategoryEnum::ASSET->value,
        ]);
        $responsePost->assertStatus(403);
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
    
    it('Sistem menolak alokasi jika akun debit dan kredit sama', function () {
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $akun = Akun::first();

        $response = $this->actingAs($bendahara)->post('/admin/kas/store', [
            'nominal' => 5000000,
            'akun_debit' => $akun->no_ref_akun,
            'akun_kredit' => $akun->no_ref_akun, // Sama dengan debit
        ]);

        $response->assertSessionHasErrors(['akun_kredit']);
    });
});
