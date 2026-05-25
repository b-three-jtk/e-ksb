<?php

namespace App\Http\Controllers\User;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financing;
use App\Models\InstallmentPaymentSchedule;
use App\Models\InstallmentPaymentTransaction;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            with('user', 'installment.paymentSchedules.payment')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $data['total_paid_installments'] = $data['financing']->installment->paymentSchedules->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();

        // Selisih harga cicilan dan tsaman naqdy
        $marginDiff = $data['financing']->installment->total_loan - $data['financing']->tsaman_naqdy;
        // Selisih harga cicilan dan tsaman naqdy per bulan
        if ($data['financing']->installment->tenor == 0) {
            $data['margin_diff_per_month'] = 0;
        } else {
            $data['margin_diff_per_month'] = $marginDiff / $data['financing']->installment->tenor;
        }

        $data['qimah_haliyyah'] = $data['financing']->tsaman_naqdy + ($data['margin_diff_per_month'] * ($data['total_paid_installments'] + 1)); // tambah satu untuk installment saat ini
        $data['payment_total'] = $data['financing']->installment->paymentSchedules->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->sum('total_amount');
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

            $InstallmentPaymentSchedule = InstallmentPaymentSchedule::with('installment.financing')->where('installment_id', $data['loan_id'])
                ->whereHas('installment.financing', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('installment', function ($query) use ($data) {
                    $query->where('id', $data['loan_id']);
                })
                ->where('status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value)
                ->orderBy('installment_number', 'asc')
                ->get();

            // update all loan payment schedules where status is SCHEDULED to PENDING for early repayment
            $InstallmentPaymentSchedule->each(function ($schedule) {
                $schedule->status = InstallmentPaymentScheduleStatusEnum::PENDING->value;
                $schedule->save();
            });

            InstallmentPaymentTransaction::create([
                'installment_trans_code' => 'LP' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), // temporary
                'principal_paid' => $data['principal_paid'],
                'margin_paid' => $data['margin_paid'],
                'installment_payment_method' => $data['method'],
                'is_early_repayment' => true,
                'payment_date' => now(),
                'installment_payment_schedule_id' => $InstallmentPaymentSchedule[0]->id,
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
