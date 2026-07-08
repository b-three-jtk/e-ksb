<?php

use App\Enums\EducationEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use App\Models\Pengguna;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PengaturanUmumSeeder::class);
});

describe('Aplikasi harus menyediakan pendaftaran pengurus baru dari anggota aktif maupun non-anggota oleh sekretaris.', function () {
    it('Sekretaris dapat menambah data pengurus koperasi non-anggota', function () {
        $sekretaris = Pengguna::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $role = Role::where('name', 'Bendahara')->first();

        $res = $this->actingAs($sekretaris)
            ->post('/admin/pengurus/store', [
                'nama' => 'Leon S Kennedy',
                'email' => 'asep@example.com',
                'nik' => '1111222233334444',
                'no_telp' => '081234567890',
                'role_id' => $role->id,
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('pengguna', [
            'nama' => 'Leon S Kennedy',
            'nik' => '1111222233334444',
            'no_telp' => '081234567890',
            'email' => 'asep@example.com',
            'status' => 'Aktif'
        ]);
    });

    it('Sekretaris dapat menambah data pengurus koperasi dari anggota aktif', function () {
        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        Anggota::factory()->create([
            'pengguna_id' => $anggota->id,
            'status' => 'Aktif',
        ]);

        $role = Role::where('name', 'Bendahara')->first();

        $this->actingAs($sekretaris)
            ->post('/admin/pengurus/store', [
                'pengguna_id' => $anggota->id,
                'nama' => 'Leon S Kennedy',
                'email' => 'asep@example.com',
                'nik' => '1111222233334444',
                'no_telp' => '081234567890',
                'role_id' => $role->id,
            ]);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $anggota->id
        ]);
        $this->assertDatabaseHas('pengguna', [
            'nama' => 'Leon S Kennedy',
            'nik' => '1111222233334444',
            'no_telp' => '081234567890',
            'email' => 'asep@example.com',
            'status' => 'Aktif'
        ]);
        $this->assertDatabaseHas('anggota', [
            'pengguna_id' => $anggota->id,
            'status' => 'Aktif',
        ]);
    });

    it('Sekretaris tidak dapat menambah data pengurus koperasi dengan data yang tidak valid', function () {
        $sekretaris = Pengguna::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $role = Role::where('name', 'Bendahara')->first();

        $res = $this->actingAs($sekretaris)
            ->post('/admin/pengurus/store', [
                'nama' => 'Leon S Kennedy',
                'email' => 'asep@example.com',
                'nik' => '111122223333',
                'no_telp' => '081234567890081234567890',
                'role_id' => $role->id,
            ]);

        $res->assertSessionHasErrors([
            'nik' => 'The nik field must be 16 digits.',
            'no_telp' => 'The no telp field must not be greater than 20 characters.',
        ]);
        $this->assertDatabaseMissing('pengguna', [
            'nama' => 'Leon S Kennedy',
            'nik' => '111122223333',
            'no_telp' => '081234567890081234567890',
            'email' => 'asep@example.com',
        ]);
    });

    it('Selain Sekretaris tidak dapat menambah data pengurus koperasi', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        $role = Role::where('name', 'Bendahara')->first();

        $responseAnggota = $this->actingAs($anggota)
            ->post('/admin/pengurus/store', [
                'nama' => 'Leon S Kennedy',
                'email' => 'asep@example.com',
                'nik' => '1111222233334444',
                'no_telp' => '0823982938213',
                'role_id' => $role->id,
            ]);

        $responseAnggota->assertStatus(403);
        $this->assertDatabaseMissing('pengguna', [
            'nama' => 'Leon S Kennedy',
            'nik' => '1111222233334444',
            'email' => 'asep@example.com',
            'status' => 'Aktif'
        ]);
    });
});

