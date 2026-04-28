<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\Role;
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
        User::create([
            'user_code' => 'KSP0231',
            'nik' => '0000000099',
            'name' => 'DPS',
            'email' => 'dps@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Dewan Pengawas Syariah')->first()->id,
            'phone_number' => '081234567890',
        ]);
        User::create([
            'user_code' => 'KSP0897',
            'nik' => '0000000000000001',
            'name' => 'Pengawas',
            'email' => 'pengawas@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Pengawas')->first()->id,
            'phone_number' => '081234567893',
        ]);
        User::create([
            'user_code' => 'KSP001',
            'nik' => '1234567890123456',
            'name' => 'Ketua',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Ketua')->first()->id,
            'phone_number' => '081234566290',
        ]);
        $anggota = User::create([
            'user_code' => 'KSP002',
            'nik' => '6543210987654321',
            'name' => 'Anggota',
            'email' => 'contactsims11@gmail.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Anggota')->first()->id,
            'phone_number' => '081234568590',
            'joined_date' => now()->subDays(30),
        ]);
        Member::factory()->create([
            'user_id' => $anggota->id,
        ]);
        User::create([
            'user_code' => 'KSP003',
            'nik' => '1122334455667788',
            'name' => 'Sekretaris',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Sekretaris')->first()->id,
            'phone_number' => '081234562490',
        ]);
        User::create([
            'user_code' => 'KSP004',
            'nik' => '8877665544332211',
            'name' => 'Bendahara',
            'email' => 'bendahara@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Bendahara')->first()->id,
            'phone_number' => '0812387567890',
        ]);
        User::create([
            'user_code' => 'KSP005',
            'nik' => '1234432112344321',
            'name' => 'Seksi Murabahah',
            'email' => 'seksimurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Ketua Murabahah')->first()->id,
            'phone_number' => '081232327890',
        ]);
        User::create([
            'user_code' => 'KSP006',
            'nik' => '4321123443211234',
            'name' => 'Seksi AMDK',
            'email' => 'seksiamdk@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Ketua AMDK')->first()->id,
            'phone_number' => '081238667890',
        ]);
        User::create([
            'user_code' => 'KSP007',
            'nik' => '5678123456781234',
            'name' => 'Penanggung Jawab Anggota',
            'email' => 'pjanggota@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'role_id' => Role::where('role_name', 'Penanggung Jawab Anggota')->first()->id,
            'phone_number' => '08123412890',
        ]);
    }
}
