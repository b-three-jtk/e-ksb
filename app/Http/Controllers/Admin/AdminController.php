<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            UserStatusEnum::ACTIVE->value,
            UserStatusEnum::INACTIVE->value,
        ];

        $admins = User::with('role')
            ->whereHas('role', fn ($q) =>
                $q->whereNotIn('role_name', [UserRoleEnum::ANGGOTA->value])
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
            ->when($request->role && !in_array($request->role, [UserRoleEnum::ANGGOTA->value]),
                fn ($q) =>
                    $q->whereHas('role', fn ($r) =>
                        $r->where('role_name', $request->role)
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
                'posisi' => $user->role->role_name,
                'status' => $user->status,
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);

        return inertia('Admin/Admins/List', [
            'admins' => $admins,
            'roles' => Role::whereHas('users')
                ->whereNotIn('role_name', [UserRoleEnum::ANGGOTA->value])
                ->pluck('role_name'),
            'filters' => $request->only(['search', 'status', 'role', 'per_page', 'sort_by', 'sort_dir']),
            'title' => 'Pengelolaan Admin'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::where('role_name', '!=', UserRoleEnum::ANGGOTA->value)->get();
        $educations = array_column(EducationEnum::cases(), 'value');

        return inertia('Admin/Admins/Create', [
            'roles' => $roles,
            'educations' => $educations,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request)
    {
        DB::begin();
        try {
            $data = $request->validated();

            User::create([
                ...$data,
                'member_code' => 'KSP' . $data['role_id'] . (User::count() + 1),
                'password' => bcrypt('Password123'),
                'status' => UserStatusEnum::ACTIVE->value,
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
        $admin = User::with('role')->findOrFail($id);

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
        $admin = User::with('role')->findOrFail($id);
        $roles = Role::where('role_name', '!=', UserRoleEnum::ANGGOTA->value)->get();
        $educations = array_column(EducationEnum::cases(), 'value');

        return inertia('Admin/Admins/Edit', [
            'admin' => $admin,
            'roles' => $roles,
            'educations' => $educations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, string $id)
    {
        DB::begin();
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
