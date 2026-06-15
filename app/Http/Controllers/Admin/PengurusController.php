<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Services\Admin\PengurusService;
use App\Services\Admin\PeranAksesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PengurusController extends Controller
{
    public function __construct(
        private PengurusService $pengurusService,
        private PeranAksesService $peranAksesService
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Admin/Admins/List', [
            'admins'  => $this->pengurusService->getSemuaPengurus($request),
            'roles'   => $this->peranAksesService->getPeranNamesWithUsers(),
            'filters' => $request->only(['search', 'status', 'role', 'per_page', 'sort_by', 'sort_dir']),
            'title'   => 'Pengelolaan Admin',
            'can'     => [
                'tambah_pengurus' => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
                'edit_pengurus'   => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Admins/Create', [
            'roles' => $this->peranAksesService->getSemuaPeran(),
            'members' => $this->pengurusService->getAnggotaAktif(),
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
            'roles' => $this->peranAksesService->getSemuaPeran(),
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
            $admin->update($data);
                $this->peranAksesService->syncUserRole($admin, $data['role_id']);
            DB::commit();
            return redirect()->route('admin.admin.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput();
        }
    }
}
