<?php

namespace Database\Seeders;

use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Anggota;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DEFAULT USERS
        $adminSistem = Pengguna::create([
            'kode_pengguna' => 'KSB2607000',
            'nik' => '3273111508900001',
            'nama' => 'Asep Suhendar',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567800',
        ]);
        $adminSistem->assignRole(UserRoleEnum::ADMIN->value);

        $dps = Pengguna::create([
            'kode_pengguna' => 'KSB2607001',
            'nik' => '3273121005850002',
            'nama' => 'Ust. Ahmad Fauzi',
            'email' => 'dps@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567890',
        ]);
        $dps->assignRole(UserRoleEnum::DPS->value);

        $pengawas = Pengguna::create([
            'kode_pengguna' => 'KSB2607002',
            'nik' => '3273142203820001',
            'nama' => 'Heri Hermawan',
            'email' => 'pengawas@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567893',
        ]);
        $pengawas->assignRole(UserRoleEnum::PENGAWAS->value);

        $ketua = Pengguna::create([
            'kode_pengguna' => 'KSB2607003',
            'nik' => '3273151708780003',
            'nama' => 'Suwita',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234566290',
        ]);
        $ketua->assignRole(UserRoleEnum::KETUA->value);

        $anggota = Pengguna::create([
            'kode_pengguna' => 'KSB2607004',
            'nik' => '3273165209890001',
            'nama' => 'Popon Setyaningsih',
            'email' => 'popon@gmail.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234568590',
            'tgl_bergabung' => now()->subDays(30),
        ]);
        $anggota->assignRole(UserRoleEnum::ANGGOTA->value);
        Anggota::factory()->create([
            'pengguna_id' => $anggota->id,
        ]);

        $sekretaris = Pengguna::create([
            'kode_pengguna' => 'KSB2607005',
            'nik' => '3273116512920002',
            'nama' => 'Sri Wahyuni',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234562490',
        ]);
        $sekretaris->assignRole(UserRoleEnum::SEKRETARIS->value);

        $bendahara = Pengguna::create([
            'kode_pengguna' => 'KSB2607006',
            'nik' => '3273125804950001',
            'nama' => 'Rahayu',
            'email' => 'bendahara@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '0812387567890',
        ]);
        $bendahara->assignRole(UserRoleEnum::BENDAHARA->value);

        $ketuaMurabahah = Pengguna::create([
            'kode_pengguna' => 'KSB2607007',
            'nik' => '3273146011880004',
            'nama' => 'Siti Rahmawati',
            'email' => 'ketuamurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081232327890',
        ]);
        $ketuaMurabahah->assignRole(UserRoleEnum::KETUAMURABAHAH->value);

        $stafMurabahah = Pengguna::create([
            'kode_pengguna' => 'KSB2607008',
            'nik' => '3273154807960002',
            'nama' => 'Nur Afni',
            'email' => 'seksimurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081232827890',
        ]);
        $stafMurabahah->assignRole(UserRoleEnum::STAFMURABAHAH->value);

        $pjAnggota = Pengguna::create([
            'kode_pengguna' => 'KSB2607009',
            'nik' => '3273165502910001',
            'nama' => 'Yuli Astuti',
            'email' => 'pjanggota@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '08123412890',
        ]);
        $pjAnggota->assignRole(UserRoleEnum::PJANGGOTA->value);

        $startDate = Carbon::create(2017, 1, 1);
        $endDate = Carbon::create(2026, 12, 31);
        
        $getRandomDate = function () use ($startDate, $endDate) {
            $randomTimestamp = mt_rand($startDate->timestamp, $endDate->timestamp);
            return Carbon::createFromTimestamp($randomTimestamp);
        };

        // create 110 random users with Anggota role
        Pengguna::factory(110)->create()->each(function ($user) use ($getRandomDate) {
            $randomDate = $getRandomDate();
            $user->update([
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
            $user->assignRole(UserRoleEnum::ANGGOTA->value);
            Anggota::factory()->create([
                'pengguna_id' => $user->id,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        });

        // create 4 random PJ Anggota (total 5)
        Pengguna::factory(4)->create()->each(function ($user) use ($getRandomDate) {
            $randomDate = $getRandomDate();
            $user->update([
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
            $user->assignRole(UserRoleEnum::PJANGGOTA->value);
            
            Anggota::factory()->create([
                'pengguna_id' => $user->id,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        });

        Pengguna::factory(10)->create()->each(function ($user) use ($getRandomDate) {
            $randomDate = $getRandomDate();
            $user->update([
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
            $user->assignRole(UserRoleEnum::ANGGOTA->value);
            Anggota::factory()->create([
                'pengguna_id' => $user->id,
                'status' => MemberStatusEnum::RESIGNED_REQUESTED->value,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        });

    }
}
