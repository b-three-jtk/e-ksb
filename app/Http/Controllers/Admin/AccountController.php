<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::query();

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('no_ref_account', 'like', "%{$request->search}%")
                    ->orWhere('account_name', 'like', "%{$request->search}%");
            });
        }

        // Fiter kategori akun
        if ($request->filled('jenis_akun')) {
            $query->where('account_category', $request->jenis_akun);
        }

        // Filter status akun
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortby = match ($request->sort_by) {
            'nomor_akun' => 'no_ref_account',
            'nama_akun' => 'account_name',
            default => 'no_ref_account',
        };

        $sortDir = $request->sort_dir === 'desc' ? 'desc' : 'asc';

        $accounts = $query->orderBy($sortby, $sortDir)
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(fn ($akun) => [
                'id' => $akun->no_ref_account,
                'nomor_akun' => $akun->no_ref_account,
                'nama_akun' => $akun->account_name,
                'jenis_akun' => $akun->account_category,
                'status' => $akun->status,
            ]);

        return Inertia::render('Admin/Accounts/List', [
            'accounts' => $accounts,

            'filters' => [
                'search' => $request->search,
                'jenis_akun' => $request->jenis_akun,
                'status' => $request->status,
                'per_page' => $request->per_page ?? 10,
                'sort_by' => $request->sort_by ?? 'nomor_akun',
                'sort_dir' => $request->sort_dir ?? 'asc',
            ],

            'jenisAkunOptions' => [
                'Aset',
                'Liabilitas',
                'Ekuitas',
                'Pendapatan',
                'Beban',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'nomor_akun' => [
                    'required',
                    'regex:/^[0-9]+$/',
                    'unique:accounts,no_ref_account',
                ],
                'nama_akun' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'jenis_akun' => [
                    'required',
                    'in:Aset,Liabilitas,Ekuitas,Pendapatan,Beban',
                ],
            ],
            [
                'nomor_akun.required' => 'Nomor akun wajib diisi.',
                'nomor_akun.regex' => 'Nomor akun hanya boleh berisi angka.',
                'nomor_akun.unique' => 'Nomor akun sudah digunakan.',

                'nama_akun.required' => 'Nama akun wajib diisi.',
                'nama_akun.max' => 'Nama akun maksimal 255 karakter.',

                'jenis_akun.required' => 'Jenis akun wajib dipilih.',
                'jenis_akun.in' => 'Jenis akun tidak valid.',
            ]
        );

        Account::create([
            'no_ref_account' => $validated['nomor_akun'],
            'account_name' => $validated['nama_akun'],
            'account_category' => $validated['jenis_akun'],
            'status' => 'Aktif',
        ]);
        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $account = Account::findOrFail($id);

        $account->update([
            'status' => $request->status,
        ]);

        return back();
    }
}
