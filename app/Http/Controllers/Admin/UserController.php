<?php

namespace App\Http\Controllers\Admin;

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
    public function show($id)
    {
        // dummy data user: {
        // name: String,
    //     nik: String,
    //     gender: String,
    //     birth_date: String,
    //     last_education: String,
    //     work_unit: String,
    //     institution: String,
    //     marital_status: String,
    //     spouse_name: String,
    //     dependents: Number,
    //     phone_number: String,
    //     email: String,
    //     address: String,
    //     residential_address: String,
    // },
    // heirs: {
    //     name: String,
    //     relationship: String,
    //     contact: String,
    // },
    // saving_accounts: {
    //     pokok: {
    //         balance: Number,
    //         last_updated: String,
    //         last_transaction: String,
    //         transaction_date: String,
    //     },
    //     wajib: {
    //         balance: Number,
    //         last_updated: String,
    //         last_transaction: String,
    //         transaction_date: String,
    //     },
    //     sukarela: {
    //         balance: Number,
    //         last_updated: String,
    //         last_transaction: String,
    //         transaction_date: String,
    //     },
    // },
        $user = [
            'name' => 'Asep Suhendar',
            'nik' => '123456789012345',
            'gender' => 'Laki-laki',
            'birth_date' => '1990-01-01',
            'last_education' => 'STRATA I',
            'work_unit' => 'JTK',
            'institution' => 'Politeknik Negeri Bandung',
            'marital_status' => 'Kawin',
            'spouse_name' => 'Siti Aminah',
            'dependents' => 2,
            'phone_number' => '081234567890',
            'email' => 'user@example.com',
            'address' => 'Jl. Merdeka No. 123, Bandung',
            'residential_address' => 'Jl. Sudirman No. 456, Bandung',
        ];
        $heirs = [
            [
                'name' => 'Siti Aminah',
                'relationship' => 'Istri',
                'contact' => '081298765432',
            ]
        ];
        $saving_accounts = [
            'pokok' => [
                'balance' => 100000,
                'last_updated' => '2024-06-01',
                'last_transaction' => 'Setoran Simpanan Pokok',
                'transaction_date' => '2024-06-01',
            ],
            'wajib' => [
                'balance' => 500000,
                'last_updated' => '2024-06-10',
                'last_transaction' => 'Setoran Simpanan Wajib',
                'transaction_date' => '2024-06-10',
            ],
            'sukarela' => [
                'balance' => 2000000,
                'last_updated' => '2024-06-15',
                'last_transaction' => 'Setoran Simpanan Sukarela',
                'transaction_date' => '2024-06-15',
            ],
        ];
        return inertia('Admin/User/Show', [
            'user' => $user,
            'heirs' => $heirs,
            'saving_accounts' => $saving_accounts,
            'id' => $id,
        ]);
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
        $perPage = $request->input('per_page', 10);

        $members = User::query()
            ->where('status', 'Dalam Peninjauan')
            ->with('workUnit:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->work_unit_id, function ($query, $unitId) {
                $query->where('work_unit_id', $unitId);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'unit_kerja' => $user->workUnit?->name ?? '-',
            ]);

        return Inertia::render('Admin/User/ProspectiveMembers', [
            'prospectiveMembers' => $members,
            'filters' => [
                'search' => $request->search,
                'work_unit_id' => $request->work_unit_id,
                'per_page' => $perPage,
            ],
            'workUnits' => WorkUnit::select('id', 'name')->get(),
            'title' => 'Verifikasi Calon Anggota',
        ]);
    }
}
