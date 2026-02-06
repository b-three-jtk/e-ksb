<?php

namespace App\Http\Controllers\User;

use App\Models\Loan;
use App\Models\Financing;
use App\Models\LoanPayment;
use App\Models\LoanPaymentSchedule;
use Illuminate\Http\Request;
use GuzzleHttp\Promise\Create;
use App\Enums\LoanPaymentStatus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Enums\LoanPaymentScheduleStatus;
use App\Http\Requests\CreateRepaymentRequest;

class UserRepaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $data = [];
        $data['financing'] = Financing::with('user', 'loan.paymentSchedules')->where('user_id', $user->id)->findOrFail($id);

        $data['total_paid_installments'] = $data['financing']->loan->paymentSchedules->where('status', LoanPaymentScheduleStatus::PAID->value)->count();

        // Selisih harga cicilan dan tsaman naqdy
        $marginDiff = $data['financing']->loan->total_loan - $data['financing']->tsaman_naqdy;
        // Selisih harga cicilan dan tsaman naqdy per bulan
        $data['margin_diff_per_month'] = $marginDiff / $data['financing']->loan->tenor;

        $data['qimah_haliyyah'] = $data['financing']->tsaman_naqdy + ($data['margin_diff_per_month'] * ($data['total_paid_installments'] + 1)); // tambah satu untuk installment saat ini
        $data['payment_total'] = $data['financing']->loan->paymentSchedules->where('status', LoanPaymentScheduleStatus::PAID->value)->sum('total_amount');
        $data['repayment_total'] = $data['qimah_haliyyah'] - $data['payment_total'];

        Log::info('Repayment data for user '.$user->id.': ', $data);
        return inertia('User/Financing/Repayment/Show', [
            'data' => $data,
        ]);
    }

    public function sendRequest(CreateRepaymentRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        $loanPaymentSchedule = LoanPaymentSchedule::where('loan_id', $data['loan_id'])
            ->where('installment_number', $data['installment_number'])
            ->firstOrFail();

        $loanPaymentSchedule->update([
            'total_amount' => $data['repayment_total'],
            'principal_amount' => $data['principal_paid'],
            'margin_amount' => $data['margin_paid'],
            'status' => LoanPaymentScheduleStatus::PENDING->value
        ]);

        LoanPayment::create([
            'transaction_code' => 'LP' . now(), // temporary
            'amount' => $data['repayment_total'],
            'principal_paid' => $data['principal_paid'],
            'margin_paid' => $data['margin_paid'],
            'status' => LoanPaymentStatus::PENDING->value,
            'method' => $data['method'],
            'is_early_repayment' => true,
            'loan_payment_schedule_id' => $loanPaymentSchedule->id,
            'user_id' => $user->id,
        ]);

        return redirect()->route('user.userDashboard');
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
