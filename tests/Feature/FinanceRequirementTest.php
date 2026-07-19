<?php

use App\Enums\AkunCategoryEnum;
use App\Enums\PositionEnum;
use App\Enums\UserStatusEnum;
use App\Models\Akun;
use App\Models\Pengguna;
use Database\Seeders\AkunSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JurnalUmumExport;
use App\Exports\LaporanArusKasExport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
});

// ============================================================================
// 1. Pengelolaan Akun oleh Bendahara
// ============================================================================
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

// ============================================================================
// 2. Pencatatan Alokasi Kas Koperasi
// ============================================================================
describe('Pencatatan Alokasi Kas', function () {
    it('Bendahara dapat menyimpan pencatatan alokasi kas koperasi untuk setiap produk', function () {
        $bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $bendahara->syncRoles('Bendahara');

        $akunDebit = Akun::where('no_ref_akun', '!=', '')->first();
        $akunKredit = Akun::where('no_ref_akun', '!=', $akunDebit->no_ref_akun)->first();

        $response = $this->actingAs($bendahara)->post('/admin/kas/store', [
            'nominal' => 5000000,
            'akun_debit' => $akunDebit->no_ref_akun,
            'akun_kredit' => $akunKredit->no_ref_akun,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Alokasi kas berhasil diposting');

        // Pastikan detail jurnal tercatat di database (karena menggunakan JurnalService->create)
        $this->assertDatabaseHas('detail_jurnal', [
            'no_ref_akun' => $akunDebit->no_ref_akun,
            'posisi_akun' => PositionEnum::DEBIT->value,
            'nominal' => 5000000,
        ]);
        $this->assertDatabaseHas('detail_jurnal', [
            'no_ref_akun' => $akunKredit->no_ref_akun,
            'posisi_akun' => PositionEnum::CREDIT->value,
            'nominal' => 5000000,
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

// ============================================================================
// 3. Ekspor Laporan Arus Kas dan Jurnal Umum
// ============================================================================
describe('Ekspor Laporan Arus Kas dan Jurnal Umum', function () {
    it('Pengawas, DPS, Ketua Koperasi, dan Bendahara dapat mengekspor laporan arus kas dan jurnal umum', function () {
        Excel::fake();

        $roles = [
            'Pengawas',
            'Dewan Pengawas Syariah',
            'Ketua',
            'Bendahara',
        ];

        foreach ($roles as $roleName) {
            $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
            $user->syncRoles($roleName);

            // Mock waktu agar timestamp di nama file sama persis
            $now = now();
            \Carbon\Carbon::setTestNow($now);

            // Test Export Jurnal Umum
            $responseExcel = $this->actingAs($user)->get('/admin/kas/export/excel');
            $responseExcel->assertStatus(200);
            Excel::assertDownloaded('jurnal_umum_' . $now->format('Ymd_His') . '.xlsx');

            // Test Export Laporan Arus Kas
            $responseCashflow = $this->actingAs($user)->get('/admin/kas/export/cashflow');
            $responseCashflow->assertStatus(200);
            Excel::assertDownloaded('laporan_arus_kas_' . $now->format('Ymd_His') . '.xlsx');
            
            \Carbon\Carbon::setTestNow(); // Reset mock
        }
    });

    it('Selain Pengawas, DPS, Ketua, dan Bendahara tidak dapat mengekspor laporan arus kas dan jurnal umum', function () {
        $disallowedRoles = [
            'Sekretaris',
            'Ketua Murabahah',
            'Staf Murabahah',
            'Penanggung Jawab Anggota',
            'Anggota'
        ];

        foreach ($disallowedRoles as $roleName) {
            $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
            $user->syncRoles($roleName);

            $responseExcel = $this->actingAs($user)->get('/admin/kas/export/excel');
            $responseExcel->assertStatus(403);

            $responseCashflow = $this->actingAs($user)->get('/admin/kas/export/cashflow');
            $responseCashflow->assertStatus(403);
        }
    });
});
