<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkUnit;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['name', 'created_at', 'email'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'created_at';

        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $allowedAdminStatuses = [
            'Aktif',
            'Tidak Aktif',
            'Mengundurkan Diri',
        ];

        $admins = User::with('role')
            ->whereHas(
                'role',
                fn($q) =>
                $q->where('name', '!=', 'Anggota')
            )
            ->whereIn('status', $allowedAdminStatuses)
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name', 'like', "%{$request->search}%")
                        ->orWhere('nik', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when(
                $request->status && in_array($request->status, $allowedAdminStatuses),
                fn($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->role,
                fn($q) =>
                $q->whereHas(
                    'role',
                    fn($r) =>
                    $r->where('name', $request->role)
                )
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'nik' => $user->nik,
                'name' => $user->name,
                'email' => $user->email,
                'posisi' => $user->role->name,
                'status' => $user->status,
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);

        return inertia('Admin/Admins/List', [
            'admins' => $admins,
            'roles' => Role::whereHas('users')
                ->where('name', '!=', 'Anggota')
                ->pluck('name'),
            'filters' => $request->only(['search', 'status', 'role', 'per_page', 'sort_by', 'sort_dir']),
            'title' => 'Pengelolaan Admin'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $work_units = WorkUnit::all();
        $roles = Role::where('name', '!=', 'Anggota')->get();

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
                'member_number' => 'KSP' . $data['role_id'] . $data['work_unit_id'] . (User::count() + 1),
                'password' => bcrypt('Password123'),
                'status' => UserStatus::ACTIVE->value,
            ]);

            DB::commit();

            return redirect()->route('admin.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $admin = User::with('role', 'workUnit')->findOrFail($id);

        $admin->profile_picture = $admin->profile_picture
            ? asset('storage/' . $admin->profile_picture)
            : null;

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
        $work_units = WorkUnit::all();
        $roles = Role::where('name', '!=', 'Anggota')->get();

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
            return redirect()->route('admin.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput();
        }
    }
}
