<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkUnit;
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
            'member_number' => 'KSP001',
            'nik' => '1234567890123456',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'institution' => 'KopSy Campus',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Admin')->first()->id,
            'work_unit_id' => WorkUnit::inRandomOrder()->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP002',
            'nik' => '6543210987654321',
            'name' => 'Anggota',
            'email' => 'contactsims11@gmail.com',
            'institution' => 'KopSy Campus',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Anggota')->first()->id,
            'work_unit_id' => WorkUnit::inRandomOrder()->first()->id,
        ]);
        User::create([
            'member_number' => 'KSP003',
            'nik' => '1122334455667788',
            'name' => 'Manajer',
            'email' => 'manajer@example.com',
            'institution' => 'KopSy Campus',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVE->value,
            'role_id' => Role::where('name', 'Manajer')->first()->id,
            'work_unit_id' => WorkUnit::inRandomOrder()->first()->id,
        ]);
    }
}
