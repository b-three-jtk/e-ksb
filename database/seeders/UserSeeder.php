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
    }
}
