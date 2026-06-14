<?php

namespace App\Services\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Installment;
use App\Models\PointTransaction;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DasborService
{
    public function getSummary(int $memberId, string $userId): array
    {
        $totalSaving = DB::table('saving_accounts')
            ->where('member_id', $memberId)
            ->sum('balance');

        $totalInstallment = Installment::whereHas('financing', function ($q) use ($memberId) {
            $q->where('member_id', $memberId)
                ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value);
        })
        ->whereIn('status', [
            InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            InstallmentPaymentScheduleStatusEnum::PENDING->value,
            InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ])
        ->sum('amount');

        $totalPoints = PointTransaction::where('user_id', $userId)
            ->sum('amount_earned');

        return [
            'total_saving'      => $totalSaving,
            'total_installment' => $totalInstallment,
            'total_points'      => $totalPoints,
        ];
    }

    public function getLedger(int $memberId): \Illuminate\Support\Collection
    {
        return SavingTransaction::whereHas(
            'savingAccount.member',
            fn($q) => $q->where('member_id', $memberId)
        )
        ->with('savingAccount')
        ->latest('transaction_date')
        ->limit(5)
        ->get()
        ->map(fn($trx) => [
            'date'    => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
            'product' => $trx->savingAccount->saving_type,
            'type'    => $trx->transaction_type,
            'amount'  => 'Rp ' . number_format($trx->saving_amount, 0, ',', '.'),
        ]);
    }
}