<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
        // 1. Reset cache roles dan permissions (penting!)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'anggota' => ['view', 'create', 'edit', 'delete'],
            'pengurus' => ['view', 'create', 'edit', 'delete'],
            'murabahah' => ['view', 'create', 'edit', 'delete', 'approve'],
            'simpanan' => ['view', 'deposit', 'withdraw'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$module}",
                    'guard_name' => 'web'
                ]);
            }
        }

        foreach (UserRoleEnum::cases() as $case) {
            $role = Role::create([
                'name' => $case->value,
                'guard_name' => 'web'
            ]);

            // Assign permissions berdasarkan role
            switch ($case) {
                case UserRoleEnum::DPS:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus']);
                    break;
                case UserRoleEnum::PENGAWAS:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus', 'view_murabahah', 'view_simpanan']);
                    break;
                case UserRoleEnum::KETUA:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'approve_murabahah']);
                    break;
                case UserRoleEnum::SEKRETARIS:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'create_anggota', 'edit_anggota', 'delete_anggota']);
                    break;
                case UserRoleEnum::BENDAHARA:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'deposit_simpanan', 'withdraw_simpanan']);
                    break;
                case UserRoleEnum::ANGGOTA:
                    $role->givePermissionTo(['view_murabahah', 'view_simpanan']);
                    break;
                case UserRoleEnum::KETUAMURABAHAH:
                    $role->givePermissionTo(['view_murabahah', 'approve_murabahah']);
                    break;
                case UserRoleEnum::STAFMURABAHAH:
                    $role->givePermissionTo(['view_murabahah', 'create_murabahah', 'edit_murabahah', 'delete_murabahah']);
                    break;
                case UserRoleEnum::KETUAAMDK:
                    $role->givePermissionTo(['view_simpanan', 'deposit_simpanan', 'withdraw_simpanan']);
                    break;
                case UserRoleEnum::PJANGGOTA:
                    $role->givePermissionTo(['view_anggota', 'view_pengurus']);
                    break;
                case UserRoleEnum::STOKIST:
                    $role->givePermissionTo(['view_murabahah']);
                    break;
                default:
                    // Role lain tidak diberikan permission khusus
                    break;
                }
        }
    }
}
