<?php

namespace App\Http\Controllers\Admin;

use App\Models\Loan;
use App\Models\User;
use App\Enums\UserStatus;
use App\Models\Financing;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['active_user_count'] = User::where('status', UserStatus::ACTIVE->value)->count();
        $data['total_saving_amount'] = SavingAccount::sum('balance') ?? 0;
        $data['total_financing_amount'] = Loan::sum('total_price') ?? 0;
        $data['transaction_data'] = SavingTransaction::with('savingAccount.user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user_name' => $transaction->savingAccount->user->name,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'created_at' => $transaction->created_at->toDateTimeString(),
                ];
            });
        $data['registration_data'] = User::with('workUnit')->where('status', UserStatus::INREVIEW->value)
            ->latest()
            ->take(5)
            ->get(['name', 'email', 'created_at'])
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'work_unit' => $user->workUnit?->name ?? '-',
                ];
            });
        $data['financing_data'] = Financing::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($financing) {
                return [
                    'id' => $financing->id,
                    'product_type' => $financing->product_type,
                    'status' => $financing->status,
                    'member_number' => $financing->user->id,
                    'user_name' => $financing->user->name,
                    'created_at' => $financing->created_at,
                ];
            });
        $data['financing_stats'] = Financing::selectRaw('EXTRACT(MONTH FROM created_at) AS month, COUNT(*) AS count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();
        return inertia('Admin/Dashboard', $data);
    }
}
