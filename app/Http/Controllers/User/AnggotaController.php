<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SavingTransaction;
use Carbon\Carbon;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalSaving = $user->savingAccounts()->sum('balance');

        $totalInstallment = $user->financings()
            ->whereHas('loan')
            ->with('loan')
            ->get()
            ->sum(fn ($f) => $f->loan->amount_ins ?? 0);

        $ledger = SavingTransaction::whereHas(
            'savingAccount',
            fn ($q) => $q->where('user_id', $user->id)
        )
        ->with('savingAccount')
        ->latest('transaction_date')
        ->limit(5)
        ->get()
        ->map(function ($trx) {
            return [
                'date'    => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                'product' => $trx->savingAccount->type,
                'type'    => $trx->type,
                'amount'  => 'Rp ' . number_format($trx->amount, 0, ',', '.'),
            ];
        });

        $ledgerCount = SavingTransaction::whereHas(
            'savingAccount',
            fn ($q) => $q->where('user_id', $user->id)
        )
        ->whereMonth('transaction_date', now()->month)
        ->whereYear('transaction_date', now()->year)
        ->count();

        return inertia('User/Dashboard', [
            'summary' => [
                'total_saving' => $totalSaving,
                'total_installment' => $totalInstallment,
                'ledger_count' => $ledgerCount,
            ],
            'ledger' => $ledger,
        ]);
    }
}
