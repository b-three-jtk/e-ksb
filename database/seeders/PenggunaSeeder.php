<?php

namespace Database\Seeders;

use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
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
            'kode_pengguna' => 'KSB2605000',
            'nik' => '0000000000',
            'nama' => 'Administrator Sistem',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567800',
        ]);
        $adminSistem->assignRole(UserRoleEnum::ADMIN->value);

        $dps = Pengguna::create([
            'kode_pengguna' => 'KSB2605001',
            'nik' => '0000000099',
            'nama' => 'DPS',
            'email' => 'dps@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567890',
        ]);
        $dps->assignRole(UserRoleEnum::DPS->value);

        $pengawas = Pengguna::create([
            'kode_pengguna' => 'KSB2605002',
            'nik' => '0000000000000001',
            'nama' => 'Pengawas',
            'email' => 'pengawas@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567893',
        ]);
        $pengawas->assignRole(UserRoleEnum::PENGAWAS->value);

        $ketua = Pengguna::create([
            'kode_pengguna' => 'KSB2605003',
            'nik' => '1234567890123456',
            'nama' => 'Ketua',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234566290',
        ]);
        $ketua->assignRole(UserRoleEnum::KETUA->value);

        $anggota = Pengguna::create([
            'kode_pengguna' => 'KSB2605004',
            'nik' => '6543210987654321',
            'nama' => 'Anggota',
            'email' => 'raizelmaid@gmail.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234568590',
            'tgl_bergabung' => now()->subDays(30),
        ]);
        $anggota->assignRole(UserRoleEnum::ANGGOTA->value);
        Member::factory()->create([
            'pengguna_id' => $anggota->id,
        ]);

        $sekretaris = Pengguna::create([
            'kode_pengguna' => 'KSB2605005',
            'nik' => '1122334455667788',
            'nama' => 'Sekretaris',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234562490',
        ]);
        $sekretaris->assignRole(UserRoleEnum::SEKRETARIS->value);

        $bendahara = Pengguna::create([
            'kode_pengguna' => 'KSB2605006',
            'nik' => '8877665544332211',
            'nama' => 'Bendahara',
            'email' => 'bendahara@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '0812387567890',
        ]);
        $bendahara->assignRole(UserRoleEnum::BENDAHARA->value);

        $ketuaMurabahah = Pengguna::create([
            'kode_pengguna' => 'KSB2605007',
            'nik' => '1234432112344321',
            'nama' => 'Ketua Murabahah',
            'email' => 'ketuamurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081232327890',
        ]);
        $ketuaMurabahah->assignRole(UserRoleEnum::KETUAMURABAHAH->value);

        $stafMurabahah = Pengguna::create([
            'kode_pengguna' => 'KSB2605008',
            'nik' => '1234432112344391',
            'nama' => 'Staf Murabahah',
            'email' => 'seksimurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081232827890',
        ]);
        $stafMurabahah->assignRole(UserRoleEnum::STAFMURABAHAH->value);

        $pjAnggota = Pengguna::create([
            'kode_pengguna' => 'KSB2605009',
            'nik' => '5678123456781234',
            'nama' => 'Penanggung Jawab Anggota',
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
            Member::factory()->create([
                'pengguna_id' => $user->id,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        });

        // create 10-15 random pengurus
        $pengurusRoles = [
            UserRoleEnum::DPS->value,
            UserRoleEnum::PENGAWAS->value,
            UserRoleEnum::KETUA->value,
            UserRoleEnum::SEKRETARIS->value,
            UserRoleEnum::BENDAHARA->value,
            UserRoleEnum::KETUAMURABAHAH->value,
            UserRoleEnum::STAFMURABAHAH->value,
            UserRoleEnum::PJANGGOTA->value,
        ];
        
        $jumlahPengurus = rand(10, 15);
        Pengguna::factory($jumlahPengurus)->create()->each(function ($user) use ($getRandomDate, $pengurusRoles) {
            $randomDate = $getRandomDate();
            $user->update([
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
            $randomRole = $pengurusRoles[array_rand($pengurusRoles)];
            $user->assignRole($randomRole);
            
            Member::factory()->create([
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
            Member::factory()->create([
                'pengguna_id' => $user->id,
                'status' => MemberStatusEnum::RESIGNED_REQUESTED->value,
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        });

    }
}
