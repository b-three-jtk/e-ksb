<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;
use Spatie\Permission\Models\Role;

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

        $admins = User::with(['roles', 'member'])
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', [UserRoleEnum::ANGGOTA->value]);
            })
            ->whereIn('status', [
                UserStatusEnum::ACTIVE->value,
                UserStatusEnum::INACTIVE->value,
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name', 'like', "%{$request->search}%")
                        ->orWhere('nik', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status === 'Anggota', function ($q) {
                $q->whereHas('member');
            })
            ->when($request->status === 'Non Anggota', function ($q) {
                $q->whereDoesntHave('member');
            })
            ->when(
                $request->role,
                fn ($q) =>
                    $q->whereHas('roles', fn ($r) =>
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
                'posisi' => $user->getRoleNames()->first(),
                'status' => $user->member
                    ? 'Anggota'
                    : 'Non Anggota',

                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);

        return inertia('Admin/Admins/List', [
            'admins' => $admins,
            'roles' => Role::whereHas('users')
                ->whereNotIn('name', [UserRoleEnum::ANGGOTA->value])
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
        $roles = Role::where('name', '!=', UserRoleEnum::ANGGOTA->value)->get();
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
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (isset($data['user_id']) && $data['user_id']) {
                $user = User::findOrFail($data['user_id']);
                $role = Role::findOrFail($data['role_id']);

                $user->update([
                    'name' => $data['name'],
                    'nik' => $data['nik'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'],
                ]);

                $user->syncRoles([$role->name]);

                $user->save();

                DB::commit();
                return redirect()->route('admin.admin.index')->with('success', 'Pengurus berhasil ditambahkan dari member');
            } else {

                $user = User::create([
                    'name' => $data['name'],
                    'nik' => $data['nik'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'],
                    'user_code' => 'KSP' . now()->format('Ym') . str_pad(User::count() + 1, 4, '0', STR_PAD_LEFT),
                    'password' => bcrypt('Password123'),
                    'status' => UserStatusEnum::ACTIVE->value,
                ]);

                $role = Role::findOrFail($data['role_id']);
                $user->assignRole($role->name);
            }
            DB::commit();
            return redirect()->route('admin.admin.index')->withInput()->with('success', 'Admin berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing admin: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal menambahkan admin']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $admin = User::with('roles')->findOrFail($id);

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
        $admin = User::with('roles')->findOrFail($id);
        $roles = Role::where('name', '!=', UserRoleEnum::ANGGOTA->value)->get();
        $educations = array_column(EducationEnum::cases(), 'value');

        Log::info('admin data: ' . print_r($admin->toArray(), true));

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
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $admin = User::findOrFail($id);
            $role = Role::findOrFail($data['role_id']);

            $admin->update($data);
            $admin->syncRoles([$role->name]);
            DB::commit();
            return redirect()->route('admin.admin.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput();
        }
    }

    public function searchMember(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['members' => []]);
        }

        $members = User::with('member')
            ->whereHas('member', function ($q) {
                $q->where('status', MemberStatusEnum::ACTIVE->value);
            })
            ->whereHas('roles', function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('user_code', 'like', "%{$query}%")
                    ->orWhere('nik', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'user_code', 'name', 'nik', 'email', 'phone_number'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ];
            });

        return response()->json(['members' => $members]);
    }
}
