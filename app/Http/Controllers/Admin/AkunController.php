<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\AkunService;
use Inertia\Inertia;
use App\Enums\AccountCategoryEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Auth;

class AkunController extends Controller
{
    public function __construct(
        protected AkunService $akunService
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'search'    => $request->search,
            'jenis_akun'=> $request->jenis_akun,
            'status'    => $request->status,
            'per_page'  => $request->per_page ?? 10,
            'sort_by'   => $request->sort_by ?? 'nomor_akun',
            'sort_dir'  => $request->sort_dir ?? 'asc',
        ];

        return Inertia::render('Admin/Accounts/List', [
            'accounts' => $this->akunService->getAccountList($filters),

            'filters' => $filters,

            'jenisAkunOptions' => collect(AccountCategoryEnum::cases())
                ->map(fn ($item) => $item->value)
                ->values(),

            'accountSummary' => $this->akunService->getAccountSummary(),

            'can' => [
                'tambah_akun' => Auth::user()->hasRole(UserRoleEnum::BENDAHARA->value),
                'edit_akun'   => Auth::user()->hasRole(UserRoleEnum::BENDAHARA->value),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'nomor_akun' => ['required', 'regex:/^[0-9]+$/', 'unique:accounts,no_ref_account'],
                'nama_akun'  => ['required', 'string', 'max:255'],
                'jenis_akun' => ['required', new Enum(AccountCategoryEnum::class)],
            ],
            [
                'nomor_akun.required' => 'Nomor akun wajib diisi.',
                'nomor_akun.regex'    => 'Nomor akun hanya boleh berisi angka.',
                'nomor_akun.unique'   => 'Nomor akun sudah digunakan.',
                'nama_akun.required'  => 'Nama akun wajib diisi.',
                'nama_akun.max'       => 'Nama akun maksimal 255 karakter.',
                'jenis_akun.required' => 'Jenis akun wajib dipilih.',
                'jenis_akun.in'       => 'Jenis akun tidak valid.',
            ]
        );

        $this->akunService->createAccount($validated);

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Aktif,Non-Aktif',
        ]);

        $this->akunService->updateStatus($id, $request->status);

        return back();
    }
}
