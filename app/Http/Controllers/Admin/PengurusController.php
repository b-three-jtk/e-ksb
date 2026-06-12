<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Services\Admin\PengurusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class PengurusController extends Controller
{
    public function __construct(private PengurusService $pengurusService){}

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

        $admins = $this->pengurusService->getSemuaPengurus($request, $sortBy, $sortDir);

        return inertia('Admin/Admins/List', [
            'admins' => $admins,
            'roles' => Role::whereHas('users')
                ->whereNotIn('name', [UserRoleEnum::ANGGOTA->value])
                ->pluck('name'),
            'filters' => $request->only(['search', 'status', 'role', 'per_page', 'sort_by', 'sort_dir']),
            'title' => 'Pengelolaan Admin',
            'can' => [
                'tambah_pengurus' => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
                'edit_pengurus' => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Admins/Create', [
            'roles' => $this->pengurusService->getSemuaPeran(),
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

            $this->pengurusService->storePengurus($data);

            DB::commit();

            return redirect()->route('admin.admin.index')->withInput()->with('success', 'Pengurus berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing admin: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal menambahkan pengurus. Silakan coba lagi.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return inertia('Admin/Admins/Show', [
            'user' => $this->pengurusService->getPengurusById($id),
            'id' => $id,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return inertia('Admin/Admins/Edit', [
            'admin' => $this->pengurusService->getPengurusById($id),
            'roles' => $this->pengurusService->getSemuaPeran(),
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

            $admin = $this->pengurusService->getPengurusById($id);
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

        return response()->json(['members' => $this->pengurusService->searchAnggotaAktif($query)]);
    }
}
