<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SavingController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private function baseQuery(Request $request)
    {
        $search = $request->input('search');
        $tab = $request->input('tab', 'semua');

        $typeMap = [
            'pokok' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'wajib' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'tabungan_anggota' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'tabungan_berjangka' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'tabungan_ibadah' => SavingTypeEnum::TABUNGAN_IBADAH->value,
        ];

        return SavingTransaction::with(['savingAccount.member.user', 'savingAccount.savingProduct'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.member.user', function ($m) use ($search) {
                    $m->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('savingAccount.savingProduct', function ($sa) use ($typeMap, $tab) {
                    $sa->where('name', $typeMap[$tab]);
                });
            })
            // Filter grup: 'simpanan' → 2 tipe simpanan
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('savingAccount.savingProduct', function ($sa) {
                    $sa->whereIn('name', [
                        SavingTypeEnum::SIMPANAN_POKOK->value,
                        SavingTypeEnum::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('savingAccount.savingProduct', function ($sa) {
                    $sa->whereIn('name', [
                        SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                        SavingTypeEnum::TABUNGAN_BERJANGKA->value,
                        SavingTypeEnum::TABUNGAN_IBADAH->value,
                    ]);
                });
            });
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $tab = $request->input('tab', 'semua');
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['transaction_date'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'transaction_date';
        }

        $query = $this->baseQuery($request)->orderBy($sortBy, $sortDir);

        $transactions = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($trx) {
                return [
                    'id'           => $trx->id,
                    'no_transaksi' => $trx->transaction_code, 
                    'tanggal'      => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'anggota'      => $trx->savingAccount->user->member_number
                                    . ' - '
                                    . $trx->savingAccount->user->name,
                    'nominal'      => $trx->type === 'Penarikan'
                                    ? -$trx->amount
                                    : $trx->amount,
                    'produk'       => $trx->savingAccount->type, // nama lengkap
                    'jenis'        => $trx->type,
                ];
            });

        $summaryBase = $this->baseQuery($request);
        $totalMasuk = (clone $summaryBase)->where('transaction_type', 'Penyetoran')->sum('saving_amount');
        $totalKeluar = (clone $summaryBase)->where('transaction_type', 'Penarikan')->sum('saving_amount');
        $totalPerputaran = $totalMasuk + $totalKeluar;

        $summary = [
            [
                'title' => 'Total Kas',
                'value' => 'Rp ' . number_format($totalMasuk - $totalKeluar, 0, ',', '.'),
                'percentage' => $totalMasuk > 0
                    ? round((($totalMasuk - $totalKeluar) / $totalMasuk) * 100)
                    : 0,
            ],
            [
                'title' => 'Total Simpanan Masuk',
                'value' => 'Rp ' . number_format($totalMasuk, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalMasuk / $totalPerputaran) * 100)
                    : 0,
            ],
            [
                'title' => 'Total Simpanan Keluar',
                'value' => 'Rp ' . number_format($totalKeluar, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalKeluar / $totalPerputaran) * 100)
                    : 0,
            ],
        ];

        return Inertia::render('Admin/Savings/List', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'tab' => $tab,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }


    private function exportTitle(string $tab): string
    {
        return match ($tab) {
            'simpanan' => 'Data Semua Simpanan',
            'pokok' => 'Data Simpanan Pokok',
            'wajib' => 'Data Simpanan Wajib',
            'tabungan' => 'Data Semua Tabungan',
            'tabungan_anggota' => 'Data Tabungan Anggota',
            'tabungan_berjangka' => 'Data Tabungan Berjangka',
            'tabungan_ibadah' => 'Data Tabungan Ibadah',
            default => 'Data Simpanan & Tabungan',
        };
    }

    public function exportCsv(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $title = $this->exportTitle($tab);
        $filename = Str::slug($title) . '_' . now()->format('Ymd_His') . '.csv';

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($transactions, $title) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [$title]);
            fputcsv($handle, []);
            fputcsv($handle, ['No Transaksi', 'Tanggal', 'Anggota', 'Produk', 'Jenis', 'Nominal']);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    str_pad($trx->id, 6, '0', STR_PAD_LEFT),
                    $trx->transaction_date->format('d/m/Y'),
                    $trx->savingAccount->user->user_code . ' - ' . $trx->savingAccount->user->name,
                    $trx->savingAccount->saving_type,
                    $trx->transaction_type,
                    $trx->transaction_type === 'Penarikan' ? -$trx->saving_amount : $trx->saving_amount,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $title = $this->exportTitle($tab);

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.saving', [
            'transactions' => $transactions,
            'title' => $title,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            Str::slug($title) . '_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = SavingTransaction::with('savingAccount.user', 'account')->find($id);

        return inertia('Admin/Savings/Show', [
            'data' => $data,
        ]);
    }

    public function createDeposit(Request $request)
    {

        $members = User::where('role_id', 9)
            ->where('status', 'Aktif')
            ->select('id', 'user_code', 'name')
            ->with(['savingAccounts:id,user_id,saving_type'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'savingAccounts' => $user->savingAccounts->map(fn($acc) => [
                        'type' => $acc->saving_type,
                    ]),
                ];
            });

        $accounts = Account::select('account_number', 'bank_name', 'account_name', 'user_id')
            ->get();

        $pengurus = Auth::user();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'accounts' => $accounts,
            'saving_types' => array_column(SavingTypeEnum::cases(), 'value'),
            'pengurus' => [
                'name' => $pengurus->name ?? 'Pengurus',
            ],
        ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'member_id' => 'required|uuid|exists:users,id',
            'saving_category' => 'required|string|in:' . implode(',', array_column(SavingTypeEnum::cases(), 'value')),
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'saving_payment_method' => 'required|in:Tunai,Non-Tunai',
            'notes' => 'nullable|string|max:500',
            'tenor_months' => 'nullable|integer|min:1',
            'target_amount' => 'nullable|numeric|min:1',

            // non-tunai
            'bank_name' => 'required_if:method,Non-Tunai|string|max:100',
            'account_name' => 'required_if:method,Non-Tunai|string|max:150',
            'account_number' => 'required_if:method,Non-Tunai|string|max:50',
            'payment_proof' => 'required_if:method,Non-Tunai|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $member = User::findOrFail($request->member_id);

        $savingAccount = SavingAccount::where('user_id', $member->id)
            ->where('saving_type', $request->saving_category)
            ->first();

        if (!$savingAccount) {
            if ($request->saving_category === 'Tabungan Berjangka' && !$request->tenor_months) {
                return back()->withErrors(['tenor_months' => 'Tenor wajib diisi']);
            }

            if ($request->saving_category === 'Tabungan Ibadah' && !$request->target_amount) {
                return back()->withErrors(['target_amount' => 'Target wajib diisi']);
            }

            $savingAccount = SavingAccount::create([
                'saving_account_code' => 'SA-' . strtoupper(Str::random(8)),
                'saving_type' => $request->saving_category,
                'tenor_months' => $request->tenor_months,
                'target_amount' => $request->target_amount,
                'user_id' => $member->id,
            ]);
        }

        $prevBalance = DB::table('get_saving_account_balance')
            ->where('saving_account_id', $savingAccount->id)
            ->value('total_balance') ?? 0;

        $transaction = DB::transaction(function () use ($request, $savingAccount) {

            $account = null;

            if ($request->saving_payment_method === 'Non-Tunai') {
                $account = Account::updateOrCreate(
                    [
                        'account_number' => $request->account_number,
                        'user_id' => $request->member_id,
                    ],
                    [
                        'bank_name' => $request->bank_name,
                        'account_name' => $request->account_name,
                    ]
                );
            }

            $transaction = SavingTransaction::create([
                'saving_transaction_code' => 'ST' . Str::upper(Str::random(8)),
                'saving_amount' => $request->amount,
                'transaction_type' => TransactionTypeEnum::DEPOSIT->value,
                'saving_payment_method' => $request->saving_payment_method,
                'saving_description' => $request->notes ?? 'Penyetoran oleh pengurus',
                'transaction_date' => $request->date,
                'updated_by' => Auth::id(),
                'saving_account_id' => $savingAccount->id,
                'account_number' => $account?->account_number,
            ]);

            $savingAccount->increment('balance', $request->amount);

            return $transaction;
        });

        $members = User::where('role_id', 11)
            ->where('status', UserStatusEnum::ACTIVE->value)
            ->with('savingAccounts')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'savingAccounts' => $user->savingAccounts->map(fn($acc) => [
                        'type' => $acc->type,
                        'balance' => $acc->balance,
                    ]),
                ];
            });

        $accounts = Account::select('account_number', 'bank_name', 'account_name', 'user_id')->get();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'accounts' => $accounts,
            'pengurus' => [
                'name' => Auth::user()->name ?? 'Pengurus',
            ],

            'struk' => [
                'no_transaksi' => $transaction->transaction_code,
                'tanggal' => $transaction->created_at,
                'pengurus' => Auth::user()->name,
                'nama_anggota' => $member->name,
                'no_anggota' => $member->user_code,
                'jenis' => $savingAccount->saving_type,
                'metode' => $transaction->saving_payment_method,
                'nominal' => $transaction->saving_amount,
                'saldo_sebelum' => $savingAccount->prevBalance - $transaction->saving_amount,
                'saldo_sesudah' => $savingAccount->prevBalance + $transaction->saving_amount,
                'tenor' => $savingAccount->tenor_months,
                'target' => $savingAccount->target_amount,
            ]
        ]);
    }
}
