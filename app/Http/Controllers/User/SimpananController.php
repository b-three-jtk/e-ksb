<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\SavingTransactionDoc;
use App\Enums\SavingType;
use App\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SimpananController extends Controller
{
    /**
     * Show withdrawal information page
     */
    public function showWithdrawalInfo(Request $request)
    {
        $user = $request->user();
        
        // Get Simpanan Sukarela account
        $savingAccount = SavingAccount::where('user_id', $user->id)
            ->where('type', SavingType::SIMPANAN_SUKARELA->value)
            ->first();

        if (!$savingAccount) {
            return redirect()->route('user.userDashboard')
                ->with('error', 'Akun simpanan sukarela tidak ditemukan');
        }

        return inertia('User/Simpanan/Penarikan/Informasi', [
            'user' => [
                'name' => $user->name,
                'member_number' => $user->member_number,
            ],
            'savingAccount' => [
                'balance' => $savingAccount->balance,
            ],
            'withdrawalDate' => Carbon::now()->format('d F Y'),
        ]);
    }

    /**
     * Show withdrawal detail page
     */
    public function showWithdrawalDetail(Request $request)
    {
        $user = $request->user();
        
        // Get Simpanan Sukarela account
        $savingAccount = SavingAccount::where('user_id', $user->id)
            ->where('type', SavingType::SIMPANAN_SUKARELA->value)
            ->first();

        if (!$savingAccount) {
            return redirect()->route('user.userDashboard')
                ->with('error', 'Akun simpanan sukarela tidak ditemukan');
        }

        // Get previous form data if coming from pernyataan page
        $amount = $request->input('amount', '');
        $description = $request->input('description', '');
        $method = $request->input('method', 'Tunai');
        $bankName = $request->input('bank_name', '');
        $accountNumber = $request->input('account_number', '');
        $accountName = $request->input('account_name', '');

        return inertia('User/Simpanan/Penarikan/Detail', [
            'maxBalance' => $savingAccount->balance,
            'previousData' => [
                'amount' => $amount,
                'description' => $description,
                'method' => $method,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
            ]
        ]);
    }

    /**
     * Show withdrawal statement page
     */
    public function showWithdrawalStatement(Request $request)
    {
        $withdrawalData = [
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'method' => $request->input('method'),
            'bank_name' => $request->input('bank_name'),
            'account_number' => $request->input('account_number'),
            'account_name' => $request->input('account_name'),
        ];

        return inertia('User/Simpanan/Penarikan/Pernyataan', [
            'withdrawalData' => $withdrawalData,
        ]);
    }

    /**
     * Submit withdrawal transaction
     */
    public function submitWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'method' => 'required|in:Tunai,Non-Tunai',
            'bank_name' => 'required_if:method,Non-Tunai|string|nullable',
            'account_number' => 'required_if:method,Non-Tunai|string|nullable',
            'account_name' => 'required_if:method,Non-Tunai|string|nullable',
            'agreed' => 'required|boolean|accepted',
        ]);

        $user = $request->user();
        
        // Get Simpanan Sukarela account
        $savingAccount = SavingAccount::where('user_id', $user->id)
            ->where('type', SavingType::SIMPANAN_SUKARELA->value)
            ->first();

        if (!$savingAccount) {
            return redirect()->back()
                ->withErrors(['error' => 'Akun simpanan sukarela tidak ditemukan']);
        }

        // Check if balance is sufficient
        if ($validated['amount'] > $savingAccount->balance) {
            return redirect()->back()
                ->withErrors(['amount' => 'Saldo tidak mencukupi untuk penarikan ini']);
        }

        // Build description with bank details if Non-Tunai
        $fullDescription = $validated['description'];
        if ($validated['method'] === 'Non-Tunai') {
            $fullDescription .= sprintf(
                "\nBank: %s\nNo. Rekening: %s\nAtas Nama: %s",
                $validated['bank_name'],
                $validated['account_number'],
                $validated['account_name']
            );
        }

        // Create transaction
        $transaction = SavingTransaction::create([
            'id' => Str::uuid()->toString(),
            'amount' => $validated['amount'],
            'type' => 'Penarikan',
            'status' => TransactionStatus::PENDING->value,
            'method' => $validated['method'],
            'description' => $fullDescription,
            'transaction_date' => Carbon::now(),
            'updated_by' => $user->id,
            'saving_account_id' => $savingAccount->id,
        ]);

        return redirect()->route('user.userDashboard')
            ->with('success', 'Permohonan penarikan simpanan berhasil diajukan dan sedang dalam peninjauan');
    }

    public function createDeposit(Request $request) 
    {
        $user = $request->user();

        $savingAccounts = $user->savingAccounts()
            ->select('id', 'type', 'balance')
            ->get();

        $totalBalance = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('savingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalPerCategory = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('SavingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->selectRaw("
                saving_accounts.type as category,
                SUM(
                    CASE
                        WHEN saving_transactions.type = 'Penyetoran' THEN saving_transactions.amount
                        WHEN saving_transactions.type = 'Penarikan' THEN -saving_transactions.amount
                    END
                ) as total
            ")
            ->join('saving_accounts', 'saving_accounts.id', '=', 'saving_transactions.saving_account_id')
            ->groupBy('saving_accounts.type')
            ->pluck('total', 'category');

        $accounts = $user->accounts()
            ->select('account_number', 'account_name', 'bank_name')
            ->get()
            ->toArray();

            return inertia('User/Simpanan/Penyetoran/Create', [
                'totalBalance' => $totalBalance,
                'totalPerCategory' => [
                    'pokok' => $totalPerCategory['Simpanan Pokok'] ?? 0,
                    'wajib' => $totalPerCategory['Simpanan Wajib'] ?? 0,
                    'sukarela' => $totalPerCategory['Simpanan Sukarela'] ?? 0,
                ],
                'accounts' => $accounts,
                'member' => $user,
                'savingAccounts' => $savingAccounts
            ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'saving_account_id' => 'required|exists:saving_accounts,id',
            'method' => 'required|in:Tunai,Non-Tunai',
            'saving_category' => 'required|in:Simpanan Pokok,Simpanan Wajib,Simpanan Sukarela',
        ]);

        if ($request->method === 'Non-Tunai') {
            $request->validate([
                'account_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
                'bank_name' => 'required|string|max:255',
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);
        }

        DB::transaction(function () use ($request) {

            $savingAccount = SavingAccount::find($request->saving_account_id);

            if (!$savingAccount || $savingAccount->type !== $request->saving_category) {
                throw new \Exception('Kategori simpanan tidak sesuai dengan akun simpanan');
            }

             $transaction = SavingTransaction::create([
                'amount' => $request->amount,
                'type' => 'Penyetoran',
                'status' => 'Belum Ditinjau',
                'method' => $request->method,
                'transaction_date' => now(),
                'saving_account_id' => $request->saving_account_id,
                'account_number' => $request->method === 'Non-Tunai' ? $request->account_number : null,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->method === 'Non-Tunai' && $request->hasFile('payment_proof')) {
                $path = $request->file('payment_proof')
                    ->store('saving-transactions', 'public');

                SavingTransactionDoc::create([
                    'transaction_id' => $transaction->id,
                    'name' => 'Bukti Penyetoran',
                    'attachment' => $path,
                ]);
            }
        });

        return redirect()
            ->route('user.userDashboard')
            ->with('success', 'Simpanan berhasil diajukan');
    }
}
