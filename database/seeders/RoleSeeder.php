<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = UserRoleEnum::cases();

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['role_name' => $role->value],
                ['role_name' => $role->value]
            );
        }
    }
}
