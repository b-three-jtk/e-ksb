<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
use App\Models\SavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $req)
    {
        $startDate = $req->start_date
            ? Carbon::parse($req->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $req->end_date
            ? Carbon::parse($req->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();
        $filterBy = $req->filter_by ?? 'month';

        // Get previous period dates
        [$prevStartDate, $prevEndDate] = $this->getPreviousPeriod($startDate, $filterBy);

        // Total financing amount from view
        $totalFinancingAmountQuery = DB::table('get_total_financing');

        if ($startDate && $endDate) {
            $totalFinancingAmountQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalFinancingAmount = $totalFinancingAmountQuery->sum('total_financing');

        $activeUserCount = User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $endDate)->count();

        return inertia('Admin/Dashboard', [
            'active_user_count' => $activeUserCount,
            'active_user_percentage' => $this->calculatePercentage(
                $activeUserCount,
                User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $prevEndDate)->count()
            ),
            'total_saving_amount' => DB::table('get_saving_account_balance')->sum('saldo_akhir') ?? '0',
            'total_financing_amount' => $totalFinancingAmount,
            'total_financing_percentage' => $this->calculatePercentage(
                $totalFinancingAmount,
                $totalFinancingAmountQuery->whereBetween('created_at', [$prevStartDate, $prevEndDate])->sum('total_financing') ?? 0
            ),
            'transaction_data' => $this->getRecentTransactions(),
            'registration_data' => $this->getPendingRegistrations(),
            'financing_data' => $this->getRecentFinancings(),
            'financing_stats' => $this->getFinancingStats($filterBy)
        ]);
    }

    private function getPreviousPeriod(Carbon $start, string $filterBy): array
    {
        return match ($filterBy) {
            'month' => [
                $start->copy()->subMonth()->startOfMonth(),
                $start->copy()->subMonth()->endOfMonth()
            ],
            'year' => [
                $start->copy()->subYear()->startOfYear(),
                $start->copy()->subYear()->endOfYear()
            ],
            default => [
                $start->copy()->subDay(),
                $start->copy()->subDay()
            ],
        };
    }

    private function calculatePercentage($current, $previous): int
    {
        return $previous == 0 ? 0 : round((($current - $previous) / $previous) * 100);
    }

    private function getRecentTransactions()
    {
        return SavingTransaction::with('savingAccount.user')
            ->latest()->take(5)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'transaction_code' => $t->saving_transaction_code,
                'user_name' => $t->savingAccount->user->name,
                'amount' => $t->amount,
                'type' => $t->type,
                'created_at' => $t->created_at->toDateTimeString(),
            ]);
    }

    private function getPendingRegistrations()
    {
        return User::where('status', UserStatusEnum::RESIGNED_REQUESTED->value)
            ->latest()->take(5)->get()
            ->map(fn($u) => [
                'member_code' => $u->member_code,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at,
            ]);
    }

    private function getRecentFinancings()
    {
        return Financing::with('user', 'financingProduct.product')
            ->latest()->take(5)->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'transaction_code' => $f->financing_transaction_code,
                'product_name' => $f->financingProduct->product->product_name ?? '-',
                'status' => $f->status,
                'member_code' => $f->user->member_code,
                'user_name' => $f->user->name,
                'created_at' => $f->created_at,
            ]);
    }

    private function getFinancingStats($filterBy = 'month')
    {
        return match ($filterBy) {
            'month' => $this->getMonthlyStats(),
            'day' => $this->getDailyStats(),
            'year' => $this->getYearlyStats(),
            default => $this->getMonthlyStats(),
        };
    }

    private function getMonthlyStats()
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $stats = Financing::selectRaw('EXTRACT(MONTH FROM created_at) AS month, COUNT(*) AS count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) use ($months) {
                return [$months[$item->month - 1] => $item->count];
            })
            ->toArray();

        // Fill semua bulan, jika tidak ada data = 0
        $result = [];
        foreach ($months as $month) {
            $result[$month] = $stats[$month] ?? 0;
        }

        return $result;
    }

    private function getDailyStats()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $daysInMonth = $endDate->day;

        $stats = Financing::selectRaw("EXTRACT(DAY FROM created_at)::INTEGER AS day, COUNT(*) AS count")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->day => $item->count];
            })
            ->toArray();

        // Fill semua hari bulan ini, jika tidak ada data = 0
        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $result[$day] = $stats[$day] ?? 0;
        }

        return $result;
    }

    private function getYearlyStats()
    {
        $currentYear = now()->year;
        $startYear = Financing::min('created_at') ? Carbon::parse(Financing::min('created_at'))->year : $currentYear;

        $stats = Financing::selectRaw("EXTRACT(YEAR FROM created_at)::INTEGER AS year, COUNT(*) AS count")
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->year => $item->count];
            })
            ->toArray();

        // Fill semua tahun dari tahun pertama data sampai sekarang
        $result = [];
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $result[$year] = $stats[$year] ?? 0;
        }

        return $result;
    }
}
