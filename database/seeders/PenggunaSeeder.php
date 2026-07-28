<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
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
            'nama' => 'Administrator Sistem',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatusEnum::ACTIVE->value,
            'no_telp' => '081234567800',
        ]);
        $adminSistem->assignRole(UserRoleEnum::ADMIN->value);
    }
}
