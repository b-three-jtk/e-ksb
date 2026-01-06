<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkUnit;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $work_units = WorkUnit::all()->map(fn($unit) => [
            'id' => $unit->id,
            'name' => $unit->name
        ]);
        $roles = Role::all()->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name
        ]);

        return inertia('Admin/Admins/Create', [
            'work_units' => $work_units,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            User::create([
                ...$data,
                'password' => bcrypt('Password123'),
                'status' => UserStatus::ACTIVE->value,
            ]);

            DB::commit();
            return redirect()->route('admin.dashboard')->with('success', 'Admin berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menambahkan admin: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $admin = User::with('role', 'workUnit')->findOrFail($id);
        return inertia('Admin/Admins/Show', [
            'user' => $admin,
            'id' => $id,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $admin = User::with('role', 'workUnit')->findOrFail($id);
        $work_units = WorkUnit::all()->map(fn($unit) => [
            'id' => $unit->id,
            'name' => $unit->name
        ]);
        $roles = Role::all()->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name
        ]);

        return inertia('Admin/Admins/Edit', [
            'admin' => $admin,
            'work_units' => $work_units,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $admin = User::findOrFail($id);
            $admin->update($data);
            DB::commit();

            return redirect()->route('admin.dashboard')->with('success', 'Admin berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui admin: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function list(Request $request)
    {
        $admins = User::with('role')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name', 'like', "%{$request->search}%")
                       ->orWhere('nik', 'like', "%{$request->search}%")
                       ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->role, function ($q) use ($request) {
                $q->whereHas('role', function ($r) use ($request) {
                    $r->where('name', $request->role);
                });
            })
            ->paginate($request->perPage ?? 10)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'nik' => $user->nik,
                    'name' => $user-> name,
                    'email' => $user->email,
                    'posisi' => $user->role->name,
                    'status' => $user->status,
                    'avatar' => $user->profile_picture
                        ? asset('storage/' . $user->profile_picture)
                        : 'https://i.pravatar.cc/40?u=' . $user->id,
                ];
            });

        $roles = Role::whereHas('users')->pluck('name');

        return inertia('Admin/Admins/List', [
            'admins' => $admins,
            'roles' => $roles,
            'filters' => $request->only(['search', 'status', 'role', 'perPage']),
            'title' => 'Pengelolaan Admin'
        ]);
    }
}
