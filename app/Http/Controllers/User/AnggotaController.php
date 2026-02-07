<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDoc;
use App\Enums\LoanStatus;
use App\Enums\UserStatus;
use App\Models\Financing;
use Illuminate\Http\Request;
use App\Enums\TransactionStatus;
use App\Enums\FinancingReqStatus;
use App\Models\SavingTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Enums\LoanPaymentScheduleStatus;
use App\Http\Requests\CreateResignRequest;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalSaving = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('savingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalInstallment = $user->financings()
        ->whereIn('status', [
            FinancingReqStatus::APPROVED,
            FinancingReqStatus::APPROVED_WITH_NOTES,
        ])
        ->whereHas('loan')
        ->with(['loan.paymentSchedules' => fn ($q) =>
            $q->where('paymentSchedules.status', LoanPaymentScheduleStatus::PAID)
        ])
        ->get()
        ->sum(function ($financing) {
            $loan = $financing->loan;
            if (!$loan) return 0;

            $totalPaid = $loan->paymentSchedules->sum('total_amount');

            if ($totalPaid >= $loan->total_price) {
                return 0;
            }

            return $loan->amount_ins ?? 0;
        });

        $ledger = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas(
                'savingAccount',
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->with('savingAccount')
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'date'    => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'product' => $trx->savingAccount->type,
                    'type'    => $trx->type,
                    'amount'  => 'Rp ' . number_format($trx->amount, 0, ',', '.'),
                ];
            });

        $activeMurabahahCount = $user->financings()
            ->whereIn('status', [FinancingReqStatus::APPROVED, FinancingReqStatus::APPROVED_WITH_NOTES,])
            ->whereHas('loan')
            ->with(['loan.paymentSchedules' => fn ($q) =>
                $q->where('paymentSchedules.status', LoanPaymentScheduleStatus::PAID)
            ])
            ->get()
            ->filter(function ($financing) {
                $loan = $financing->loan;
                if (!$loan){
                    return false;
                }

                $totalPaid = $loan->paymentSchedules->sum('total_amount');

                return $totalPaid < $loan->total_price;
            })
            ->count();

        return inertia('User/Dashboard', [
            'summary' => [
                'total_saving' => $totalSaving,
                'total_installment' => $totalInstallment,
                'murabahah_count' => $activeMurabahahCount,
            ],
            'ledger' => $ledger,
        ]);
    }

    public function createResign()
    {
        $user = auth()->user();

        $hasExistingResign = $user->status === UserStatus::RESIGNED_REQUESTED->value;

        $totalSaving = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('savingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalObligation = Financing::with('loan')->where('user_id', $user->id)
            ->where('status', FinancingReqStatus::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $loan = $financing->loan;
                if (!$loan) return 0;

                $totalUnpaid = $loan->remaining_principal + $loan->remaining_margin;

                return $totalUnpaid;
            });

        return inertia('User/Resign/Create', [
            'member' => [
                ...$user->toArray(),
                'total_saving' => $totalSaving,
                'total_obligation' => $totalObligation,
            ],
            'has_existing_resign' => $hasExistingResign,
        ]);
    }

    public function storeResign(CreateResignRequest $request)
    {
        $user = auth()->user();

        $hasExistingResign = $user->status === UserStatus::RESIGNED_REQUESTED->value;

        if ($hasExistingResign) {
            return back()->withErrors([
                'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.',
            ]);
        }

        $data = $request->validated();

        $path = $data['document']->store('resign_docs', 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'document' => 'Gagal menyimpan dokumen. Silakan coba lagi.',
            ]);
        }

        DB::beginTransaction();
        try {
            UserDoc::create([
                'name' => 'Dokumen Pengunduran Diri',
                'attachment' => $path,
                'user_id' => $user->id,
            ]);
            $user->status = UserStatus::RESIGNED_REQUESTED->value;
            $user->save();

            DB::commit();

            return redirect()
            ->route('user.userDashboard')
            ->with('success', 'Permohonan pengunduran diri berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'resign' => 'Terjadi kesalahan saat mengajukan pengunduran diri. Silakan coba lagi.',
            ]);
        }
    }
}
