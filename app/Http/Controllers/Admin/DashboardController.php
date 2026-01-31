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

        return inertia('Admin/Dashboard', [
            'active_user_count' => User::where('status', UserStatus::ACTIVE->value)
                ->where('created_at', '<=', $endDate)->count(),
            'active_user_percentage' => $this->calculatePercentage(
                User::where('status', UserStatus::ACTIVE->value)->where('created_at', '<=', $endDate)->count(),
                User::where('status', UserStatus::ACTIVE->value)->where('created_at', '<=', $prevEndDate)->count()
            ),
            'total_saving_amount' => SavingAccount::sum('balance') ?? '0',
            'total_financing_amount' => Loan::whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_price') ?? '0',
            'total_financing_percentage' => $this->calculatePercentage(
                Loan::whereBetween('created_at', [$startDate, $endDate])->sum('total_price') ?? 0,
                Loan::whereBetween('created_at', [$prevStartDate, $prevEndDate])->sum('total_price') ?? 0
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
                'transaction_code' => $t->transaction_code,
                'user_name' => $t->savingAccount->user->name,
                'amount' => $t->amount,
                'type' => $t->type,
                'created_at' => $t->created_at->toDateTimeString(),
            ]);
    }

    private function getPendingRegistrations()
    {
        return User::with('workUnit')
            ->where('status', UserStatus::INREVIEW->value)
            ->latest()->take(5)->get()
            ->map(fn($u) => [
                'member_number' => $u->member_number,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at,
                'work_unit' => $u->workUnit?->name ?? '-',
            ]);
    }

    private function getRecentFinancings()
    {
        return Financing::with('user')
            ->latest()->take(5)->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'transaction_code' => $f->transaction_code,
                'product_type' => $f->product_type,
                'status' => $f->status,
                'member_number' => $f->user->member_number,
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
