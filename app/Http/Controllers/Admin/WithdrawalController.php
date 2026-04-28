<?php

namespace App\Http\Controllers\Admin;

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
        $members = User::with([
                'savingAccounts',
                'accounts' => function ($q) {
                    $q->latest();
                }
            ])
            ->where('status', UserStatusEnum::ACTIVE->value)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_code' => $user->user_code,
                    'savingAccounts' => $user->savingAccounts->map(function ($acc) {
                        return [
                            'id' => $acc->id,
                            'type' => $acc->type,
                            'balance' => DB::table('get_saving_account_balance')->where('saving_account_id', $acc->id)->value('total_balance') ?? 0,
                            'tenor_months' => $acc->tenor_months,
                            'target_amount' => $acc->target_amount,
                            'opened_at' => optional($acc->created_at)->toDateString(),
                        ];
                    })->toArray(),
                    'accounts' => $user->accounts->map(function ($acc) {
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:users,id',
            'saving_account_id' => 'required|exists:saving_accounts,id',
            'amount' => 'required|numeric|min:1',
            'withdrawal_date' => 'required|date|before_or_equal:today',
            'method' => 'required|in:Tunai,Non-Tunai',
            'bank_name' => 'required_if:method,Non-Tunai|nullable|string',
            'account_name' => 'required_if:method,Non-Tunai|nullable|string',
            'account_number' => 'required_if:method,Non-Tunai|nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $member = User::findOrFail($validated['member_id']);
        $savingAccount = SavingAccount::findOrFail($validated['saving_account_id']);
        $savingBalance = DB::table('get_saving_account_balance')->where('saving_account_id', $savingAccount->id)->value('total_balance') ?? 0;

        // Verify that the saving account belongs to the member
        if ($savingAccount->user_id !== $member->id) {
            return back()
                ->withErrors(['saving_account_id' => 'Rekening simpanan tidak ditemukan untuk anggota ini']);
        }

        // Verify balance is sufficient
        if ($savingBalance < $validated['amount']) {
            return back()
                ->withErrors(['amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp ' . number_format($validated['amount'])]);
        }

        $typeLower = mb_strtolower((string) $savingAccount->type);

        // Berjangka can only be withdrawn after maturity date.
        if (str_contains($typeLower, 'berjangka')) {
            $tenorMonths = (int) ($savingAccount->tenor_months ?? 0);
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
            if ($targetAmount > 0 && (float) $savingAccount->balance < $targetAmount) {
                return back()->withErrors([
                    'saving_account_id' => 'Tabungan ibadah belum mencapai target minimal Rp ' . number_format($targetAmount, 0, ',', '.'),
                ]);
            }
        }

        try {
            [$transaction, $saldoSebelum] = DB::transaction(function () use ($validated, $member, $savingAccount) {
                $saldoSebelum = $savingAccount->balance;

                $transaction = SavingTransaction::create([
                    'saving_transaction_code' => $this->generateTransactionCode(),
                    'saving_account_id' => $savingAccount->id,
                    'saving_amount' => $validated['amount'],
                    'transaction_type' => TransactionTypeEnum::WITHDRAWAL->value,
                    'saving_payment_method' => $validated['method'],
                    'transaction_date' => now(),
                    'saving_description' => $validated['notes'] ?? '',
                    'updated_by' => auth()->id(),
                ]);

                if ($validated['method'] === 'Non-Tunai') {
                    Account::updateOrCreate(
                        [
                            'user_id' => $member->id,
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

                $savingAccount->update([
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
                'no_transaksi' => str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                'tanggal' => $transaction->transaction_date,
                'pengurus' => $namaAdmin,
                'nama_anggota' => $member->name,
                'no_anggota' => $member->user_code,
                'jenis' => $savingAccount->type,
                'metode' => $validated['method'],
                'nominal' => $validated['amount'],
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSebelum - $validated['amount'],
                'bank_name' => $validated['bank_name'] ?? '',
                'account_name' => $validated['account_name'] ?? '',
                'account_number' => $validated['account_number'] ?? '',
            ];

            $this->storeReceiptPdf($transaction, $strukData);

            return redirect()
                ->route('admin.withdrawal.create')
                ->with('success', 'Penarikan simpanan berhasil disimpan')
                ->with('struk', $strukData);

        } catch (\Exception $e) {
            report($e);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate and store withdrawal receipt as PDF into saving_transaction_docs.
     */
    private function storeReceiptPdf(SavingTransaction $transaction, array $strukData): void
    {
        $pdf = Pdf::loadView('exports.withdrawal_receipt', [
            'struk' => $strukData,
        ])->setPaper([0, 0, 226.77, 650]);

        $directory = 'saving-transactions/' . now()->format('Y-m');
        $filename = 'struk-withdrawal-' . $transaction->id . '.pdf';
        $path = $directory . '/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        $existingDoc = $transaction->receipt;

        if ($existingDoc && $existingDoc->attachment) {
            Storage::disk('public')->delete($existingDoc->attachment);
        }
    }

    /**
     * Generate unique transaction code
     */
    private function generateTransactionCode()
    {
        $date = Carbon::now()->format('d');
        $latestTransaction = SavingTransaction::where('transaction_type', 'Penarikan')
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

        if ($latestTransaction) {
            $codeNumber = intval(substr($latestTransaction->transaction_code, -4)) + 1;
        } else {
            $codeNumber = 1;
        }

        return 'W' . $date . str_pad($codeNumber, 4, '0', STR_PAD_LEFT);
    }
}
