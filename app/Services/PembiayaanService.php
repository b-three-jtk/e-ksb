<?php

namespace App\Services;

use App\Models\Financing;
use Carbon\Carbon;

class PembiayaanService
{
    public function getPersonalFinancings(string $memberId, int $perPage = 10, string $search = '')
    {
        return Financing::query()
            ->with(['financingItem.productType'])
            ->where('member_id', $memberId)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereRaw(
                    'LOWER(financing_transaction_code) LIKE ?',
                    ['%' . mb_strtolower($search) . '%']
                );
            })
            ->orderByDesc('akad_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Financing $financing) => $this->mapFinancingForList($financing));
    }

    public function getActiveFinancing(string $memberId): ?array
    {
        $activeFinancingModel = Financing::query()
            ->with(['financingItem.productType'])
            ->where('member_id', $memberId)
            ->where('status', 'Angsuran Berjalan')
            ->orderByDesc('akad_date')
            ->orderByDesc('created_at')
            ->first();

        return $activeFinancingModel ? $this->mapFinancingForList($activeFinancingModel) : null;
    }

    public function mapFinancingForList(Financing $financing): array
    {
        $productName = null;

        if ($financing->financingItem) {
            $productName = $financing->financingItem->name;
        }

        return [
            'id' => $financing->id,
            'transaction_code' => $financing->financing_transaction_code,
            'akad_date' => $financing->akad_date,
            'product_name' => $productName,
            'status' => $financing->status,
            'remaining_balance' => 0,
            'loan' => null,
        ];
    }

    public function computeFinancingSummary(Financing $financing): void
    {
        $financing->total_price = ($financing->cost_price ?? 0)
            + ($financing->margin_amount ?? 0)
            - ($financing->down_payment ?? 0);

        $installments = $financing->installment;
        $hasInstallments = $installments && $installments->count() > 0;

        $financing->installment_per_month = $financing->tenor > 0
            ? $financing->total_price / $financing->tenor
            : 0;

        if ($hasInstallments) {
            $financing->total_paid = $installments
                ->sum(fn($i) => $i->payment?->nominal ?? 0);
        } else {
            $financing->total_paid = 0;
        }

        $hasEarlyRepayment = $installments
            ? $installments->contains(fn($i) => $i->payment?->is_early_repayment)
            : false;

        $financing->remaining_balance = $hasEarlyRepayment ? 0 : max(0, $financing->total_price - $financing->total_paid);
    }

    public function computeNextDueDate(Financing $financing): void
    {
        $installments = $financing->installment;

        if (!$installments || !$financing->akad_date) {
            $financing->next_due_date = null;
            return;
        }

        $paidCount = $installments->count();

        $financing->next_due_date = $paidCount < $financing->tenor
            ? Carbon::parse($financing->akad_date)
                ->addMonthsNoOverflow($paidCount + 1)
                ->format('Y-m-d')
            : null;
    }
}
