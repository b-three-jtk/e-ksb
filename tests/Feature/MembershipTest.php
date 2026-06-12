<?php

use App\Enums\EducationEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Financing;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('FR-01 Aplikasi harus menyediakan registrasi anggota baru KSB oleh sekretaris.', function () {

    it('Sekretaris dapat meregistrasi anggota baru dan status otomatis Menunggu Pembayaran', function () {
        $user = User::factory()->create();
        $user->assignRole('Sekretaris');

        $res = $this->actingAs($user)
            ->post('/admin/users/store', [
                'name' => 'Test Member',
                'gender' => 'Laki-laki',
                'birth_place' => 'Bandung',
                'birth_date' => '1990-01-01',
                'marital_status' => 'Kawin',
                'email' => 'test@example.com',
                'password' => 'password',
                'domicile_address' => 'Jl. Test No. 123',
                'last_education' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'nik' => '1234567890123456',
                'phone_number' => '081234567890',
                'heir_nik' => '6543210987654321',
                'heir_name' => 'Heir Test',
                'heir_relationship' => 'Saudara',
                'heir_contact' => '081234567891',
            ]);

        $res->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('members', [
            'user_id' => User::where('nik', '1234567890123456')->first()->id,
            'status' => 'Menunggu Pembayaran'
        ]);
    });

    it('Selain Sekretaris tidak dapat melakukan registrasi anggota baru (403 Forbidden)', function () {
        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $res = $this->actingAs($anggota)
            ->post('/admin/users/store', [
                'name' => 'Test Member',
                'gender' => 'Laki-laki',
                'birth_place' => 'Bandung',
                'birth_date' => '1990-01-01',
                'marital_status' => 'Kawin',
                'email' => 'test@example.com',
                'password' => 'password',
                'domicile_address' => 'Jl. Test No. 123',
                'last_education' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'nik' => '1234567890123456',
                'phone_number' => '081234567890',
                'heir_nik' => '6543210987654321',
                'heir_name' => 'Heir Test',
                'heir_relationship' => 'Saudara',
                'heir_contact' => '081234567891',
            ]);

        $res->assertStatus(403);
    });
});

describe('FR-02: Aplikasi harus menyediakan autentikasi akun pengguna berdasarkan kode keanggotaan dan kata sandi.', function () {
    it('Pengguna dengan kode keanggotaan dan kata sandi yang benar dapat masuk ke dalam sistem', function () {
        $user = User::factory()->create([
            'user_code' => 'MEMBER123',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);
        Member::factory()->create([
            'user_id' => $user->id,
        ]);
        $user->assignRole('Anggota');

        $res = $this->post('auth/login', [
            'user_code' => 'MEMBER123',
            'password' => 'password123',
        ]);

        $res->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    });

    it('Pengguna dengan kode keanggotaan atau kata sandi yang salah tidak dapat masuk ke dalam sistem', function () {
        $user = User::factory()->create([
            'user_code' => 'MEMBER123',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);
        Member::factory()->create([
            'user_id' => $user->id,
        ]);
        $user->assignRole('Anggota');

        $res = $this->post('auth/login', [
            'user_code' => $user->user_code,
            'password' => 'wrongpassword',
        ]);

        $res->assertSessionHasErrors();
        $this->assertGuest();
    });
});

describe('FR-03: Aplikasi harus menyediakan dashboard operasional yang menyajikan ringkasan dan visualisasi data transaksi sesuai dengan hak akses masing-masing peran.', function () {
    it('Anggota dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        Member::factory()->create([
            'user_id' => $user->id,
        ]);

        $res = $this->actingAs($user)->get('/user/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('User/Dashboard')
                ->has('summary')
                ->has('ledger')
        );
    });

    it('Sekretaris dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = User::factory()->create();
        $user->assignRole('Sekretaris');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('active_user_count')
                ->has('active_user_percentage')
                ->has('total_saving_amount')
                ->has('total_financing_amount')
                ->has('total_financing_percentage')
                ->has('transaction_data')
                ->has('registration_data')
                ->has('financing_data')
                ->has('financing_stats')
        );
    });
});

