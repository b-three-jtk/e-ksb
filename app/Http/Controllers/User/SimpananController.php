<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Enums\SavingType;
use App\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
}
