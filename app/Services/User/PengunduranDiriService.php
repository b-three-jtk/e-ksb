<?php

namespace App\Services\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Pembiayaan;
use App\Models\MemberDoc;
use App\Models\TransaksiSimpanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengunduranDiriService
{
    public function getResignData(int $anggotaId): array
    {
        $totalSaving = TransaksiSimpanan::whereHas(
            'akunSimpanan',
            fn($q) => $q->where('anggota_id', $anggotaId)
        )
        ->sum(DB::raw("
            CASE
                WHEN tipe_transaksi = 'Penyetoran' THEN nominal_simpanan
                WHEN tipe_transaksi = 'Penarikan' THEN -nominal_simpanan
            END
        "));

        $totalObligation = $this->getTotalObligation($anggotaId);

        return [
            'total_saving'      => $totalSaving,
            'total_obligation'  => $totalObligation,
        ];
    }

    public function getTotalObligation(int $anggotaId): float
    {
        $costPriceSum = Pembiayaan::where('anggota_id', $anggotaId)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->sum('harga_perolehan');

        $marginAmountSum = Pembiayaan::where('anggota_id', $anggotaId)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->sum('margin_keuntungan');

        return $costPriceSum + $marginAmountSum;
    }

    /**
     * @throws \Exception
     */
    public function submitResign(\Illuminate\Http\UploadedFile $document, int $anggotaId, $anggota): void
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
                'anggota_id'      => $anggotaId,
            ]);

            $anggota->status = MemberStatusEnum::RESIGNED_REQUESTED->value;
            $anggota->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
