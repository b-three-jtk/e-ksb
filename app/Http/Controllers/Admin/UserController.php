<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkUnit;
use Inertia\Inertia;

class UserController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['role', 'workUnit', 'savingAccounts.transactions' => function($query) {$query->latest('created_at')->take(1);}, 'heirs', 'userDocs', 'financings.loan.payments'])->findOrFail($id);
        return inertia('Admin/User/Show', ['user' => $user]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function prospectiveMembers(Request $request)
    {
        $query = User::query()
            ->where('status', 'Dalam Peninjauan')
            ->with('workUnit:id,name')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->work_unit_id, function ($q, $unitId) {
                $q->where('work_unit_id', $unitId);
            })
            ->orderByDesc('created_at');

        $perPage = $request->input('per_page', 10);
        $members = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/User/ProspectiveMembers', [
            'prospectiveMembers' => $members,
            'filters' => $request->only(['search', 'work_unit_id', 'per_page']),
            'workUnits' => WorkUnit::select('id', 'name')->get(),
            'title' => 'Verifikasi Calon Anggota',
        ]);
    }
}
