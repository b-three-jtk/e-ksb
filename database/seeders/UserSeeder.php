<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $work_unit = WorkUnit::create(['name' => 'Jurusan Akuntansi']);
        User::create([
            'member_number' => 'KS001',
            'nik' => '0000000001',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'institution' => 'Polban',
            'status' => 'Aktif',
            'role_id' => $role->id,
            'work_unit_id' => $work_unit->id,
        ]);
    }
}
