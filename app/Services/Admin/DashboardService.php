<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\Member;
use App\Models\SavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function getPreviousPeriod(Carbon $start, string $filterBy): array
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

    public function calculatePercentage($current, $previous): int
    {
        return $previous == 0 ? 0 : round((($current - $previous) / $previous) * 100);
    }

    public function getMonthlyStats()
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

    public function getDailyStats()
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

    public function getYearlyStats()
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

    public function getTotalActiveMember($endDate, $prevEndDate): array
    {
        $total = User::where('status', UserStatusEnum::ACTIVE->value)->with('roles')->whereHas(
            'roles',
            fn($q) =>
            $q->where('name', 'Anggota')
        )->where('created_at', '<=', $endDate)->count();

        $percentage = $this->calculatePercentage(
            $total,
            User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $prevEndDate)->count()
        );

        return [$total, $percentage];
    }

    public function getTotalInactiveMember($endDate, $prevEndDate): array
    {
        $total = User::where('status', UserStatusEnum::INACTIVE->value)->with('roles')->whereHas(
            'roles',
            fn($q) =>
            $q->where('name', 'Anggota')
        )->where('created_at', '<=', $endDate)->count();

        $percentage = $this->calculatePercentage(
            $total,
            User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $prevEndDate)->count()
        );

        return [$total, $percentage];
    }

    public function getTotalStaff($endDate, $prevEndDate): array
    {
        $total = User::where('status', UserStatusEnum::ACTIVE->value)->with('roles')->whereHas(
            'roles',
            fn($q) =>
            $q->where('name', '!=', 'Anggota')
        )->where('created_at', '<=', $endDate)->count();

        $percentage = $this->calculatePercentage(
            $total,
            User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $prevEndDate)->count()
        );

        return [$total, $percentage];
    }

    public function getAkadSimpanan($savingType): string
    {
        switch ($savingType) {
            case SavingTypeEnum::SIMPANAN_POKOK->value:
                return 'Musyarakah';
            case SavingTypeEnum::SIMPANAN_WAJIB->value:
                return 'Musyarakah';
            case SavingTypeEnum::TABUNGAN_ANGGOTA->value:
                return 'Wadiah Yad Dhamanah';
            case SavingTypeEnum::TABUNGAN_BERJANGKA->value:
                return 'Mudharabah Mutlaqah';
            case SavingTypeEnum::TABUNGAN_IBADAH->value:
                return 'Mudharabah Mutlaqah';
            default:
                return null;
        }
    }

    public function getAllRecentTransactions($filter)
    {
        // join saving transactions dan financing
        $savingTransactions = SavingTransaction::with('savingAccount.member.user')
            ->latest()->take(5)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'transaction_code' => $t->saving_transaction_code,
                'user_name' => $t->savingAccount->member->user->name,
                'amount' => $t->amount,
                'product' => $t->savingAccount->saving_type,
                'akad' => $this->getAkadSimpanan($t->savingAccount->saving_type),
                'created_at' => $t->created_at->toDateString(),
            ]);

        $financingTransactions = Financing::with('member.user', 'financingItem')
            ->latest()->take(5)->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'transaction_code' => $f->financing_transaction_code,
                'user_name' => $f->member->user->name,
                'amount' => $f->amount,
                'product' => 'Pembiayaan',
                'akad' => 'Murabahah',
                'created_at' => $f->created_at->toDateString(),
            ]);

        // gabungkan dan urutkan berdasarkan created_at
        $allTransactions = $filter === 'all' ? $savingTransactions->concat($financingTransactions)
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->toArray() : ($filter === 'simpanan' ? $savingTransactions : $financingTransactions)->toArray();

        return $allTransactions;
    }

    public function getRevenues($startDate, $endDate)
    {
        $financings = Financing::whereIn('status', [
            FinancingReqStatusEnum::PAID->value,
            FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value
        ])
            ->whereHas('installment.payment', function ($query) use ($startDate, $endDate) {
                // Memastikan hanya mengambil financing yang punya pembayaran di range tanggal tersebut
                $query->where('payment_date', '>=', $startDate)
                    ->where('payment_date', '<=', $endDate);
            })
            ->with([
                'installment.payment' => function ($query) use ($startDate, $endDate) {
                    // Eager loading hanya pembayaran yang sesuai range tanggal saja
                    $query->where('payment_date', '>=', $startDate)
                        ->where('payment_date', '<=', $endDate);
                }
            ])
            ->get();

        // Karena satu Financing bisa punya beberapa Payment di bulan yang berbeda,
        // pecah dulu datanya per baris Payment agar bisa di-groupBy berdasarkan bulan payment_date.
        $paymentRows = collect();

        foreach ($financings as $financing) {
            $marginPerMonth = $financing->margin_amount / $financing->installment->tenor;

            // Ambil semua payment milik financing ini yang lolos filter tanggal
            foreach ($financing->installment->payment as $payment) {
                $paymentRows->push([
                    'month' => Carbon::parse($payment->payment_date)->format('Y-m'),
                    'margin' => $marginPerMonth,
                    'financing_id' => $financing->id,
                    'payment_id' => $payment->id
                ]);
            }
        }

        // Kelompokkan data payment berdasarkan bulan ('Y-m')
        $groupedByMonth = $paymentRows->groupBy('month');

        // Hitung total margin per bulan
        $revenuesPerMonth = $groupedByMonth->map(function ($paymentsInMonth, $monthString) {
            return $paymentsInMonth->sum(function ($paymentRow) use ($monthString) {
                Log::info("Bulan Payment: {$monthString}, Financing ID: {$paymentRow['financing_id']}, Payment ID: {$paymentRow['payment_id']}, Margin: {$paymentRow['margin']}");

                return $paymentRow['margin'];
            });
        });

        // Angka ini adalah real uang margin yang masuk dari cicilan di bulan tersebut.
        return $revenuesPerMonth;
    }
    public function getMemberGrowth($startDate, $endDate, $filterBy)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $skeleton = collect();
        $format = '';

        // 1. Buat Skeleton secara manual
        if ($filterBy === 'month') {
            // PAKSA bikin 12 bulan (Jan - Dec) berdasarkan tahun dari $endDate
            $year = $end->year;
            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::create($year, $m, 1);
                $skeleton->put($date->format('M'), 0); // Output: Jan, Feb, Mar, dst.
            }
            $format = 'M';
        } else if ($filterBy === 'year') {
                // PAKSA bikin 5 tahun terakhir berdasarkan tahun dari $endDate
            $endYear = $end->year;
            for ($y = $endYear - 4; $y <= $endYear; $y++) {
                $date = Carbon::create($y, 1, 1);
                $skeleton->put($date->format('Y'), 0); // Output: 2020, 2021, 2022, dst.
            }
            $format = 'Y';
        } else {
            // Untuk 'day' dan 'year', tetap gunakan CarbonPeriod sesuai rentang tanggal
            $format = 'd M'; // Default to day
            $period = CarbonPeriod::create($start, '1 day', $end);

            foreach ($period as $date) {
                $skeleton->put($date->format($format), 0);
            }
        }
        $queryStart = $filterBy === 'month' || $filterBy === 'year' ? $end->copy()->startOfYear() : $start->startOfDay();
        $queryEnd = $filterBy === 'month' || $filterBy === 'year' ? $end->copy()->endOfYear() : $end->endOfDay();

        $queryData = User::where('status', UserStatusEnum::ACTIVE->value)
            ->with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', 'Anggota'))
            ->whereBetween('created_at', [$queryStart, $queryEnd])
            ->get()
            ->groupBy(fn($user) => $user->created_at->format($format))
            ->map(fn($group) => $group->count());

        $result = $skeleton->merge($queryData);

        Log::info("Member Growth Data: " . $result->toJson());

        return $result->toArray();
    }
}
