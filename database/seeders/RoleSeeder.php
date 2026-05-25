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
            'anggota' => ['view', 'create', 'edit'],
            'pengunduran_diri' => ['view', 'create', 'edit'],
            'pengurus' => ['view', 'create', 'edit'],
            'murabahah' => ['view', 'create', 'edit', 'approve'],
            'simpanan' => ['view', 'create', 'edit'],
            'kas' => ['view', 'create', 'edit'],
            'pengaturan' => ['view', 'create', 'edit'],
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
                    $role->givePermissionTo(['view_anggota', 'view_pengunduran_diri', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'view_kas', 'view_pengaturan']);
                    break;
                case UserRoleEnum::PENGAWAS:
                    $role->givePermissionTo(['view_anggota', 'view_pengunduran_diri', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'view_kas', 'view_pengaturan']);
                    break;
                case UserRoleEnum::KETUA:
                    $role->givePermissionTo(['view_anggota', 'view_pengunduran_diri', 'view_pengurus', 'view_murabahah', 'view_simpanan', 'view_kas', 'view_pengaturan', 'edit_pengunduran_diri', 'create_pengaturan', 'edit_pengaturan', 'approve_murabahah']);
                    break;
                case UserRoleEnum::SEKRETARIS:
                    $role->givePermissionTo(['create_anggota', 'view_anggota', 'edit_anggota', 'create_pengurus', 'view_pengurus', 'edit_pengurus', 'view_pengaturan']);
                    break;
                case UserRoleEnum::BENDAHARA:
                    $role->givePermissionTo(['view_simpanan', 'view_murabahah', 'create_kas', 'view_kas', 'edit_kas', 'view_pengaturan']);
                    break;
                case UserRoleEnum::KETUAMURABAHAH:
                    $role->givePermissionTo(['view_murabahah', 'approve_murabahah']);
                    break;
                case UserRoleEnum::STAFMURABAHAH:
                    $role->givePermissionTo(['view_murabahah', 'create_murabahah', 'edit_murabahah']);
                    break;
                case UserRoleEnum::PJANGGOTA:
                    $role->givePermissionTo(['view_anggota', 'create_simpanan', 'view_simpanan', 'edit_simpanan']);
                    break;
                default:
                    // Role lain tidak diberikan permission khusus
                    break;
                }
        }
    }
}
