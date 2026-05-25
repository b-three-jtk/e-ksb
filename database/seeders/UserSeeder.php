<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Member::factory()->count(100)->create();

        // SIMULATION FOR DEFAULT USERS
        $dps = User::create([
            'user_code' => 'KSP0231',
            'nik' => '0000000099',
            'name' => 'DPS',
            'email' => 'dps@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081234567890',
        ]);
        $dps->assignRole(UserRoleEnum::DPS->value);

        $pengawas = User::create([
            'user_code' => 'KSP0897',
            'nik' => '0000000000000001',
            'name' => 'Pengawas',
            'email' => 'pengawas@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081234567893',
        ]);
        $pengawas->assignRole(UserRoleEnum::PENGAWAS->value);

        $ketua = User::create([
            'user_code' => 'KSP001',
            'nik' => '1234567890123456',
            'name' => 'Ketua',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081234566290',
        ]);
        $ketua->assignRole(UserRoleEnum::KETUA->value);

        $anggota = User::create([
            'user_code' => 'KSP002',
            'nik' => '6543210987654321',
            'name' => 'Anggota',
            'email' => 'contactsims11@gmail.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081234568590',
            'joined_date' => now()->subDays(30),
        ]);
        $anggota->assignRole(UserRoleEnum::ANGGOTA->value);
        Member::factory()->create([
            'user_id' => $anggota->id,
        ]);

        $sekretaris = User::create([
            'user_code' => 'KSP003',
            'nik' => '1122334455667788',
            'name' => 'Sekretaris',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081234562490',
        ]);
        $sekretaris->assignRole(UserRoleEnum::SEKRETARIS->value);

        $bendahara = User::create([
            'user_code' => 'KSP004',
            'nik' => '8877665544332211',
            'name' => 'Bendahara',
            'email' => 'bendahara@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '0812387567890',
        ]);
        $bendahara->assignRole(UserRoleEnum::BENDAHARA->value);

        $ketuaMurabahah = User::create([
            'user_code' => 'KSP005',
            'nik' => '1234432112344321',
            'name' => 'Ketua Murabahah',
            'email' => 'ketuamurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081232327890',
        ]);
        $ketuaMurabahah->assignRole(UserRoleEnum::KETUAMURABAHAH->value);

        $stafMurabahah = User::create([
            'user_code' => 'KSP045',
            'nik' => '1234432112344391',
            'name' => 'Staf Murabahah',
            'email' => 'seksimurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '081232827890',
        ]);
        $stafMurabahah->assignRole(UserRoleEnum::STAFMURABAHAH->value);

        $pjAnggota = User::create([
            'user_code' => 'KSP007',
            'nik' => '5678123456781234',
            'name' => 'Penanggung Jawab Anggota',
            'email' => 'pjanggota@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'phone_number' => '08123412890',
        ]);
        $pjAnggota->assignRole(UserRoleEnum::PJANGGOTA->value);

    }
}
