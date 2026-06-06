<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['name', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'name';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $roles = Role::with('permissions')
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permission_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                ];
            });

        return inertia('Admin/Roles/List', [
            'roles' => $roles,
            'filters' => $request->only(['search', 'per_page', 'sort_by', 'sort_dir']),
            'title' => 'Peran dan Akses',
        ]);
    }

    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all()
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

        return inertia('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ],
            'permissions' => $permissions,
            'title' => 'Edit Peran dan Akses',
        ]);
    }

    public function update(Request $request, string $id)
    {
        $allowedPermissionIds = Permission::pluck('id')->toArray();

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'in:' . implode(',', $allowedPermissionIds)],
        ]);

        $role = Role::findOrFail($id);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Hak akses peran berhasil diperbarui.');
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
