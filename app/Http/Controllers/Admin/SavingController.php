<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\Member;
use App\Models\MemberBankAccount;
use App\Models\SavingProduct;
use App\Models\User;
use App\Models\MemberDoc;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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
                    'no_transaksi' => $trx->saving_transaction_code, 
                    'tanggal'      => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'anggota'      => $trx->savingAccount->member->user->user_code
                                    . ' - '
                                    . $trx->savingAccount->member->user->name,
                    'nominal'      => $trx->transaction_type === TransactionTypeEnum::WITHDRAWAL->value
                                    ? -$trx->saving_amount
                                    : $trx->saving_amount,
                    'produk'       => $trx->savingAccount->savingProduct->name,
                    'jenis'        => $trx->transaction_type,
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
                    $trx->saving_transaction_code,
                    \Carbon\Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    $trx->savingAccount->member->user->user_code . ' - ' .
                    $trx->savingAccount->member->user->name,
                    $trx->savingAccount->savingProduct->name ?? '-',
                    $trx->transaction_type,
                    $trx->transaction_type === TransactionTypeEnum::WITHDRAWAL->value
                        ? -$trx->saving_amount
                        : $trx->saving_amount,
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

        $members = Member::where('status', 'Aktif')
            ->with([
                'user:id,user_code,name',
                'savingAccounts.savingProduct:id,name'
            ])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'savingAccounts' => $member->savingAccounts->map(fn($acc) => [
                        'type' => $acc->savingProduct->name ?? null,
                        'balance' => $acc->balance ?? 0,
                    ]),
                ];
            });

        $accounts = MemberBankAccount::select(
            'account_number',
            'bank_name',
            'account_name',
            'member_id'
        )->get();

        $pengurus = Auth::user();

            return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'accounts' => $accounts,
            'saving_types' => collect(SavingTypeEnum::cases())->map(fn($case) => $case->value),
            'pengurus' => [
                'name' => Auth::user()->name ?? 'Pengurus',
            ],
        ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'saving_category' => 'required|exists:saving_products,name',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'saving_payment_method' => 'required|in:Tunai,Non-Tunai',
        ]);

        $member = Member::with('user')->findOrFail($request->member_id);

        $savingProduct = SavingProduct::where('name', $request->saving_category)->firstOrFail();

        $savingAccount = SavingAccount::where('member_id', $member->id)
            ->where('saving_product_id', $savingProduct->id)
            ->first();

        if (!$savingAccount) {
            $savingAccount = SavingAccount::create([
                'saving_account_code' => 'SA-' . strtoupper(Str::random(8)),
                'saving_product_id' => $savingProduct->id,
                'saving_tenor' => $request->tenor_months,
                'target_amount' => $request->target_amount,
                'balance' => 0,
                'member_id' => $member->id,
            ]);
        }

        $prevBalance = $savingAccount->balance;

        $transaction = DB::transaction(function () use ($request, $savingAccount, $member, $savingProduct, $prevBalance) {

            $account = null;

            // if ($request->saving_payment_method === 'Non-Tunai') {
            //     $account = MemberBankAccount::updateOrCreate(
            //         [
            //             'account_number' => $request->account_number,
            //             'member_id' => $member->id,
            //         ],
            //         [
            //             'bank_name' => $request->bank_name,
            //             'account_name' => $request->account_name,
            //         ]
            //     );
            // }

            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');

                $path = $file->store('member_docs/payment_proof', 'public');

                MemberDoc::create([
                    'member_id' => $member->id,
                    'doc_name' => 'Bukti Pembayaran Setoran',
                    'doc_attachment' => $path,
                ]);
            }

            $trx = SavingTransaction::create([
                'saving_transaction_code' => 'ST' . Str::upper(Str::random(8)),
                'saving_amount' => $request->amount,
                'transaction_type' => TransactionTypeEnum::DEPOSIT->value,
                'saving_payment_method' => $request->saving_payment_method,
                'saving_description' => $request->notes ?? 'Penyetoran oleh pengurus',
                'transaction_date' => $request->date,
                'updated_by' => Auth::id(),
                'saving_account_id' => $savingAccount->id,
                'account_number' => null,
            ]);

            $savingAccount->increment('balance', $request->amount);

            $strukData = [
                'no_transaksi' => $trx->saving_transaction_code,
                'tanggal' => $trx->transaction_date,
                'pengurus' => Auth::user()->name,

                'nama_anggota' => $member->user->name,
                'no_anggota' => $member->user->user_code,

                'jenis' => $savingProduct->name,
                'metode' => $trx->saving_payment_method,

                'nominal' => $trx->saving_amount,

                'saldo_sebelum' => $prevBalance,
                'saldo_sesudah' => $prevBalance + $trx->saving_amount,

                'tenor' => $savingAccount->saving_tenor,
                'target' => $savingAccount->target_amount,

                // 'bank_name' => $request->bank_name ?? '',
                // 'account_name' => $request->account_name ?? '',
                // 'account_number' => $request->account_number ?? '',
            ];

            $this->storeReceiptDepositPdf($trx, $strukData, $member->id);

            return $trx;
        });

        $members = Member::where('status', 'Aktif')
            ->with([
                'user:id,user_code,name',
                'savingAccounts.savingProduct:id,name'
            ])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'savingAccounts' => $member->savingAccounts->map(fn($acc) => [
                        'type' => $acc->savingProduct->name ?? null,
                        'balance' => $acc->balance ?? 0,
                    ]),
                ];
            });

        $accounts = MemberBankAccount::select(
            'account_number',
            'bank_name',
            'account_name',
            'member_id'
        )->get();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'accounts' => $accounts,
            'saving_types' => SavingProduct::pluck('name'),
            'pengurus' => [
                'name' => Auth::user()->name ?? 'Pengurus',
            ],

            'struk' => [
                'no_transaksi' => $transaction->saving_transaction_code,
                'tanggal' => $transaction->transaction_date,
                'pengurus' => Auth::user()->name,

                'nama_anggota' => $member->user->name,
                'no_anggota' => $member->user->user_code,

                'jenis' => $savingProduct->name,
                'metode' => $transaction->saving_payment_method,

                'nominal' => $transaction->saving_amount,

                'saldo_sebelum' => $prevBalance,
                'saldo_sesudah' => $prevBalance + $transaction->saving_amount,

                'tenor' => $savingAccount->saving_tenor,
                'target' => $savingAccount->target_amount,
            ]
        ]);
    }

    private function storeReceiptDepositPdf($transaction, array $strukData, $memberId): ?string
    {
        $pdf = Pdf::loadView('exports.deposit_receipt', [
            'struk' => $strukData,
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        $directory = 'member_docs/receipts/' . now()->format('Y-m');
        Storage::disk('public')->makeDirectory($directory);

        $filename = 'struk-deposit-' . $transaction->id . '.pdf';
        $path = $directory . '/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        if (Storage::disk('public')->exists($path)) {
            MemberDoc::create([
                'member_id' => $memberId,
                'doc_name' => 'Struk Penyetoran',
                'doc_attachment' => $path,
            ]);

            return $path;
        }

            throw new \Exception('File tidak berhasil disimpan');

        return null;
    }
}
