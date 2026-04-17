<?php

namespace App\Http\Controllers\User;

use App\Models\Loan;
use App\Models\Financing;
use App\Models\LoanPayment;
use App\Models\LoanPaymentSchedule;
use DB;
use Illuminate\Http\Request;
use App\Enums\LoanPaymentStatus;
use App\Http\Controllers\Controller;
use App\Enums\LoanPaymentScheduleStatus;
use App\Http\Requests\CreateRepaymentRequest;

class UserRepaymentController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        $data = [];
        $data['financing'] = Financing::
            with('user', 'loan.paymentSchedules.payment')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $data['total_paid_installments'] = $data['financing']->loan->paymentSchedules->where('status', LoanPaymentScheduleStatus::PAID->value)->count();

        // Selisih harga cicilan dan tsaman naqdy
        $marginDiff = $data['financing']->loan->total_loan - $data['financing']->tsaman_naqdy;
        // Selisih harga cicilan dan tsaman naqdy per bulan
        if ($data['financing']->loan->tenor == 0) {
            $data['margin_diff_per_month'] = 0;
        } else {
            $data['margin_diff_per_month'] = $marginDiff / $data['financing']->loan->tenor;
        }

        $data['qimah_haliyyah'] = $data['financing']->tsaman_naqdy + ($data['margin_diff_per_month'] * ($data['total_paid_installments'] + 1)); // tambah satu untuk installment saat ini
        $data['payment_total'] = $data['financing']->loan->paymentSchedules->where('status', LoanPaymentScheduleStatus::PAID->value)->sum('total_amount');
        $data['repayment_total'] = $data['qimah_haliyyah'] - $data['payment_total'];

        return inertia('User/Financing/Repayment/Show', [
            'data' => $data,
        ]);
    }

    public function sendRequest(CreateRepaymentRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {

            $loanPaymentSchedules = LoanPaymentSchedule::with('loan.financing')->where('loan_id', $data['loan_id'])
                ->whereHas('loan.financing', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('loan', function ($query) use ($data) {
                    $query->where('id', $data['loan_id']);
                })
                ->where('status', '!=', LoanPaymentScheduleStatus::PAID->value)
                ->orderBy('installment_number', 'asc')
                ->get();

            // update all loan payment schedules where status is SCHEDULED to PENDING for early repayment
            $loanPaymentSchedules->each(function ($schedule) {
                $schedule->status = LoanPaymentScheduleStatus::PENDING->value;
                $schedule->save();
            });

            LoanPayment::create([
                'transaction_code' => 'LP' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), // temporary
                'amount' => $data['repayment_total'],
                'principal_paid' => $data['principal_paid'],
                'margin_paid' => $data['margin_paid'],
                'status' => LoanPaymentStatus::PENDING->value,
                'method' => $data['method'],
                'is_early_repayment' => true,
                'loan_payment_schedule_id' => $loanPaymentSchedules[0]->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return redirect()->route('user.userDashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => 'There was an error processing your repayment request. Please try again later.']);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