describe('FR-04 Aplikasi harus menyediakan pengajuan pengunduran diri keanggotaan oleh anggota aktif.', function () {
    it('Anggota aktif dapat mengajukan pengunduran diri dengan melampirkan dokumen yang diperlukan', function () {
        $member = Member::factory()->create([
            'status' => 'Aktif',
        ]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($user)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('resign.pdf'),
            ]);

        Log::info('Resignation submission response: ' . $res->getContent());

        $res->assertStatus(302);
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'status' => 'Pengunduran Diri Diajukan',
        ]);
    });

    it('Anggota yang sudah mengajukan pengunduran diri tidak dapat mengajukan lagi', function () {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        Member::factory()->create([
            'user_id' => $user->id,
            'status' => 'Pengunduran Diri Diajukan',
        ]);

        $res = $this->actingAs($user)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('resign.pdf'),
            ]);

        $res->assertSessionHasErrors([
            'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.'
        ]);
    });

    it('Anggota yang masih mempunyai kewajiban tidak dapat mengajukan pengunduran diri', function () {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'status' => 'Aktif',
        ]);

        Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $res = $this->actingAs($user)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('resign.pdf'),
            ]);

        $res->assertSessionHasErrors([
            'resign' => 'Anda masih memiliki kewajiban finansial yang belum dilunasi. Silakan selesaikan kewajiban tersebut sebelum mengajukan pengunduran diri.'
        ]);
    });
});

describe('FR-05 Aplikasi harus menyediakan penanganan permohonan pengajuan diri anggota oleh ketua dan sekretaris', function () {
    it('Ketua dapat memproses permohonan pengunduran diri anggota', function () {
        $ketua = User::factory()->create();
        $ketua->assignRole('Ketua');

        $member = Member::factory()->create([
            'status' => 'Pengunduran Diri Diajukan',
        ]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($ketua)
            ->put('/admin/resignations/' . $user->id);

        $res->assertStatus(302);
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'status' => 'Mengundurkan Diri',
        ]);
    });

    it('Selain Ketua tidak dapat memproses permohonan pengunduran diri anggota', function () {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $member = Member::factory()->create([
            'status' => 'Pengunduran Diri Diajukan',
        ]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($anggota)
            ->put('/admin/resignations/' . $user->id);

        $res->assertStatus(403);
    });
});

describe('FR-06 Aplikasi harus menyediakan pendaftaran pengurus baru dari anggota aktif maupun non-anggota oleh sekretaris.', function () {
    it('Sekretaris dapat menambah data pengurus koperasi non-anggota', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $role = Role::where('name', 'Bendahara')->first();

        $res = $this->actingAs($sekretaris)
            ->post('/admin/store', [
                'name' => 'Test Pengurus',
                'email' => 'pengurus@example.com',
                'nik' => '1111222233334444',
                'phone_number' => '081234567890',
                'role_id' => $role->id,
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'pengurus@example.com',
            'status' => 'Aktif'
        ]);
    });

    it('Sekretaris dapat menambah data pengurus koperasi dari anggota aktif', function () {
        $sekretaris = User::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        Member::factory()->create([
            'user_id' => $anggota->id,
            'status' => 'Aktif',
        ]);

        $role = Role::where('name', 'Bendahara')->first();

        $res = $this->actingAs($sekretaris)
            ->post('/admin/store', [
                'user_id' => $anggota->id,
                'name' => 'Test Pengurus',
                'email' => 'pengurus@example.com',
                'nik' => '1111222233334444',
                'phone_number' => '081234567890',
                'role_id' => $role->id,
            ]);

        Log::info('Add admin from member response: ' . $res->getContent());

        $res->assertStatus(302);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $anggota->id
        ]);
    });

    it('Selain Sekretaris tidak dapat menambah data pengurus koperasi', function () {
        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        $role = Role::where('name', 'Bendahara')->first();

        $responseAnggota = $this->actingAs($anggota)
            ->post('/admin/store', [
                'name' => 'Test Pengurus',
                'email' => 'pengurus2@example.com',
                'nik' => '1111222233334444',
                'role_id' => $role->id,
            ]);

        $responseAnggota->assertStatus(403);
    });
});

