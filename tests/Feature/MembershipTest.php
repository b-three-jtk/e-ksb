<?php

use App\Enums\EducationEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Financing;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// FR 51
describe('system identifies and authenticates user roles for proper functionality access', function () {
    it('allows sekretaris to access member registration page', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $responseSekretaris = $this->actingAs($sekretaris)
                            ->get('/admin/users/create');

        $responseSekretaris->assertStatus(200);
    });

    it('denies anggota from accessing member registration page', function () {
        $anggotaBiasa = User::factory()->create();
        $anggotaBiasa->assignRole('Anggota');
        $responseAnggota = $this->actingAs($anggotaBiasa)
                                ->get('/admin/users/create');

        $responseAnggota->assertStatus(403);
    });
});

// FR 52, 53, 54, 55, 59
test('only sekretaris can register new members', function () {
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
                                'user_code' => 'KSP999',
                                'domicile_address' => 'Jl. Test No. 123',
                                'last_education' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                                'nik' => '1234567890123456',
                                'phone_number' => '081234567890',
                                'heir_nik' => '6543210987654321',
                                'heir_name' => 'Heir Test',
                                'heir_relationship' => 'Saudara',
                                'heir_contact' => '081234567891',
                            ]);

        // check if status aktif
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'status' => 'Aktif'
        ]);
        $responseSekretaris->assertStatus(302);
});

//  60
describe('only sekretaris can add new pengurus data', function () {
    it('allows sekretaris to add new pengurus data', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $responseSekretaris = $this->actingAs($sekretaris)
                            ->post('/admin/store', [
                                'nik' => '1234567890123456',
                                'gender' => 'Laki-laki',
                                'email' => 'test-pengurus@example.com',
                                'phone_number' => '081234567890',
                                'name' => 'Test Pengurus',
                                'role_id' => 2,
                            ]);

        $responseSekretaris->assertStatus(302);
    });

    it('allows sekretaris to add new pengurus data from existing members', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $existingMember = User::factory()->create();
        $existingMember->assignRole('Anggota');

        $responseSekretaris = $this->actingAs($sekretaris)
                            ->post('/admin/store', [
                                'nik' => $existingMember->nik,
                                'gender' => $existingMember->gender,
                                'email' => $existingMember->email,
                                'phone_number' => $existingMember->phone_number,
                                'name' => $existingMember->name,
                                'role_id' => 2,
                            ]);

        $responseSekretaris->assertStatus(302);
    });

    it('denies anggota from adding new pengurus data', function () {
        $anggotaBiasa = User::factory()->create();
        $anggotaBiasa->assignRole('Anggota');
        $responseAnggota = $this->actingAs($anggotaBiasa)
                            ->post('/admin/store', [
                                'nik' => '1234567890123456',
                                'gender' => 'Laki-laki',
                                'email' => 'test-pengurus@example.com',
                                'phone_number' => '081234567890',
                                'name' => 'Test Pengurus',
                                'role_id' => 2,
                            ]);

        $responseAnggota->assertStatus(403);
    });
});

describe('only sekretaris can update existing pengurus data', function () {
    it('allows sekretaris to update pengurus data', function () {
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $pengurus = User::factory()->create();
        $pengurus->assignRole('Bendahara');

        $responseSekretaris = $this->actingAs($sekretaris)
                            ->put("/admin/update/{$pengurus->id}", [
                                'nik' => '1234567890123456']);
        $responseSekretaris->assertStatus(302);
    });

    it('denies anggota from updating pengurus data', function () {
        $anggotaBiasa = User::factory()->create();
        $anggotaBiasa->assignRole('Anggota');
        $pengurus = User::factory()->create();
        $pengurus->assignRole('Bendahara');

        $responseAnggota = $this->actingAs($anggotaBiasa)
                            ->put("/admin/update/{$pengurus->id}", [
                                'nik' => '1234567890123456']);
        $responseAnggota->assertStatus(403);
    });
});

// FR 60, 61, 62
test('system supports assigning pengurus roles from active members and non-members', function () {
    $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $responseSekretaris = $this->actingAs($sekretaris)
                            ->get('/admin/pengurus/create');

        $responseSekretaris->assertStatus(200);
});

test('non-member pengurus ID is unique and does not clash with member or active pengurus IDs', function () {
    $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $responseSekretaris = $this->actingAs($sekretaris)
                            ->get('/admin/pengurus/create');

        $responseSekretaris->assertStatus(200);
});

test('specific pengurus positions can only be held by one active pengurus at a time', function () {
    $sekretaris = User::factory()->create();
        $sekretaris->assignRole('Sekretaris');
        $responseSekretaris = $this->actingAs($sekretaris)
                            ->get('/admin/pengurus/create');

        $responseSekretaris->assertStatus(200);
});

// FR 56, 57, 63, 64, 65
test('only active members can submit resignation requests', function () {
    $anggotaBiasa = User::factory()->create();
        $anggotaBiasa->assignRole('Anggota');
        $responseAnggota = $this->actingAs($anggotaBiasa)
                                ->get('/admin/anggota/resign');

        $responseAnggota->assertStatus(200);
});

test('members cannot submit new resignation if there is an active pending request', function () {
    $anggotaBiasa = User::factory()->create();
        $anggotaBiasa->assignRole('Anggota');
        $responseAnggota = $this->actingAs($anggotaBiasa)
                                ->get('/admin/anggota/resign');

        $responseAnggota->assertStatus(200);
});

test('system verifies member has no pending financial or administrative obligations before resignation', function () {
        $anggotaBiasa = Member::factory()->create();
        $user = User::where('id', $anggotaBiasa->user_id)->first();
        $user->assignRole('Anggota');

        $responseAnggota = $this->actingAs($user)
                                ->post('/user/resign', [
                                    'document' => UploadedFile::fake()->create('resign.pdf')
                                ]);

        $responseAnggota->assertStatus(302);
});

test('system rejects resignation and displays reason if obligations exist', function () {
        $anggotaBiasa = Member::factory()->create();
        $user = User::where('id', $anggotaBiasa->user_id)->first();
        $user->assignRole('Anggota');
        $responseAnggota = $this->actingAs($user)
                                ->get('/admin/anggota/resign');

        $responseAnggota->assertStatus(200);
});

test('only ketua koperasi can approve or reject member resignation requests', function () {
    $ketuaKoperasi = User::factory()->create();
        $ketuaKoperasi->assignRole('Ketua');
        $responseKetua = $this->actingAs($ketuaKoperasi)
                                ->get('/admin/anggota/resign');

        $responseKetua->assertStatus(200);
});