describe('Aplikasi harus menyediakan daftar pengurus untuk ketua koperasi dan sekretaris.', function () {
    it('Ketua dan Sekretaris dapat melihat daftar pengurus', function () {
        $ketua = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $resKetua = $this->actingAs($ketua)->get('/admin/pengurus');
        $resSekretaris = $this->actingAs($sekretaris)->get('/admin/pengurus');

        $resKetua->assertStatus(200);
        $resSekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat daftar pengurus', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Staf Murabahah');

        $res = $this->actingAs($anggota)->get('/admin/pengurus');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan detail informasi masing-masing pengurus.', function () {
    it('Ketua dan Sekretaris dapat melihat detail informasi pengurus', function () {
        $ketua = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $resPengurusbyKetua = $this->actingAs($ketua)->get('/admin/pengurus/show/' . $sekretaris->id);
        $resPengurusbySekretaris = $this->actingAs($sekretaris)->get('/admin/pengurus/show/' . $ketua->id);

        $resPengurusbyKetua->assertStatus(200);
        $resPengurusbySekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat detail informasi pengurus', function () {
        $anggota1 = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota1->assignRole('Bendahara');

        $anggota2 = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota2->assignRole('Sekretaris');

        $resPengurus = $this->actingAs($anggota1)->get('/admin/pengurus/show/' . $anggota2->id);

        $resPengurus->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pembaruan informasi pengurus oleh sekretaris.', function () {
    it('Sekretaris dapat mengubah data pengurus', function () {
        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');
        $role = Role::where('name', 'Staf Murabahah')->first();

        $pengurus = Pengguna::factory([
            'status' => 'Aktif',
            'nama' => 'Leon Lama'
        ])->create();
        $pengurus->assignRole('Staf Murabahah');

        $responseSekretaris = $this->actingAs($sekretaris)
            ->put('/admin/pengurus/update/' . $pengurus->id, [
                'nama' => 'Leon Baru',
                'nik' => '1234567890123456',
                'no_telp' => '08934673463',
                'role_id' => $role->id,
            ]);

        $responseSekretaris->assertStatus(302);
        $this->assertDatabaseHas('pengguna', [
            'id' => $pengurus->id,
            'nama' => 'Leon Baru',
            'nik' => '1234567890123456',
            'no_telp' => '08934673463'
        ]);
    });

    it('Sekretaris tidak dapat mengubah data pengurus dengan data tidak valid', function () {
        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $pengurus = Pengguna::factory([
            'status' => 'Aktif',
            'nama' => 'Leon Lama'
        ])->create();
        $pengurus->assignRole('Staf Murabahah');

        $responseSekretaris = $this->actingAs($sekretaris)
            ->put('/admin/pengurus/update/' . $pengurus->id, [
                'nama' => 'Leon Baru',
                'nik' => '12345678901',
                'no_telp' => '0893467346308934673463',
                'role_id' => 123,
            ]);

        $responseSekretaris->assertSessionHasErrors([
            'nik' => 'The nik field must be 16 digits.',
            'no_telp' => 'The no telp field must not be greater than 20 characters.',
            'role_id' => 'The selected role id is invalid.',
        ]);

        $this->assertDatabaseMissing('pengguna', [
            'id' => $pengurus->id,
            'nama' => 'Leon Baru',
            'nik' => '1234567890123456',
            'no_telp' => '0893467346308934673463'
        ]);
    });

    it('Selain Sekretaris tidak dapat mengubah data pengurus', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        $role = Role::where('name', 'Staf Murabahah')->first();

        $pengurus = Pengguna::factory(['nama' => 'Nama Lama', 'status' => 'Aktif'])->create();
        $pengurus->assignRole('Staf Murabahah');

        $responseAnggota = $this->actingAs($anggota)
            ->put('/admin/pengurus/update/' . $pengurus->id, [
                'nama' => 'Leon Baru',
                'nik' => '1234567890123456',
                'no_telp' => '08934673463',
                'role_id' => $role->id,
            ]);

        $responseAnggota->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan registrasi anggota baru KSB oleh sekretaris.', function () {

    it('Sekretaris dapat mendaftarkan anggota baru dan status otomatis Menunggu Pembayaran', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Sekretaris');

        $res = $this->actingAs($user)
            ->post('/admin/users/store', [
                'nama' => 'Leon S Kennedy',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'email' => 'leon@example.com',
                'password' => 'password',
                'alamat_domisili' => 'Jl. Ennerdale No. 123',
                'pendidikan_terakhir' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'nik' => '1234567890123456',
                'no_telp' => '081234567890',
                'nik_ahli_waris' => '6543210987654321',
                'nama_ahli_waris' => 'Ada Wong',
                'heir_hubungan' => 'Istri',
                'kontak_ahli_waris' => '081234567891',
            ]);

        $res->assertSessionHasNoErrors();
        $res->assertStatus(302);

        $this->assertDatabaseHas('pengguna', [
            'nama' => 'Leon S Kennedy',
            'email' => 'leon@example.com',
        ]);

        $this->assertDatabaseHas('anggota', [
            'pengguna_id' => Pengguna::where('nik', '1234567890123456')->first()->id,
            'status' => 'Menunggu Pembayaran'
        ]);
    });

    it('Sekretaris tidak dapat mendaftarkan anggota baru dengan data yang tidak lengkap', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Sekretaris');

        $res = $this->actingAs($user)
            ->post('/admin/users/store', [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'heiger_nik' => '6543210987654321',
                'nama_ahli_waris' => 'Ada Wong',
                'heir_hubungan' => 'Istri',
                'kontak_ahli_waris' => '081234567891',
            ]);

            $res->assertSessionHasErrors([
                'nama' => 'The nama field is required.',
                'alamat_domisili' => 'The alamat domisili field is required.',
                'pendidikan_terakhir' => 'The pendidikan terakhir field is required.',
                'nik' => 'The nik field is required.',
                'no_telp' => 'The no telp field is required.',
            ]);

            $this->assertDatabaseMissing('anggota', [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin'
                ]
            );
    });

    it('Selain Sekretaris tidak dapat melakukan registrasi anggota baru', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $res = $this->actingAs($anggota)
            ->post('/admin/users/store', [
                'nama' => 'Leon S Kennedy',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'email' => 'leon@example.com',
                'password' => 'password',
                'alamat_domisili' => 'Jl. Ennerdale No. 123',
                'pendidikan_terakhir' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'nik' => '1234567890123456',
                'no_telp' => '081234567890',
                'nik_ahli_waris' => '6543210987654321',
                'nama_ahli_waris' => 'Ada Wong',
                'heir_hubungan' => 'Istri',
                'kontak_ahli_waris' => '081234567891',
            ]);

        $res->assertStatus(403);
        $this->assertDatabaseMissing('pengguna', [
            'nama' => 'Leon S Kennedy',
            'email' => 'leon@example.com',
        ]);
    });
});

describe('Aplikasi harus menyediakan daftar anggota untuk ketua koperasi dan sekretaris.', function () {
    it('Ketua dan Sekretaris dapat melihat daftar anggota', function () {
        $ketua = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $resAnggotabyKetua = $this->actingAs($ketua)->get('/admin/users');
        $resAnggotabySekretaris = $this->actingAs($sekretaris)->get('/admin/users');

        $resAnggotabyKetua->assertStatus(200);
        $resAnggotabySekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat daftar anggota', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $resAnggota = $this->actingAs($anggota)->get('/admin/users');

        $resAnggota->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan detail informasi masing-masing anggota.', function () {
    it('Ketua dan Sekretaris dapat melihat detail informasi anggota', function () {
        $ketua = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $resKetua = $this->actingAs($ketua)->get('/admin/users/show/' . $anggota->id);
        $resSekretaris = $this->actingAs($sekretaris)->get('/admin/users/show/' . $anggota->id);

        $resKetua->assertStatus(200);
        $resSekretaris->assertStatus(200);
    });

    it('Selain Ketua dan Sekretaris tidak dapat melihat detail informasi anggota', function () {
        $anggota1 = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota1->assignRole('Anggota');

        $anggota2 = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota2->assignRole('Anggota');

        $res = $this->actingAs($anggota1)->get('/admin/users/show/' . $anggota2->id);

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pembaruan data anggota oleh sekretaris.', function () {
    it('Sekretaris dapat mengubah data anggota', function () {
        $sekretaris = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = Pengguna::factory([
            'status' => 'Aktif',
            'nama' => 'Leona S Kennedy',
            'nik' => '3214567890123456',
            'no_telp' => '08934673463'
        ])->create();
        $anggota->assignRole('Anggota');

        $responseSekretaris = $this->actingAs($sekretaris)
            ->put('/admin/users/' . $anggota->id . '/update', [
                'nama' => 'Leon S Kennedy',
                'nik' => '1234567890123456',
                'no_telp' => '628934673463',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'alamat_domisili' => 'Jl. Ennerdale No. 123',
                'pendidikan_terakhir' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'ahli_waris[0][nik_ahli_waris]' => '6543210987654321',
                'ahli_waris[0][nama_ahli_waris]' => 'Ada Wong',
                'ahli_waris[0][hubungan]' => 'Istri',
                'ahli_waris[0][kontak_ahli_waris]' => '081234567891',
            ]);

        $responseSekretaris->assertStatus(302);
        $this->assertDatabaseHas('pengguna', [
            'id' => $anggota->id,
            'nama' => 'Leon S Kennedy',
            'nik' => '1234567890123456',
            'no_telp' => '628934673463',
        ]);
    });

    it('Selain Sekretaris tidak dapat mengubah data anggota', function () {
        $anggota1 = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota1->assignRole('Anggota');

        $anggota2 = Pengguna::factory([
            'status' => 'Aktif',
            'nama' => 'Nama Lama'
        ])->create();
        $anggota2->assignRole('Anggota');

        $responseAnggota = $this->actingAs($anggota1)
            ->put('/admin/users/' . $anggota2->id . '/update', [
                'nama' => 'Leon S Kennedy',
                'nik' => '1234567890123456',
                'no_telp' => '628934673463',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'alamat_domisili' => 'Jl. Ennerdale No. 123',
                'pendidikan_terakhir' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
                'ahli_waris[0][nik_ahli_waris]' => '6543210987654321',
                'ahli_waris[0][nama_ahli_waris]' => 'Ada Wong',
                'ahli_waris[0][hubungan]' => 'Istri',
                'ahli_waris[0][kontak_ahli_waris]' => '081234567891',
            ]);

        $responseAnggota->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan informasi profil bagi masing-masing anggota.', function () {
    it('Anggota dapat melihat informasi profilnya sendiri', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $res = $this->actingAs($anggota)->get('/user/profile');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('User/Profile/Show')
                ->has('user')
        );
    });

    it('Selain Anggota tidak dapat melihat informasi profil anggota', function () {
        $pengurus = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $pengurus->assignRole('Sekretaris');

        $res = $this->actingAs($pengurus)->get('/user/profile');

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pembaruan informasi profil bagi masing-masing anggota.', function () {
    it('Anggota dapat memperbarui informasi profilnya sendiri', function () {
        $anggota = Pengguna::factory([
            'status' => 'Aktif',
            'nama' => 'Leona S Kennedy',
            'no_telp' => '081234567891'
        ])->create();
        $anggota->assignRole('Anggota');

        $res = $this->actingAs($anggota)
            ->put('/user/profile', [
                'nama' => 'Leon S Kennedy',
                'nik' => '1234567890123456',
                'no_telp' => '0987654321',
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('pengguna', [
            'id' => $anggota->id,
            'nama' => 'Leon S Kennedy',
            'nik' => $anggota->nik,
            'no_telp' => '0987654321',
        ]);
    });

    it('Selain Anggota tidak dapat memperbarui informasi profil anggota', function () {
        $pengurus = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $pengurus->assignRole('Sekretaris');

        $res = $this->actingAs($pengurus)
            ->put('/user/profile', [
                'nama' => 'Leon S Kennedy',
                'no_telp' => '081234567890',
                'alamat_domisili' => 'Jl. Ennerdale No. 123',
                'pendidikan_terakhir' => EducationEnum::DIPLOMA_IV_BACHELOR->value,
            ]);

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pengalokasian anggota ke penanggung jawab anggota oleh ketua koperasi.', function () {
    it('Ketua dapat mengalokasikan anggota ke penanggung jawab anggota', function () {
        $ketua = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $ketua->assignRole('Ketua');

        $pj = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $pj->assignRole('Penanggung Jawab Anggota');

        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');

        $anggota = Anggota::factory()->create([
            'pengguna_id' => $anggota->id,
            'status' => 'Aktif',
        ]);

        $res = $this->actingAs($ketua)
            ->post('/admin/allocation', [
                'pj_anggota_id' => $pj->id,
                'member_ids' => [$anggota->id],
            ]);

        $res->assertStatus(302);
        $this->assertDatabaseHas('anggota', [
            'id' => $anggota->id,
            'pj_anggota_id' => $pj->id,
        ]);
    });

    it('Selain Ketua tidak dapat mengalokasikan anggota ke penanggung jawab anggota', function () {
        $bendahara = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $bendahara->assignRole('Bendahara');

        $anggota = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $anggota->assignRole('Anggota');
        $anggota = Anggota::factory()->create([
            'pengguna_id' => $anggota->id,
            'status' => 'Aktif',
        ]);

        $res = $this->actingAs($bendahara)
            ->post('/admin/allocation', [
                'pj_anggota_id' => $bendahara->id,
                'member_ids' => [$anggota->id],
            ]);

        $res->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan riwayat poin yang sudah diperoleh anggota', function () {
    it('Anggota dapat melihat riwayat poin yang sudah diperoleh', function () {
        $user = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $user->assignRole('Anggota');
        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => 'Aktif',
        ]);

        $res = $this->actingAs($user)->get('/user/profile');

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'saldo' => 1000000,
        ]);

        $this->travelTo(now()->endOfMonth());

        $this->artisan('points:calculate-monthly-savings')
            ->assertSuccessful();

        $this->assertDatabaseHas('poin', [
            'pengguna_id' => $user->id,
            'jml_perolehan' => 10, // 1 poin per 100.000 saldo, total saldo 5.000.000 = 50 poin
        ]);

        $this->travelBack();

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('User/Profile/Show')
                ->has('user.points')
        );
    });
});

describe('Aplikasi harus menyediakan dashboard operasional yang menyajikan ringkasan dan visualisasi data transaksi sesuai dengan hak akses masing-masing peran.', function () {
    it('DPS dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Dewan Pengawas Syariah');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Pengawas dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Pengawas');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Ketua dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Ketua');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Bendahara dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Bendahara');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Sekretaris dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Sekretaris');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Ketua Murabahah dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Ketua Murabahah');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Staf Murabahah dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Staf Murabahah');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Penanggung Jawab Anggota dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Penanggung Jawab Anggota');

        $res = $this->actingAs($user)->get('/admin/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('Anggota dapat melihat dashboard dengan data transaksi yang sesuai', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Anggota');
        Anggota::factory()->create([
            'pengguna_id' => $user->id,
        ]);

        $res = $this->actingAs($user)->get('/user/dashboard');

        $res->assertStatus(200);
        $res->assertInertia(fn (AssertableInertia $page) =>
            $page->component('User/Dashboard')
                ->has('summary')
                ->has('tabungan')
        );
    });
});

describe('Aplikasi harus menyediakan pengajuan pengunduran diri keanggotaan oleh anggota aktif.', function () {
    it('Anggota aktif dapat mengajukan pengunduran diri dengan melampirkan dokumen yang diperlukan', function () {
        $anggota = Anggota::factory()->create([
            'status' => 'Aktif',
        ]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($user)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('resign.pdf'),
            ]);

        Log::info('Resignation submission response: ' . $res->getContent());

        $res->assertStatus(302);
        $this->assertDatabaseHas('anggota', [
            'pengguna_id' => $user->id,
            'status' => 'Pengunduran Diri Diajukan',
        ]);
    });

    it('Anggota yang sudah mengajukan pengunduran diri tidak dapat mengajukan lagi', function () {
        $user = Pengguna::factory()->create();
        $user->assignRole('Anggota');
        Anggota::factory()->create([
            'pengguna_id' => $user->id,
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
        $user = Pengguna::factory([
            'status' => 'Aktif'
        ])->create();
        $user->assignRole('Anggota');
        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => 'Aktif',
        ]);

        Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'harga_perolehan' => 1000000,
            'margin_keuntungan' => 100000,
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

describe('Aplikasi harus menyediakan verifikasi permohonan pengunduran diri anggota oleh ketua koperasi.', function () {
    it('Ketua dapat memproses permohonan pengunduran diri anggota', function () {
        $ketua = Pengguna::factory()->create();
        $ketua->assignRole('Ketua');

        $anggota = Anggota::factory()->create([
            'status' => 'Pengunduran Diri Diajukan',
        ]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($ketua)
            ->put('/admin/resignations/' . $user->id);

        $res->assertStatus(302);
        $this->assertDatabaseHas('anggota', [
            'pengguna_id' => $user->id,
            'status' => 'Mengundurkan Diri',
        ]);
    });

    it('Selain Ketua tidak dapat memproses permohonan pengunduran diri anggota', function () {
        $sekretaris = Pengguna::factory()->create();
        $sekretaris->assignRole('Sekretaris');

        $anggota = Anggota::factory()->create([
            'status' => 'Pengunduran Diri Diajukan',
        ]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->assignRole('Anggota');

        $res = $this->actingAs($sekretaris)
            ->put('/admin/resignations/' . $user->id);

        $res->assertStatus(403);
    });
});

