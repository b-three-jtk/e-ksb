<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
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
    public function index()
    {
        //
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
