<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $req, DashboardService $service)
    {
        $data = [];
        $startDate = $req->start_date
            ? Carbon::parse($req->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $req->end_date
            ? Carbon::parse($req->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();
        $filterBy = $req->filter_by ?? 'month';

        // Get previous period dates
        [$prevStartDate, $prevEndDate] = $service->getPreviousPeriod($startDate, $filterBy);

        [$data['total_active_member'], $data['total_active_member_percentage']] = $service->getTotalActiveMember($endDate, $prevEndDate);

        [$data['total_inactive_member'], $data['total_inactive_member_percentage']] = $service->getTotalInactiveMember($endDate, $prevEndDate);

        [$data['total_staff'], $data['total_staff_percentage']] = $service->getTotalStaff($endDate, $prevEndDate);

        $data['recent_transactions'] = $service->getAllRecentTransactions($req->transaction_filter ?? 'all');

        $data['revenues'] = $service->getRevenues($startDate, $endDate);

        $data['member_growth'] = $service->getMemberGrowth($startDate, $endDate, $filterBy);

        return inertia('Admin/Dashboard', [
            'data' => $data,
        ]);
    }
}