describe('FR-07 Aplikasi harus menyediakan daftar anggota dan pengurus untuk ketua koperasi dan sekretaris.', function () {
    it('Ketua dan Sekretaris dapat melihat daftar anggota dan pengurus', function () {
        $ketua = User::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = User::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $resKetua = $this->actingAs($ketua)->get('/admin/pengurus');
        $resSekretaris = $this->actingAs($sekretaris)->get('/admin/pengurus');

        $resAnggotabyKetua = $this->actingAs($ketua)->get('/admin/users/list');
        $resAnggotabySekretaris = $this->actingAs($sekretaris)->get('/admin/users/list');

        $resKetua->assertStatus(200);
        $resSekretaris->assertStatus(200);
        $resAnggotabyKetua->assertStatus(200);
        $resAnggotabySekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat daftar anggota dan pengurus', function () {
        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $res = $this->actingAs($anggota)->get('/admin/pengurus');
        $resAnggota = $this->actingAs($anggota)->get('/admin/users/list');

        $res->assertStatus(403);
        $resAnggota->assertStatus(403);
    });
});

// describe('FR-08 Aplikasi harus menyediakan pengalokasian anggota ke penanggung jawab anggota oleh ketua koperasi.', function () {
//     it('Ketua dapat mengalokasikan anggota ke penanggung jawab anggota', function () {

//     });

//     it('Selain Ketua tidak dapat mengalokasikan anggota ke penanggung jawab anggota', function () {

//     });
// });

describe('FR-09 Aplikasi harus menyediakan detail informasi masing-masing anggota dan pengurus.', function () {
    it('Ketua dan Sekretaris dapat melihat detail informasi anggota dan pengurus', function () {
        $ketua = User::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = User::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $resKetua = $this->actingAs($ketua)->get('/admin/users/show/' . $anggota->id);
        $resSekretaris = $this->actingAs($sekretaris)->get('/admin/users/show/' . $anggota->id);
        $resPengurusbyKetua = $this->actingAs($ketua)->get('/admin/show/' . $sekretaris->id);
        $resPengurusbySekretaris = $this->actingAs($sekretaris)->get('/admin/show/' . $ketua->id);

        $resKetua->assertStatus(200);
        $resSekretaris->assertStatus(200);
        $resPengurusbyKetua->assertStatus(200);
        $resPengurusbySekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat detail informasi anggota dan pengurus', function () {
        $anggota1 = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota1->assignRole('Anggota');

        $anggota2 = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota2->assignRole('Anggota');

        $res = $this->actingAs($anggota1)->get('/admin/users/show/' . $anggota2->id);
        $resPengurus = $this->actingAs($anggota1)->get('/admin/show/' . $anggota2->id);

        $res->assertStatus(403);
        $resPengurus->assertStatus(403);
    });
});

describe('FR-11 Aplikasi harus menyediakan pembaruan informasi pengurus oleh sekretaris.', function () {
    it('Sekretaris dapat mengubah data pengurus', function () {
        $sekretaris = User::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $pengurus = User::factory([
            'status' => 'Aktif',
            'name' => 'Nama Lama'
        ])->create();
        $pengurus->assignRole('Staf Murabahah');

        $responseSekretaris = $this->actingAs($sekretaris)
            ->put('/admin/update/' . $pengurus->id, [
                'name' => 'Nama Baru Diupdate',
                'email' => $pengurus->email,
                'nik' => $pengurus->nik,
                'phone_number' => '08999999999'
            ]);

        $responseSekretaris->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $pengurus->id,
            'name' => 'Nama Baru Diupdate'
        ]);
    });

    it('Selain Sekretaris tidak dapat mengubah data pengurus', function () {
        $anggota = User::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $pengurus = User::factory(['name' => 'Nama Lama', 'status' => 'Aktif'])->create();
        $pengurus->assignRole('Staf Murabahah');

        $responseAnggota = $this->actingAs($anggota)
            ->put('/admin/update/' . $pengurus->id, [
                'nik' => '1111222233334444',
                'email' => $pengurus->email,
                'name' => 'Hacked Name',
            ]);

        $responseAnggota->assertStatus(403);
    });
});
