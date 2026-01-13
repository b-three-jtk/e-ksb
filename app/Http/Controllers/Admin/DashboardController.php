<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Enums\UserStatus;
use App\Models\Financing;
use Illuminate\Http\Request;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        // Date Range
        $startDate = $req->start_date ?? now()->startOfMonth();
        $endDate = $req->end_date ?? now()->endOfMonth();
        $filterBy = $req->filter_by ?? 'month';

        if ($filterBy == 'month') {
            $prevDate = Carbon::parse($startDate)->subMonth()->endOfMonth()->toDateString();
        } elseif ($filterBy == 'day') {
            $prevDate = Carbon::parse($startDate)->subDay()->toDateString();
        } elseif ($filterBy == 'year') {
            $prevDate = Carbon::parse($startDate)->subYear()->endOfYear()->toDateString();
        } else {
            $prevDate = Carbon::parse($startDate)->subDay()->toDateString();
        }

        // Get Active User Count sampai end date yang dipilih (batas akhir)
        $data['active_user_count'] = User::where('status', UserStatus::ACTIVE->value)
            ->where('created_at', '<=', $endDate)
            ->count();
        $activeUserCountPrev = User::where('status', UserStatus::ACTIVE->value)
            ->where('created_at', '<=', $prevDate)
            ->count();

        $data['active_user_percentage'] = $activeUserCountPrev == 0 ? 0 : round((($data['active_user_count'] - $activeUserCountPrev) / $activeUserCountPrev) * 100);

        $data['total_saving_amount'] = SavingAccount::sum('balance') ?? 0;
        $data['total_financing_amount'] = Loan::whereBetween('created_at', [$req->start_date ?? now()->startOfYear(), $req->end_date ?? now()->endOfYear()])
            ->sum('total_price') ?? 0;

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
