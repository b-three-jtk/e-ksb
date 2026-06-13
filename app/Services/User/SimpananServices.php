<?php

namespace App\Services\User;

use App\Enums\TransactionTypeEnum;
use App\Enums\PositionEnum;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\MemberBankAccount;
use App\Models\Account;
use App\Services\Admin\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SimpananServices
{
    public function __construct(private JournalService $journalService)
    {
    }

    public function storeWithdrawal(array $validated, string $userId): array
    {
        $member = Member::with('user')->findOrFail($validated['member_id']);
        $savingAccount = SavingAccount::with(['ibadah', 'berjangka'])->findOrFail($validated['saving_account_id']);
        $savingBalance = $savingAccount->balance;

        if ((int) $savingAccount->member_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'saving_account_id' => 'Rekening simpanan tidak ditemukan untuk anggota ini'
            ]);
        }

        if ($savingBalance < $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp ' . number_format($validated['amount'])
            ]);
        }

        $savingType = (string) ($savingAccount->saving_type ?? '');
        $typeLower = mb_strtolower($savingType);

        if (str_contains($typeLower, 'berjangka')) {
            $tenorMonths = (int) ($savingAccount->berjangka?->tenor ?? 0);
            if ($tenorMonths > 0 && $savingAccount->created_at) {
                $maturityDate = Carbon::parse($savingAccount->created_at)->addMonths($tenorMonths)->startOfDay();
                if (Carbon::today()->lt($maturityDate)) {
                    throw ValidationException::withMessages([
                        'saving_account_id' => 'Tabungan berjangka belum jatuh tempo. Pencairan dapat dilakukan mulai ' . $maturityDate->format('d/m/Y'),
                    ]);
                }
            }
        }

        if (str_contains($typeLower, 'ibadah')) {
            $targetAmount = (float) ($savingAccount->ibadah?->target_amount ?? 0);
            if ($targetAmount > 0 && (float) $savingBalance < $targetAmount) {
                throw ValidationException::withMessages([
                    'saving_account_id' => 'Tabungan ibadah belum mencapai target minimal Rp ' . number_format($targetAmount, 0, ',', '.'),
                ]);
            }
        }

        [$transaction, $saldoSebelum] = DB::transaction(function () use ($validated, $member, $savingAccount, $savingType, $userId) {
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
                'updated_by' => $userId,
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

        $kasAccount = Account::where('account_name', 'Kas')->firstOrFail();
        $savingAccountRef = Account::where('account_name', $savingType)->firstOrFail();

        $this->journalService->create(
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
            $userId
        );

        $strukData = [
            'transaction_id' => $transaction->id,
            'no_transaksi' => $transaction->saving_transaction_code,
            'tanggal' => $transaction->transaction_date,
            'pengurus' => auth()->user()->name ?? 'Pengurus',
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

        return $strukData;
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
}
