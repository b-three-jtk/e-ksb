<?php

use App\Enums\EducationEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('[REQ-F-01] Aplikasi dapat mengidentifikasi dan mengkonfirmasi pengguna yang masuk ke dalam sistem agar dapat melakukan fungsionalitas sesuai dengan perannya', function () {
    $user = User::factory()->create();
    Member::factory()->create([
        'user_id' => $user->id,
    ]);
    $user->assignRole('Anggota');

    $user1 = User::factory()->create();
    $user1->assignRole('Sekretaris');

    $response = $this->actingAs($user)->get('/user/dashboard');
    $response1 = $this->actingAs($user1)->get('/admin/dashboard');

    $response->assertStatus(200);
    $response1->assertStatus(200);
});

describe('[REQ-F-02, REQ-F-08] Aplikasi harus memungkinkan hanya pengguna dengan peran Sekretaris yang dapat melakukan registrasi anggota baru', function () {

    it('Sekretaris dapat meregistrasi anggota baru dan status otomatis Menunggu Pembayaran', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $responseSekretaris = $this->actingAs($sekretaris)
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

        $responseSekretaris->assertStatus(302);

        // Cek data di tabel users masuk
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // [REQ-F-08] Pastikan statusnya Menunggu Pembayaran, bukan Aktif
        $this->assertDatabaseHas('members', [
            'user_id' => User::where('nik', '1234567890123456')->first()->id,
            'status' => 'Menunggu Pembayaran'
        ]);
    });

    it('Selain Sekretaris tidak dapat melakukan registrasi anggota baru (403 Forbidden)', function () {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $responseAnggota = $this->actingAs($anggota)
            ->post('/admin/users/store', [
                'name' => 'Test Hacker',
                'email' => 'hacker@example.com',
                'nik' => '1234567890123456',
                // ... isi form lainnya ...
            ]);

        $responseAnggota->assertStatus(403);
    });
});

describe('[REQ-F-03, REQ-F-09] Aplikasi harus memungkinkan hanya pengguna dengan peran Sekretaris yang dapat menambah data pengurus koperasi', function () {

    it('Sekretaris dapat menambah data pengurus koperasi non-anggota', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $role = Role::where('name', 'Staf Murabahah')->first();

        $responseSekretaris = $this->actingAs($sekretaris)
            ->post('/admin/store', [
                'name' => 'Test Pengurus',
                'email' => 'pengurus@example.com',
                'nik' => '1111222233334444',
                'phone_number' => '081234567890',
                'role_id' => $role->id,
            ]);

        $responseSekretaris->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'pengurus@example.com',
            'status' => 'Aktif' // Pengurus biasanya langsung aktif
        ]);
    });

    it('Selain Sekretaris tidak dapat menambah data pengurus koperasi', function () {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');
        $role = Role::where('name', 'Staf Murabahah')->first();

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

describe('[REQ-F-04] Aplikasi harus memungkinkan hanya pengguna dengan peran Sekretaris yang dapat mengubah data pengurus koperasi yang sudah terdaftar', function () {

    it('Sekretaris dapat mengubah data pengurus', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $pengurus = User::factory()->create(['name' => 'Nama Lama']);
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
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $pengurus = User::factory()->create(['name' => 'Nama Lama']);
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

describe('[REQ-F-05] Aplikasi harus memungkinkan hanya pengguna dengan peran Ketua Koperasi yang dapat memproses pengunduran diri anggota', function () {

    it('Ketua dapat memproses pengunduran diri anggota', function () {
        $ketua = User::factory()->create();
        $ketua->assignRole('Ketua');

        $anggota = Member::factory()->create([
            'status' => MemberStatusEnum::RESIGNED_REQUESTED->value
        ]);
        $user = User::where('id', $anggota->user_id)->first();
        $user->assignRole('Anggota');

        $responseKetua = $this->actingAs($ketua)
            ->put('/admin/resignation/' . $user->id, [
                'status' => 'accept'
            ]);

        $responseKetua->assertStatus(302);
    });

    it('Selain Ketua tidak dapat memproses pengunduran diri anggota', function () {
        $anggotaBiasa = Member::factory()->create([
            'status' => MemberStatusEnum::RESIGNED_REQUESTED->value
        ]);
        $user = User::where('id', $anggotaBiasa->user_id)->first();
        $user->assignRole('Anggota');

        // Mencoba memproses pengunduran dirinya sendiri (atau orang lain)
        $responseAnggota = $this->actingAs($user)
            ->put('/admin/resignation/' . $anggotaBiasa->id, [
                'status' => 'accept'
            ]);

        $responseAnggota->assertStatus(403);
    });
});

test('[REQ-F-06] Aplikasi harus memastikan bahwa hanya anggota dengan status Aktif yang dapat mengajukan pengunduran diri', function () {
    $anggotaPending = Member::factory()->create([
        'status' => MemberStatusEnum::PAYMENT_PENDING->value
    ]);
    $user = User::where('id', $anggotaPending->user_id)->first();
    $user->assignRole('Anggota');

    $responseAnggota = $this->actingAs($user)
        ->post('/user/resign', [
            'document' => UploadedFile::fake()->create('resign.pdf')
        ]);

    $responseAnggota->assertSessionHasErrors([
        'resign' => 'Status anggota tidak valid untuk pengajuan pengunduran diri.'
    ]);
});

test('[REQ-F-11] Aplikasi harus mencegah pengajuan pengunduran diri baru apabila sudah ada pengajuan yang masih aktif', function () {
    $anggotaResigning = Member::factory()->create([
        'status' => MemberStatusEnum::RESIGNED_REQUESTED->value
    ]);
    $user = User::where('id', $anggotaResigning->user_id)->first();
    $user->assignRole('Anggota');

    $responseAnggota = $this->actingAs($user)
        ->post('/user/resign', [
            'document' => UploadedFile::fake()->create('resign.pdf')
        ]);

    $responseAnggota->assertSessionHasErrors([
        'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.'
    ]);
});

test('[REQ-F-12] Aplikasi memverifikasi bahwa anggota tidak memiliki kewajiban finansial sebelum mengizinkan pengajuan pengunduran diri', function () {
    $anggotaAktif = Member::factory()->create([
        'status' => 'Aktif'
    ]);
    $user = User::where('id', $anggotaAktif->user_id)->first();
    $user->assignRole('Anggota');

    // Asumsi factory member membuat anggota bersih tanpa utang
    $responseAnggota = $this->actingAs($user)
        ->post('/user/resign', [
            'document' => UploadedFile::fake()->create('resign.pdf')
        ]);

    $responseAnggota->assertStatus(302);
});

test('[REQ-F-07, REQ-F-10] Aplikasi harus membuat nomor anggota dan nomor pengurus yang unik secara otomatis', function () {
    $anggota1 = User::factory()->create();
    $anggota1->assignRole('Anggota');

    $anggota2 = User::factory()->create();
    $anggota2->assignRole('Anggota');

    $pengurus = User::factory()->create();
    $pengurus->assignRole('Staf Murabahah');

    expect($anggota1->user_code)->not()->toBe($anggota2->user_code);
    expect($pengurus->user_code)->not()->toBe($anggota1->user_code);
});
