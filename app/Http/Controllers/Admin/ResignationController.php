<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\WorkUnit;
use App\Enums\LoanStatus;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use App\Enums\FinancingReqStatus;
use App\Http\Controllers\Controller;

class ResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $per_page = $request->input('per_page', 10);
        $sort_by = $request->input('sort_by', 'created_at');
        $sort_dir = $request->input('sort_dir', 'desc');
        $work_unit_id = $request->input('work_unit_id', '');

        $query = User::with('workUnit')
            ->where('status', UserStatus::RESIGNED_REQUESTED)
            ->when($search, function ($q) use ($search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($work_unit_id, function ($q) use ($work_unit_id) {
                return $q->where('work_unit_id', $work_unit_id);
            });

        // Apply sorting
        $query->orderBy($sort_by, $sort_dir);

        // Paginate results
        $members = $query->paginate($per_page)->withQueryString();

        // Get all work units for filter
        $workUnits = WorkUnit::all(['id', 'name']);

        return inertia('Admin/User/Resignation/List', [
            'members' => $members,
            'workUnits' => $workUnits,
            'filters' => [
                'search' => $search,
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
                'work_unit_id' => $work_unit_id,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function validation(string $id)
    {
        $user = User::with('userDocs', 'workUnit', 'savingAccounts', 'financings.loan')->where('status', UserStatus::RESIGNED_REQUESTED)->findOrFail($id);
        $user->userDocs()->where('name', 'Dokumen Pengunduran Diri')->first();
        $user->userDocs->first()->attachment = $user->userDocs->first()->attachment ? asset('storage/' . $user->userDocs->first()->attachment) : null;

        $totalSavings = $user->savingAccounts()->sum('balance');
        $totalObligation = $user->financings()
            ->whereIn('status', [
                FinancingReqStatus::APPROVED,
                FinancingReqStatus::APPROVED_WITH_NOTES,
            ])
            ->whereHas('loan')
            ->with([
                'loan.payments' => fn($q) =>
                    $q->where('status', LoanStatus::PAID)
            ])
            ->get()
            ->sum(function ($financing) {
                $loan = $financing->loan;
                if (!$loan)
                    return 0;

                $totalPaid = $loan->payments->sum('amount');
                return max($loan->total_price - $totalPaid, 0);
            });
        return inertia('Admin/User/Resignation/Validation', [
            'data' => $user,
            'total_savings' => $totalSavings,
            'total_obligation' => $totalObligation,
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $user = User::where('status', UserStatus::RESIGNED_REQUESTED)->findOrFail($id);
        $request->status === 'reject' ? $user->status = UserStatus::RESIGNED_REJECTED : $user->status = UserStatus::INACTIVE;
        $user->save();

        return to_route('admin.resignations.index')->with('success', 'Pengunduran diri berhasil divalidasi.');
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }
}
