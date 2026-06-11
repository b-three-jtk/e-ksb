<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Enums\PositionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepositRequest;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\BerjangkaAccount;
use App\Models\IbadahAccount;
use App\Models\Member;
use App\Models\MemberBankAccount;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\Account;
use App\Models\GlobalSetting;
use App\Services\Admin\JournalService;
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

        $query = SavingTransaction::with([
            'savingAccount.member.user',
            'savingAccount'
        ]);

        // khusus PJ anggota
        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('savingAccount.member', function ($q) {
                $q->where('pj_user_id', Auth::id());
            });
        }

        return $query
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.member.user', function ($m) use ($search) {
                    $m->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('savingAccount', function ($sa) use ($typeMap, $tab) {
                    $sa->where('saving_type', $typeMap[$tab]);
                });
            })
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('saving_type', [
                        SavingTypeEnum::SIMPANAN_POKOK->value,
                        SavingTypeEnum::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('saving_type', [
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
                    'produk'       => $trx->savingAccount->saving_type,
                    'jenis'        => $trx->transaction_type,
                ];
            });

        $summaryBase = $this->baseQuery($request);
        $totalMasuk = (clone $summaryBase)->where('transaction_type', 'Penyetoran')->sum('saving_amount');
        $totalKeluar = (clone $summaryBase)->where('transaction_type', 'Penarikan')->sum('saving_amount');
        $totalPerputaran = $totalMasuk + $totalKeluar;

        $tabLabels = [
            'semua'              => 'Simpanan & Tabungan',
            'simpanan'           => 'Semua Simpanan',
            'pokok'              => 'Simpanan Pokok',
            'wajib'              => 'Simpanan Wajib',
            'tabungan'           => 'Semua Tabungan',
            'tabungan_anggota'   => 'Tabungan Anggota',
            'tabungan_berjangka' => 'Tabungan Berjangka',
            'tabungan_ibadah'    => 'Tabungan Ibadah',
        ];
        $label = $tabLabels[$tab] ?? 'Simpanan & Tabungan';

        $summary = [
            [
                'title' => "Total {$label}",
                'value' => 'Rp ' . number_format($totalMasuk - $totalKeluar, 0, ',', '.'),
                'percentage' => $totalMasuk > 0
                    ? round((($totalMasuk - $totalKeluar) / $totalMasuk) * 100)
                    : 0,
            ],
            [
                'title' => "Total {$label} Masuk",
                'value' => 'Rp ' . number_format($totalMasuk, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalMasuk / $totalPerputaran) * 100)
                    : 0,
            ],
            [
                'title' => "Total {$label} Keluar",
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
                    $trx->savingAccount->saving_type ?? '-',
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
        $data = SavingTransaction::with('savingAccount.member.user', 'memberBankAccount')->find($id);
        $saving_transaction_receipt = $data->saving_transaction_receipt ? Storage::url($data->saving_transaction_receipt) : null;

        return inertia('Admin/Savings/Show', [
            'data' => $data,
            'saving_transaction_receipt' => $saving_transaction_receipt,
        ]);
    }

    private function getGlobalSettingValue(string $key): float
    {
        return (float) GlobalSetting::where('key', $key)
            ->latest('effective_date')
            ->value('value') ?? 0;
    }

    public function createDeposit(Request $request)
    {
        $members = Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
            MemberStatusEnum::PAYMENT_PENDING->value
            ])
            ->when(
                Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value),
                function ($q) {
                    $q->where('pj_user_id', Auth::id());
                }
            )
            ->with([
                'user:id,user_code,name',
                'savingAccounts.ibadah',
                'savingAccounts.berjangka'
            ])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'status' => $member->status,
                    'savingAccounts' => $member->savingAccounts
                        ->filter(function ($acc) {
                            if ($acc->saving_type === 'Tabungan Ibadah') {
                                return $acc->ibadah;
                            }

                            if ($acc->saving_type === 'Tabungan Berjangka') {
                                return $acc->berjangka;
                            }

                            return true;
                        })
                        ->map(fn($acc) => [
                            'id' => $acc->id,
                            'type' => $acc->saving_type ?? null,
                            'purpose' => $acc->berjangka?->purpose ?? $acc->ibadah?->purpose,
                            'balance' => $acc->balance ?? 0,
                            'target_amount' => $acc->ibadah?->target_amount,
                            'matured_at' => $acc->berjangka
                                ? $acc->created_at
                                    ->copy()
                                    ->addMonths($acc->berjangka->tenor)
                                    ->format('d M Y')
                                : null,
                            'is_frozen' => $acc->ibadah
                                ? $acc->balance >= $acc->ibadah->target_amount
                                : false,
                            'is_matured' => $acc->berjangka
                                ? now()->gte(
                                    $acc->created_at
                                        ->copy()
                                        ->addMonths($acc->berjangka->tenor)
                                )
                                : false,
                        ])
                        ->values()
                        ->toArray(),
                ];
            });

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $members,
            'saving_types' => collect(SavingTypeEnum::cases())
                ->map(fn ($case) => $case->value),
            'pengurus' => ['name' => Auth::user()->name ?? 'Pengurus'],
            'global_saving' => [
                'pokok' => $this->getGlobalSettingValue('saving_pokok_amount'),
                'wajib' => $this->getGlobalSettingValue('saving_wajib_amount'),
            ],
        ]);
    }

    private function getTrxPrefix(string $category): string
    {
        return match ($category) {
            'Tabungan Anggota' => 'TA',
            'Simpanan Pokok' => 'SP',
            'Simpanan Wajib' => 'SW',
            'Tabungan Berjangka' => 'TB',
            'Tabungan Ibadah' => 'TI',
            default => 'ST',
        };
    }

    public function storeDeposit(StoreDepositRequest $request)
    {
        $data = $request->validated();

        if (in_array($data['saving_category'], ['Simpanan Pokok', 'Simpanan Wajib'])) {
            $data['amount'] = $this->getGlobalSettingValue(
                $data['saving_category'] === 'Simpanan Pokok'
                    ? 'saving_pokok_amount'
                    : 'saving_wajib_amount'
            );
        }

        $member = Member::with('user')->findOrFail($data['member_id']);

        if (
            Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value)
            && $member->pj_user_id !== Auth::id()
        ) {
            abort(403, 'Anda tidak berhak melakukan transaksi untuk anggota ini.');
        }

        if (filled($data['saving_account_id'] ?? null)) {
            $savingAccount = SavingAccount::where(
                'id',
                $data['saving_account_id']
            )->where(
                'member_id',
                $member->id
            )->firstOrFail();
        } else {

            if (
                in_array(
                    $data['saving_category'],
                    [
                        'Simpanan Pokok',
                        'Simpanan Wajib',
                        'Tabungan Anggota'
                    ]
                )
            ) {
                $savingAccount = SavingAccount::firstOrCreate(
                    [
                        'member_id' => $member->id,
                        'saving_type' => $data['saving_category'],
                    ],
                    [
                        'saving_account_code' =>
                            $this->getTrxPrefix($data['saving_category'])
                            . '-SA-' . strtoupper(Str::random(6)),
                    ]
                );
            } else {
                $savingAccount = SavingAccount::create([
                    'member_id' => $member->id,
                    'saving_type' => $data['saving_category'],
                    'saving_account_code' =>
                        $this->getTrxPrefix($data['saving_category'])
                        . '-SA-' . strtoupper(Str::random(6)),
                ]);
            }
        }

        if ($savingAccount->wasRecentlyCreated && $data['saving_category'] === 'Tabungan Berjangka')
        {
            if (empty($data['tenor_months'])) {
                throw ValidationException::withMessages([
                    'tenor_months' => 'Jatuh tempo wajib diisi untuk tabungan berjangka baru.'
                ]);
            }

            BerjangkaAccount::create([
                'saving_account_id' => $savingAccount->id,
                'purpose' => $data['purpose'] ?? null,
                'tenor' => $data['tenor_months'],
            ]);
        }

        Log::info('Saving account for member', [
            'member_id' => $member->id,
            'saving_account_id' => $savingAccount->id,
            'was_recently_created' => $savingAccount->wasRecentlyCreated,
        ]);

        if ($data['saving_category'] === 'Simpanan Pokok') {

            $expected = $this->getGlobalSettingValue('saving_pokok_amount');

            if ((float)$data['amount'] != $expected) {
                throw ValidationException::withMessages([
                    'amount' => "Simpanan Pokok harus sebesar Rp " . number_format($expected, 0, ',', '.')
                ]);
            }

            if ($member->status !== MemberStatusEnum::PAYMENT_PENDING->value) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya untuk anggota Menunggu Pembayaran.'
                ]);
            }

            if (SavingTransaction::where('saving_account_id', $savingAccount->id)->exists()) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.'
                ]);
            }
        }

        if ($data['saving_category'] === 'Simpanan Wajib') {

            $expected = $this->getGlobalSettingValue('saving_wajib_amount');

            if (abs((float)$data['amount'] - (float)$expected) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => "Simpanan Wajib harus sebesar Rp " . number_format($expected, 0, ',', '.')
                ]);
            }
        }

        if ($data['saving_category'] === 'Tabungan Ibadah')
        {
            if ($savingAccount->wasRecentlyCreated) {
                // Akun baru — wajib ada target_amount
                if (!isset($data['target_amount']) || !$data['target_amount']) {
                    throw ValidationException::withMessages([
                        'target_amount' => 'Target tabungan wajib diisi.'
                    ]);
                }

                IbadahAccount::create([
                    'saving_account_id' => $savingAccount->id,
                    'purpose' => $data['purpose'],
                    'target_amount' => $data['target_amount'],
                ]);
            }

            // Refresh setelah create (atau pakai existing)
            $ibadahAccount = $savingAccount->fresh()->ibadah;

            if (
                $ibadahAccount &&
                $savingAccount->balance >= $ibadahAccount->target_amount
            ) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.'
                ]);
            }
        }

        if ($data['saving_category'] === 'Tabungan Berjangka' && $savingAccount->berjangka)
        {
            $jatuhTempo = $savingAccount
                ->created_at
                ->copy()
                ->addMonths($savingAccount->berjangka->tenor);

            if (now()->gte($jatuhTempo)) {
                throw ValidationException::withMessages([
                    'saving_category' =>
                        'Tabungan Berjangka sudah jatuh tempo.'
                ]);
            }
        }

        $prevBalance = $savingAccount->balance;

        $transaction = DB::transaction(function () use ($data, $savingAccount, $member) {
            $trx = SavingTransaction::create([
                'saving_transaction_code' => $this->getTrxPrefix($data['saving_category']) . strtoupper(Str::random(8)),
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

        $kasAccount = Account::where(
            'account_name',
            'Kas'
        )->firstOrFail();

        $savingAccountRef = Account::where(
            'account_name',
            $data['saving_category']
        )->firstOrFail();

        app(JournalService::class)->create(
            [
                [
                    'account'  => $kasAccount->no_ref_account,
                    'position' => PositionEnum::DEBIT->value,
                    'nominal'  => $transaction->saving_amount,
                ],
                [
                    'account'  => $savingAccountRef->no_ref_account,
                    'position' => PositionEnum::CREDIT->value,
                    'nominal'  => $transaction->saving_amount,
                ],
            ],
            $transaction->transaction_date,
            Auth::id()
        );

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
            'saldo_sesudah' => $prevBalance + $transaction->saving_amount,
            'purpose' => $data['purpose'] ?? null,
        ];

        $this->storeReceiptDepositPdf($transaction, $strukData, $member->id);

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members' => $this->getMembersForDeposit(),
            'saving_types' => collect(SavingTypeEnum::cases())
                ->map(fn ($case) => $case->value),
            'pengurus' => [
                'name' => Auth::user()->name ?? 'Pengurus'
            ],
            'global_saving' => [
                'pokok' => $this->getGlobalSettingValue('saving_pokok_amount'),
                'wajib' => $this->getGlobalSettingValue('saving_wajib_amount'),
            ],
            'struk' => $strukData,
        ]);
    }

    public function createWithdrawal()
    {
        $members =  $members = $this->getMembersForSavingSelection(true);

        return Inertia::render('Admin/Savings/Withdrawal/Create', [
            'members' => $members,
        ]);
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request)
    {
        $validated = $request->validated();

        $member = Member::with('user')->findOrFail($validated['member_id']);
        $savingAccount = SavingAccount::findOrFail($validated['saving_account_id']);
        $savingBalance = $savingAccount->balance;

        if ((int) $savingAccount->member_id !== (int) $member->id) {
            return back()
                ->withErrors(['saving_account_id' => 'Rekening simpanan tidak ditemukan untuk anggota ini']);
        }

        if ($savingBalance < $validated['amount']) {
            return back()
                ->withErrors(['amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp ' . number_format($validated['amount'])]);
        }

        $savingType = (string) ($savingAccount->saving_type ?? '');
        $typeLower = mb_strtolower($savingType);

        if (str_contains($typeLower, 'berjangka')) {
            $tenorMonths = (int) ($savingAccount->saving_tenor ?? 0);
            if ($tenorMonths > 0 && $savingAccount->created_at) {
                $maturityDate = Carbon::parse($savingAccount->created_at)->addMonths($tenorMonths)->startOfDay();
                if (Carbon::today()->lt($maturityDate)) {
                    return back()->withErrors([
                        'saving_account_id' => 'Tabungan berjangka belum jatuh tempo. Pencairan dapat dilakukan mulai ' . $maturityDate->format('d/m/Y'),
                    ]);
                }
            }
        }

        if (str_contains($typeLower, 'ibadah')) {
            $targetAmount = (float) ($savingAccount->target_amount ?? 0);
            if ($targetAmount > 0 && (float) $savingBalance < $targetAmount) {
                return back()->withErrors([
                    'saving_account_id' => 'Tabungan ibadah belum mencapai target minimal Rp ' . number_format($targetAmount, 0, ',', '.'),
                ]);
            }
        }

        try {
            [$transaction, $saldoSebelum] = DB::transaction(function () use ($validated, $member, $savingAccount, $savingType) {
                $lockedSavingAccount = SavingAccount::query()
                    ->whereKey($savingAccount->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $saldoSebelum = $lockedSavingAccount->balance;

                if ($saldoSebelum === null) {
                    $saldoSebelum = (float) ($lockedSavingAccount->balance ?? 0);
                } else {
                    $saldoSebelum = (float) $saldoSebelum;
                }

                if ($saldoSebelum < (float) $validated['amount']) {
                    throw new \RuntimeException('Saldo tidak cukup untuk penarikan.');
                }

                $transaction = SavingTransaction::create([
                    'saving_transaction_code' => $this->generateWithdrawalTransactionCode($savingType),
                    'saving_account_id' => $lockedSavingAccount->id,
                    'balance_after_transaction' => $saldoSebelum - $validated['amount'],
                    'saving_amount' => $validated['amount'],
                    'transaction_type' => TransactionTypeEnum::WITHDRAWAL->value,
                    'saving_payment_method' => $validated['method'],
                    'transaction_date' => $validated['withdrawal_date'],
                    'saving_description' => $validated['notes'] ?? '',
                    'updated_by' => auth()->id(),
                ]);

                if ($validated['method'] === 'Non-Tunai') {
                    MemberBankAccount::updateOrCreate(
                        [
                            'member_id' => $member->id,
                            'account_number' => $validated['account_number'],
                        ],
                        [
                            'bank_name' => $validated['bank_name'],
                            'account_name' => $validated['account_name'],
                        ]
                    );

                    $transaction->update([
                        'account_number' => $validated['account_number'],
                    ]);
                }

                $lockedSavingAccount->update([
                    'balance' => $saldoSebelum - $validated['amount'],
                ]);

                return [$transaction, $saldoSebelum];
            });

            $kasAccount = Account::where(
                'account_name',
                'Kas'
            )->firstOrFail();

            $savingAccountRef = Account::where(
                'account_name',
                $savingType
            )->firstOrFail();

            app(JournalService::class)->create(
                [
                    [
                        'account'  => $savingAccountRef->no_ref_account,
                        'position' => PositionEnum::DEBIT->value,
                        'nominal'  => $transaction->saving_amount,
                    ],
                    [
                        'account'  => $kasAccount->no_ref_account,
                        'position' => PositionEnum::CREDIT->value,
                        'nominal'  => $transaction->saving_amount,
                    ],
                ],
                $transaction->transaction_date,
                Auth::id()
            );

            $admin = auth()->user();
            $namaAdmin = $admin->name ?? 'Pengurus';

            $strukData = [
                'transaction_id' => $transaction->id,
                'no_transaksi' => $transaction->saving_transaction_code,
                'tanggal' => $transaction->transaction_date,
                'pengurus' => $namaAdmin,
                'nama_anggota' => $member->user?->name ?? '-',
                'no_anggota' => $member->user?->user_code ?? '-',
                'jenis' => $savingType !== '' ? $savingType : '-',
                'metode' => $validated['method'],
                'nominal' => $validated['amount'],
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSebelum - $validated['amount'],
                'bank_name' => $validated['bank_name'] ?? '',
                'account_name' => $validated['account_name'] ?? '',
                'account_number' => $validated['account_number'] ?? '',
            ];

            try {
                $receiptPath = $this->storeReceiptWithdrawalPdf($transaction, $strukData);
                if ($receiptPath) {
                    $transaction->update([
                        'saving_transaction_receipt' => $receiptPath,
                    ]);
                }
            } catch (\Throwable $receiptException) {
                report($receiptException);
            }

            return redirect()
                ->route('admin.savings.withdrawal.create')
                ->with('success', 'Penarikan simpanan berhasil disimpan')
                ->with('struk', $strukData);
        } catch (\Exception $e) {
            report($e);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // Helper
    private function getMembersForSavingSelection(bool $includeBankAccounts = false)
    {
        $query = Member::query()
            ->when($includeBankAccounts, function ($q) {
                $q->with([
                    'user',
                    'savingAccounts',
                    'bankAccounts' => function ($subQuery) {
                        $subQuery->latest();
                    },
                ]);
            }, function ($q) {
                $q->with(['user:id,user_code,name', 'savingAccounts']);
            })
            ->whereIn('status', [
                MemberStatusEnum::ACTIVE->value,
                MemberStatusEnum::PAYMENT_PENDING->value,
            ])
            ->whereHas('user', function ($q) {
                $q->where('status', UserStatusEnum::ACTIVE->value);
            });

        if (Auth::user()?->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->where('pj_user_id', Auth::id());
        }

        return $query->get()->map(function ($member) use ($includeBankAccounts) {
            if ($includeBankAccounts) {
                return [
                    'id' => $member->id,
                    'name' => $member->user?->name,
                    'user_code' => $member->user?->user_code,
                    'savingAccounts' => $member->savingAccounts->map(function ($acc) {
                        return [
                            'id' => $acc->id,
                            'type' => $acc->saving_type ?? '-',
                            'balance' => $acc->balance ?? 0,
                            'tenor_months' => $acc->saving_tenor,
                            'target_amount' => $acc->target_amount,
                            'opened_at' => optional($acc->created_at)->toDateString(),
                        ];
                    })->toArray(),
                    'accounts' => $member->bankAccounts->map(function ($acc) {
                        return [
                            'bank_name' => $acc->bank_name,
                            'account_name' => $acc->account_name,
                            'account_number' => $acc->account_number,
                        ];
                    })->toArray(),
                ];
            }

            return [
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
                    'is_frozen' => !is_null($acc->target_amount) && $acc->balance >= $acc->target_amount,
                    'is_matured' => $acc->saving_tenor && $acc->created_at
                        ? now()->gte($acc->created_at->copy()->addMonths($acc->saving_tenor))
                        : false,
                ]),
            ];
        });
    }

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
                    'purpose' => $acc->berjangka?->purpose?? $acc->ibadah?->purpose,
                    'balance' => $acc->balance ?? 0,
                    'target_amount' => $acc->ibadah?->target_amount,
                    'matured_at' => $acc->berjangka
                        ? $acc->created_at
                            ->copy()
                            ->addMonths($acc->berjangka->tenor)
                            ->format('d M Y')
                        : null,
                    'is_frozen' => $acc->ibadah
                        ? $acc->balance >= $acc->ibadah->target_amount
                        : false,
                    'is_matured' => $acc->berjangka
                        ? now()->gte(
                            $acc->created_at
                                ->copy()
                                ->addMonths($acc->berjangka->tenor)
                        )
                        : false,
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
            SavingTransaction::where('id', $transaction->id)->update(['saving_transaction_receipt' => $path]);

            return $path;
        }

            throw new \Exception('File tidak berhasil disimpan');

        return null;
    }

    private function storeReceiptWithdrawalPdf(SavingTransaction $transaction, array $strukData): ?string
    {
        try {
            $pdf = Pdf::loadView('exports.withdrawal_receipt', [
                'struk' => $strukData,
            ])->setPaper([0, 0, 226.77, 600], 'portrait');

            $directory = 'member_docs/receipts/' . now()->format('Y-m');
            Storage::disk('public')->makeDirectory($directory);

            $filename = 'struk-withdrawal-' . $transaction->id . '.pdf';
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            if (!Storage::disk('public')->exists($path)) {
                try {
                    $full = storage_path('app/public/' . $path);
                    $dir = dirname($full);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($full, $pdf->output());
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if (Storage::disk('public')->exists($path) || file_exists(storage_path('app/public/' . $path))) {
                return $path;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function generateWithdrawalTransactionCode(string $savingType): string
    {
        $yymm = Carbon::now()->format('ym');

        // inisial jenis simpanan
        $categoryPrefix = $this->getTrxPrefix($savingType);

        $prefix = $categoryPrefix . $yymm;

        $latestTransaction = SavingTransaction::where('transaction_type', TransactionTypeEnum::WITHDRAWAL->value)
            ->where('saving_transaction_code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('saving_transaction_code')
            ->first();

        $lastNumber = 0;
        if ($latestTransaction) {
            preg_match('/(\d{4})$/', (string) $latestTransaction->saving_transaction_code, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
        }

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
