<?php

namespace App\Services\Admin;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PeranAksesService
{
    public function getSemuaPeran()
    {
        return Role::where('name', '!=', UserRoleEnum::ANGGOTA->value)->get();
    }

    public function getPeranNamesWithUsers()
    {
        return Role::whereHas('users')
            ->whereNotIn('name', [UserRoleEnum::ANGGOTA->value])
            ->pluck('name');
    }

    public function syncUserRole(User $user, int|string $roleId): void
    {
        $role = Role::findOrFail($roleId);
        $user->syncRoles([$role->name]);
    }

    public function assignRoleToUser(User $user, int|string $roleId): void
    {
        $role = Role::findOrFail($roleId);
        $user->assignRole($role->name);
    }

    public function getRolesPaginated(?string $search, string $sortBy, string $sortDir, int $perPage = 10)
    {
        return Role::with('permissions')
            ->where('name', '!=', UserRoleEnum::ANGGOTA->value)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getRoleWithPermissions(string $id): Role
    {
        $role = Role::with('permissions')->findOrFail($id);
        if ($role->name === UserRoleEnum::ANGGOTA->value) {
            abort(404);
        }
        return $role;
    }

    public function getGroupedPermissions()
    {
        return Permission::all()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $this->formatPermissionLabel($permission->name),
                    'group' => $this->permissionGroup($permission->name),
                ];
            })
            ->groupBy('group')
            ->map(function ($items) {
                return $items->values();
            });
    }

    public function storeRole(array $data): Role
    {
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        return $role;
    }

    public function updateRole(string $id, array $data): Role
    {
        $role = Role::findOrFail($id);
        if ($role->name === UserRoleEnum::ANGGOTA->value) {
            abort(404);
        }
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        return $role;
    }

    private function formatPermissionLabel(string $name): string
    {
        $parts = explode('_', $name);
        $action = array_shift($parts);
        $actionLabel = match ($action) {
            'view' => 'Lihat',
            'create' => 'Buat',
            'edit' => 'Ubah',
            'approve' => 'Setujui',
            default => ucfirst($action),
        };

        $module = implode(' ', array_map(function ($part) {
            return ucfirst(str_replace(['-', '_'], ' ', $part));
        }, $parts));

        return trim("{$actionLabel} {$module}");
    }

    private function permissionGroup(string $name): string
    {
        $parts = explode('_', $name);
        array_shift($parts);

        return ucfirst(implode(' ', array_map(function ($part) {
            return ucfirst(str_replace(['-', '_'], ' ', $part));
        }, $parts)));
    }
}
