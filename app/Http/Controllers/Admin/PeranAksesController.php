<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PeranAksesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Enums\UserRoleEnum;

class PeranAksesController extends Controller
{
    public function __construct(private PeranAksesService $peranAksesService)
    {
    }

    public function index(Request $request)
    {
        $allowedSorts = ['name', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'name';
        $sortDir = $request->sort_dir === 'desc' ? 'desc' : 'asc';

        $roles = $this->peranAksesService->getRolesPaginated(
            $request->search,
            $sortBy,
            $sortDir,
            $request->per_page ?? 10
        )->through(function ($role) {
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

    public function show(string $id)
    {
        $role = $this->peranAksesService->getRoleWithPermissions($id);
        $permissions = $this->peranAksesService->getGroupedPermissions();

        return inertia('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ],
            'permissions' => $permissions,
            'title' => 'Detail Peran dan Akses',
            'readonly' => true,
        ]);
    }

    public function edit(string $id)
    {
        $role = $this->peranAksesService->getRoleWithPermissions($id);
        $permissions = $this->peranAksesService->getGroupedPermissions();

        return inertia('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ],
            'permissions' => $permissions,
            'title' => 'Edit Peran dan Akses',
            'readonly' => false,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $allowedPermissionIds = Permission::pluck('id')->toArray();

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'in:' . implode(',', $allowedPermissionIds)],
        ], [
            'name.unique' => 'Nama peran sudah digunakan, silakan gunakan nama lain.',
        ]);

        $this->peranAksesService->updateRole($id, $data);

        return redirect()->route('admin.roles.index')->with('success', 'Hak akses peran berhasil diperbarui.');
    }

    public function create()
    {
        $permissions = $this->peranAksesService->getGroupedPermissions();

        return inertia('Admin/Roles/Create', [
            'permissions' => $permissions,
            'title' => 'Buat Peran dan Akses',
        ]);
    }

    public function store(Request $request)
    {
        $allowedPermissionIds = Permission::pluck('id')->toArray();

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'in:' . implode(',', $allowedPermissionIds)],
        ], [
            'name.unique' => 'Nama peran sudah digunakan, silakan gunakan nama lain.',
        ]);

        $this->peranAksesService->storeRole($data);

        return redirect()->route('admin.roles.index')->with('success', 'Peran dan hak akses baru berhasil dibuat.');
    }
}
