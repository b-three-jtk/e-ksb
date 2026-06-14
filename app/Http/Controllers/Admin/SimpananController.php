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
use App\Services\Admin\JurnalService;
use App\Services\User\SimpananServices;
use App\Services\PengaturanUmumService;
use App\Services\Admin\SimpananService;
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

class SimpananController extends Controller
{
    public function __construct(
        private SimpananServices $simpananServices,
        private PengaturanUmumService $pengaturanUmumService,
        private SimpananService $simpananService,
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->simpananService->getTransactionList($request);

        return Inertia::render('Admin/Savings/List', $data);
    }

    public function exportCsv(Request $request)
    {
        $tab      = $request->input('tab', 'semua');
        $title    = $this->simpananService->getExportTitle($tab);
        $filename = Str::slug($title) . '_' . now()->format('Ymd_His') . '.csv';

        $transactions = $this->simpananService->buildBaseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
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
                    $trx->savingAccount->member->user->user_code . ' - ' . $trx->savingAccount->member->user->name,
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
        $tab   = $request->input('tab', 'semua');
        $title = $this->simpananService->getExportTitle($tab);

        $transactions = $this->simpananService->buildBaseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.saving', [
            'transactions' => $transactions,
            'title'        => $title,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(Str::slug($title) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function show(string $id)
    {
        $data = SavingTransaction::with('savingAccount.member.user', 'memberBankAccount')->find($id);
        $saving_transaction_receipt = $data->saving_transaction_receipt ? Storage::url($data->saving_transaction_receipt) : null;

        return inertia('Admin/Savings/Show', [
            'data' => $data,
            'saving_transaction_receipt' => $saving_transaction_receipt,
        ]);
    }

    public function createDeposit(Request $request)
    {
        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members'      => $this->simpananService->getMembersForDeposit(),
            'saving_types' => collect(SavingTypeEnum::cases())->map(fn($c) => $c->value),
            'pengurus'     => ['name' => Auth::user()->name ?? 'Pengurus'],
            'global_saving' => [
                'pokok' => $this->simpananService->getSettingValue('saving_pokok_amount'),
                'wajib' => $this->simpananService->getSettingValue('saving_wajib_amount'),
            ],
        ]);
    }

    public function storeDeposit(StoreDepositRequest $request)
    {
        $data = $request->validated();

        if (in_array($data['saving_category'], [
            SavingTypeEnum::SIMPANAN_POKOK->value,
            SavingTypeEnum::SIMPANAN_WAJIB->value,
        ])) {
            $data['amount'] = $this->simpananService->getSettingValue(
                $data['saving_category'] === SavingTypeEnum::SIMPANAN_POKOK->value
                    ? 'saving_pokok_amount'
                    : 'saving_wajib_amount'
            );
        }

        $member = Member::with('user')->findOrFail($data['member_id']);

        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value) && $member->pj_user_id !== Auth::id()) {
            abort(403, 'Anda tidak berhak melakukan transaksi untuk anggota ini.');
        }

        $savingAccount = $this->simpananService->resolveOrCreateSavingAccount($data, $member);

        Log::info('Saving account for member', [
            'member_id'            => $member->id,
            'saving_account_id'    => $savingAccount->id,
            'was_recently_created' => $savingAccount->wasRecentlyCreated,
        ]);

        $this->simpananService->validateDepositRules($data, $savingAccount, $member);

        $prevBalance = $savingAccount->balance;
        $transaction = $this->simpananService->createDepositTransaction($data, $savingAccount, $member);

        Log::info('Deposit transaction created', [
            'transaction_id'    => $transaction->id,
            'saving_account_id' => $savingAccount->id,
            'amount'            => $transaction->saving_amount,
            'new_balance'       => $transaction->balance_after_transaction,
        ]);

        $strukData = [
            'no_transaksi'  => $transaction->saving_transaction_code,
            'tanggal'       => $transaction->transaction_date,
            'pengurus'      => Auth::user()->name,
            'nama_anggota'  => $member->user->name,
            'no_anggota'    => $member->user->user_code,
            'jenis'         => $data['saving_category'],
            'metode'        => $transaction->saving_payment_method,
            'nominal'       => $transaction->saving_amount,
            'saldo_sebelum' => $prevBalance,
            'saldo_sesudah' => $prevBalance + $transaction->saving_amount,
            'purpose'       => $data['purpose'] ?? null,
        ];

        $this->simpananService->storeReceiptDepositPdf($transaction, $strukData, $member->id);
        $transaction->refresh();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members'      => $this->simpananService->getMembersForDeposit(),
            'saving_types' => collect(SavingTypeEnum::cases())->map(fn($c) => $c->value),
            'pengurus'     => ['name' => Auth::user()->name ?? 'Pengurus'],
            'global_saving' => [
                'pokok' => $this->simpananService->getSettingValue('saving_pokok_amount'),
                'wajib' => $this->simpananService->getSettingValue('saving_wajib_amount'),
            ],
            'struk' => $strukData,
            'receipt' => Storage::url($transaction->saving_transaction_receipt),
        ]);
    }

    public function createWithdrawal()
    {
        $members = $this->getMembersForSavingSelection(true);

        return Inertia::render('Admin/Savings/Withdrawal/Create', [
            'members' => $members,
        ]);
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request)
    {
        try {
            $result = $this->simpananServices->storeWithdrawal(
                $request->validated(),
                Auth::id()
            );

            return redirect()
                ->route('admin.savings.withdrawal.create')
                ->with('struk', $result['struk'])
                ->with('receipt', $result['receipt']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    private function getMembersForSavingSelection(bool $includeBankAccounts = false)
    {
        $query = Member::query()
            ->when($includeBankAccounts, function ($q) {
                $q->with([
                    'user',
                    'savingAccounts.ibadah',
                    'savingAccounts.berjangka',
                    'bankAccounts' => function ($subQuery) {
                        $subQuery->latest();
                    },
                ]);
            }, function ($q) {
                $q->with(['user:id,user_code,name', 'savingAccounts.ibadah', 'savingAccounts.berjangka']);
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
                            'tenor_months' => $acc->berjangka?->tenor,
                            'target_amount' => $acc->ibadah?->target_amount,
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
                    'purpose' => $acc->ibadah?->purpose ?? $acc->berjangka?->purpose ?? null,
                    'balance' => $acc->balance ?? 0,
                    'target_amount' => $acc->ibadah?->target_amount ?? null,
                    'matured_at' => $acc->berjangka?->tenor && $acc->created_at
                        ? $acc->created_at->copy()->addMonths($acc->berjangka->tenor)->format('d M Y')
                        : null,
                    'is_frozen' => !is_null($acc->ibadah?->target_amount) && $acc->balance >= $acc->ibadah->target_amount,
                    'is_matured' => $acc->berjangka?->tenor && $acc->created_at
                        ? now()->gte($acc->created_at->copy()->addMonths($acc->berjangka->tenor))
                        : false,
                ]),
            ];
        });
    }
}