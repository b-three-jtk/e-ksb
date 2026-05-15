<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepositRequest;
use App\Models\BerjangkaAccount;
use App\Models\IbadahAccount;
use App\Models\Member;
use App\Models\MemberDoc;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                    Carbon::parse($trx->transaction_date)->format('d/m/Y'),
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
        $members = Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
            MemberStatusEnum::PAYMENT_PENDING->value
        ])
            ->with(['user:id,user_code,name', 'savingAccounts.savingProduct:id,name'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'status' => $member->status,
                    'savingAccounts' => $member->savingAccounts->map(fn($acc) => [
                        'type' => $acc->savingProduct->name ?? null,
                        'purpose' => $acc->purpose ?? null,
                        'balance' => $acc->balance ?? 0,
                        'target_amount' => $acc->target_amount ?? null,
                        'matured_at' => $acc->saving_tenor && $acc->created_at
                            ? $acc->created_at->copy()->addMonths($acc->saving_tenor)->format('d M Y')
                            : null,
                        'is_frozen'  => !is_null($acc->target_amount) && $acc->balance >= $acc->target_amount,
                        'is_matured' => $acc->saving_tenor && $acc->created_at
                            ? now()->gte($acc->created_at->copy()->addMonths($acc->saving_tenor))
                            : false,
                    ]),
                ];
            });

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'saving_types' => SavingTypeEnum::cases(),
            'pengurus' => ['name' => Auth::user()->name ?? 'Pengurus'],
        ]);
    }

    public function storeDeposit(StoreDepositRequest $request)
    {
        $data = $request->validated();

        $member = Member::with('user')->findOrFail($data['member_id']);

        $savingAccount = SavingAccount::firstOrCreate(
            [
                'member_id' => $member->id,
                'saving_type' => $data['saving_category'],
            ],
            [
                'saving_account_code' => 'SA-' . strtoupper(Str::random(8)),
                'balance' => 0,
            ]
        );

        Log::info('Saving account for member', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'was_recently_created' => $savingAccount->wasRecentlyCreated,
        ]);

        if ($data['saving_category'] === 'Simpanan Pokok') {
            if ($member->status !== MemberStatusEnum::PAYMENT_PENDING->value) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya untuk anggota dengan status Menunggu Pembayaran.'
                ]);
            }
            if (SavingTransaction::where('saving_account_id', $savingAccount->id)->exists()) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.'
                ]);
            }
        }

        if ($data['saving_category'] === 'Tabungan Ibadah' && $savingAccount->wasRecentlyCreated === false) {
            if (!$data['target_amount']) {
                throw ValidationException::withMessages(['target_amount' => 'Target tabungan wajib diisi.']);
            }

            $existingIbadah = IbadahAccount::updateOrCreate([
                'saving_account_id' => $savingAccount->id,
            ], [
                'tenor' => $data['tenor_months'] ?? null,
                'target_amount' => $data['target_amount'] ?? null,
            ]);

            if ($savingAccount->balance >= $existingIbadah->target_amount) {
                throw ValidationException::withMessages(['saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.']);
            }
        }

        if ($data['saving_category'] === 'Tabungan Berjangka') {
            BerjangkaAccount::updateOrCreate([
                'saving_account_id' => $savingAccount->id,
            ], [
                'tenor' => $data['tenor_months'] ?? null,
                'purpose' => $data['purpose'] ?? null,
            ]);

            if (!$data['tenor_months']) {
                throw ValidationException::withMessages(['tenor_months' => 'Tenor bulan wajib diisi untuk Tabungan Berjangka.']);
            }
        }

        $prevBalance = $savingAccount->balance;

        $transaction = DB::transaction(function () use ($data, $savingAccount, $member) {
            $trx = SavingTransaction::create([
                'saving_transaction_code' => 'ST' . strtoupper(Str::random(8)),
                'saving_amount' => $data['amount'],
                'balance_after_transaction' => $savingAccount->balance + $data['amount'],
                'transaction_type' => TransactionTypeEnum::DEPOSIT->value,
                'saving_payment_method' => $data['saving_payment_method'],
                'saving_description' => $data['notes'] ?? 'Penyetoran',
                'transaction_date' => $data['date'],
                'updated_by' => Auth::id(),
                'saving_account_id' => $savingAccount->id,
            ]);

            $savingAccount->increment('balance', $data['amount']);

            if ($data['saving_category'] === 'Simpanan Pokok') {
                $member->update(['status' => MemberStatusEnum::ACTIVE->value]);
            }

            return $trx;
        });

        Log::info('Deposit transaction created', [
            'transaction_id' => $transaction->id,
            'saving_account_id' => $savingAccount->id,
            'amount' => $transaction->saving_amount,
            'new_balance' => $transaction->balance_after_transaction,
        ]);

        $strukData = [
            'no_transaksi' => $transaction->saving_transaction_code,
            'tanggal' => $transaction->transaction_date,
            'pengurus' => Auth::user()->name,
            'nama_anggota' => $member->user->name,
            'no_anggota' => $member->user->user_code,
            'jenis' => $data['saving_category'],
            'metode' => $transaction->saving_payment_method,
            'nominal' => $transaction->saving_amount,
            'saldo_sebelum' => $prevBalance,
            'saldo_sesudah' => $prevBalance + $request->amount,
            'purpose' => $data['purpose'] ?? null,
        ];

        $this->storeReceiptDepositPdf($transaction, $strukData, $member->id);

        // Return dengan data fresh
        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $this->getMembersForDeposit(), // extract ke method
            'saving_types' => $data['saving_category'],
            'pengurus' => ['name' => Auth::user()->name ?? 'Pengurus'],
            'struk' => $strukData,
        ]);
    }

    // Helper baru
    private function getMembersForDeposit()
    {
        return Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
            MemberStatusEnum::PAYMENT_PENDING->value
        ])
            ->with(['user:id,user_code,name', 'savingAccounts'])
            ->get()
            ->map(fn($member) => [
                'id' => $member->id,
                'user_code' => $member->user->user_code,
                'name' => $member->user->name,
                'status' => $member->status,
                'savingAccounts' => $member->savingAccounts->map(fn($acc) => [
                    'type' => $acc->saving_type ?? null,
                    'purpose' => $acc->purpose ?? null,
                    'balance' => $acc->balance ?? 0,
                    'target_amount' => $acc->target_amount ?? null,
                    'matured_at' => $acc->saving_tenor && $acc->created_at
                        ? $acc->created_at->copy()->addMonths($acc->saving_tenor)->format('d M Y')
                        : null,
                    // tambahkan flag jika perlu
                    'is_frozen' => $acc->target_amount && $acc->balance >= $acc->target_amount,
                    'is_matured' => false, // logic sesuai kebutuhan
                ]),
            ]);
    }

    private function storeReceiptDepositPdf($transaction, array $strukData, $memberId): string
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
