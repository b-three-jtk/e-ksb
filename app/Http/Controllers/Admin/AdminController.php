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
}
