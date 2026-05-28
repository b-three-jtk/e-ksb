<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\Member;
use App\Models\MemberBankAccount;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    /**
     * Display the withdrawal creation page
     */
    public function create()
    {
        $members = Member::query()
            ->with([
                'user',
                'savingAccounts',
                'bankAccounts' => function ($q) {
                    $q->latest();
                },
            ])
            ->whereHas('user', function ($q) {
                $q->where('status', UserStatusEnum::ACTIVE->value);
            })
            ->get()
            ->map(function ($member) {
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
            });

        return Inertia::render('Admin/Savings/Withdrawal/Create', [
            'members' => $members,
        ]);
    }

    /**
     * Store withdrawal transaction
     */
    public function store(StoreWithdrawalRequest $request)
    {
        $validated = $request->validated();

        $member = Member::with('user')->findOrFail($validated['member_id']);
        $savingAccount = SavingAccount::findOrFail($validated['saving_account_id']);
        $savingBalance = $savingAccount->balance;

        // Verify that the saving account belongs to the member
        if ((int) $savingAccount->member_id !== (int) $member->id) {
            return back()
                ->withErrors(['saving_account_id' => 'Rekening simpanan tidak ditemukan untuk anggota ini']);
        }

        // Verify balance is sufficient
        if ($savingBalance < $validated['amount']) {
            return back()
                ->withErrors(['amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp ' . number_format($validated['amount'])]);
        }

        $savingType = (string) ($savingAccount->saving_type ?? '');
        $typeLower = mb_strtolower($savingType);

        // Berjangka can only be withdrawn after maturity date.
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

        // Ibadah can be withdrawn only when target amount has been reached.
        if (str_contains($typeLower, 'ibadah')) {
            $targetAmount = (float) ($savingAccount->target_amount ?? 0);
            if ($targetAmount > 0 && (float) $savingBalance < $targetAmount) {
                return back()->withErrors([
                    'saving_account_id' => 'Tabungan ibadah belum mencapai target minimal Rp ' . number_format($targetAmount, 0, ',', '.'),
                ]);
            }
        }

        try {
            [$transaction, $saldoSebelum] = DB::transaction(function () use ($validated, $member, $savingAccount) {
                $lockedSavingAccount = SavingAccount::query()
                    ->whereKey($savingAccount->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Get latest balance from database view (source of truth)
                $saldoSebelum = $lockedSavingAccount->balance;

                if ($saldoSebelum === null) {
                    // fallback to model field if view not available
                    $saldoSebelum = (float) ($lockedSavingAccount->balance ?? 0);
                } else {
                    $saldoSebelum = (float) $saldoSebelum;
                }

                if ($saldoSebelum < (float) $validated['amount']) {
                    throw new \RuntimeException('Saldo tidak cukup untuk penarikan.');
                }

                $transaction = SavingTransaction::create([
                    'saving_transaction_code' => $this->generateTransactionCode(),
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

            // Get admin/pengurus info
            $admin = auth()->user();
            $namaAdmin = $admin->name ?? 'Pengurus';

            // Prepare receipt data
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
                $receiptPath = $this->storeReceiptImage($transaction, $strukData);
                if ($receiptPath) {
                    $transaction->update([
                        'saving_transaction_receipt' => $receiptPath,
                    ]);
                }
            } catch (\Throwable $receiptException) {
                // Receipt generation should not break successful withdrawal posting.
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

    private function storeReceiptImage(SavingTransaction $transaction, array $strukData): ?string
    {
        return $this->storeReceiptViaPdf($transaction, $strukData);
    }

    /**
     * Generate receipt via dompdf and save as PDF.
     */
    private function storeReceiptViaPdf(SavingTransaction $transaction, array $strukData): ?string
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.withdrawal_receipt', [
                'struk' => $strukData,
            ])->setPaper([0, 0, 226.77, 600], 'portrait');

            $directory = 'member_docs/receipts/' . now()->format('Y-m');
            Storage::disk('public')->makeDirectory($directory);

            $filename = 'struk-withdrawal-' . $transaction->id . '.pdf';
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            // verify file saved
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

    /**
     * Generate unique transaction code
     */
    private function generateTransactionCode(): string
    {
        $date = Carbon::now()->format('d');
        $prefix = 'ST' . $date;

        $latestTransaction = SavingTransaction::where('transaction_type', TransactionTypeEnum::WITHDRAWAL->value)
            ->whereDate('created_at', Carbon::today())
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
