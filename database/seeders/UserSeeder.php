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
        // 20 data users
        User::factory()->count(20)->create();
    }
}
