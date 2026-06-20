<?php

namespace App\Services\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Financing;
use App\Models\MemberDoc;
use App\Models\SavingTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengunduranDiriService
{
    public function getResignData(int $memberId): array
    {
        $totalSaving = SavingTransaction::whereHas(
            'savingAccount',
            fn($q) => $q->where('member_id', $memberId)
        )
        ->sum(DB::raw("
            CASE
                WHEN transaction_type = 'Penyetoran' THEN saving_amount
                WHEN transaction_type = 'Penarikan' THEN -saving_amount
            END
        "));

        $totalObligation = $this->getTotalObligation($memberId);

        return [
            'total_saving'      => $totalSaving,
            'total_obligation'  => $totalObligation,
        ];
    }

    public function getTotalObligation(int $memberId): float
    {
        $costPriceSum = Financing::where('member_id', $memberId)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->sum('cost_price');

        $marginAmountSum = Financing::where('member_id', $memberId)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->sum('margin_amount');

        return $costPriceSum + $marginAmountSum;
    }

    /**
     * @throws \Exception
     */
    public function submitResign(\Illuminate\Http\UploadedFile $document, int $memberId, $member): void
    {
        $path = $document->store('resign_docs', 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            throw new \Exception('storage_failed');
        }

        DB::beginTransaction();
        try {
            MemberDoc::create([
                'doc_name'       => 'Dokumen Pengunduran Diri',
                'doc_attachment' => $path,
                'member_id'      => $memberId,
            ]);

            $member->status = MemberStatusEnum::RESIGNED_REQUESTED->value;
            $member->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
