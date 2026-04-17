<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
use Illuminate\Support\Facades\Request;
use LogicException;

class RepaymentController extends Controller
{
    public function validation(string $id)
    {
        $data = [];
        $data['financing'] = Financing::
            with('user', 'loan.paymentSchedules.payment')
            ->whereHas('loan.paymentSchedules', function ($query) use ($id) {
                $query->where('status', InstallmentPaymentScheduleStatusEnum::PENDING->value)->orderBy('installment_number', 'asc');
            })
            ->findOrFail($id);

        $data['total_paid_installments'] = $data['financing']->loan->paymentSchedules->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();

        // Selisih harga cicilan dan tsaman naqdy
        $marginDiff = $data['financing']->loan->total_loan - $data['financing']->tsaman_naqdy;
        // Selisih harga cicilan dan tsaman naqdy per bulan
        if ($data['financing']->loan->tenor == 0) {
            $data['margin_diff_per_month'] = 0;
        } else {
            $data['margin_diff_per_month'] = $marginDiff / $data['financing']->loan->tenor;
        }

        $data['qimah_haliyyah'] = $data['financing']->tsaman_naqdy + ($data['margin_diff_per_month'] * ($data['total_paid_installments'] + 1)); // tambah satu untuk installment saat ini
        $data['payment_total'] = $data['financing']->loan->paymentSchedules->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->sum('total_amount');
        $data['repayment_total'] = $data['qimah_haliyyah'] - $data['payment_total'];

        return inertia('Admin/Repayment/Validation', [
            'data' => $data,
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $financing = Financing::
            with('user', 'loan.paymentSchedules.payment')
            ->findOrFail($id);

        if ($request->input('action') === 'accept') {
            // Logic to accept the repayment validation
            $financing->loan->paymentSchedules()
                ->where('status', InstallmentPaymentScheduleStatusEnum::PENDING->value)
                ->orderBy('installment_number', 'asc')
                ->first()
                ->update(['status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value]);
        } elseif ($request->input('action') === 'reject') {
            // Logic to reject the repayment validation
            $financing->loan->paymentSchedules()
                ->where('status', InstallmentPaymentScheduleStatusEnum::PENDING->value)
                ->orderBy('installment_number', 'asc')
                ->first()
                ->update(['status' => InstallmentPaymentScheduleStatusEnum::REJECTED->value]);
        } else {
            throw new LogicException('Invalid action specified.');
        }

        return redirect()->route('admin.repayments.validation', ['id' => $id])
            ->with('success', 'Validasi pelunasan berhasil diproses.');
    }
}
