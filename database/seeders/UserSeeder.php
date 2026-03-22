<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Database\Seeder;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(100)->create();
        User::create([
            'member_number' => 'KSP0231',
            'nik' => '0000000099',
            'name' => 'DPS',
            'email' => 'dps@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Dewan Pengawas Syariah')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP0897',
            'nik' => '0000000000000001',
            'name' => 'Pengawas',
            'email' => 'pengawas@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Pengawas')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP001',
            'nik' => '1234567890123456',
            'name' => 'Ketua',
            'email' => 'ketua@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Ketua')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP002',
            'nik' => '6543210987654321',
            'name' => 'Anggota',
            'email' => 'contactsims11@gmail.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Anggota')->first()->id,
            'joined_date' => now()->subDays(30),
        ]);
        User::create([
            'member_number' => 'KSP003',
            'nik' => '1122334455667788',
            'name' => 'Sekretaris',
            'email' => 'sekretaris@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Sekretaris')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP004',
            'nik' => '8877665544332211',
            'name' => 'Bendahara',
            'email' => 'bendahara@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Bendahara')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP005',
            'nik' => '1234432112344321',
            'name' => 'Seksi Murabahah',
            'email' => 'seksimurabah@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Seksi Murabahah')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP006',
            'nik' => '4321123443211234',
            'name' => 'Seksi AMDK',
            'email' => 'seksiamdk@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Seksi AMDK')->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP007',
            'nik' => '5678123456781234',
            'name' => 'Penanggung Jawab Anggota',
            'email' => 'pjanggota@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Penanggung Jawab Anggota')->first()->id,
        ]);
    }
}
