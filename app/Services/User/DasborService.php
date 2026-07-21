<?php

namespace App\Services\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Angsuran;
use App\Models\Poin;
use App\Models\TransaksiSimpanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DasborService
{
    public function getSummary(int $anggotaId, string $userId): array
    {
        $totalSaving = DB::table('akun_simpanan')
            ->where('anggota_id', $anggotaId)
            ->sum('saldo');
        $totalSaving = (int) round($totalSaving);

        $totalInstallment = Angsuran::whereHas('pembiayaan', function ($q) use ($anggotaId) {
            $q->where('anggota_id', $anggotaId)
                ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value);
        })
        ->whereIn('status', [
            InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            InstallmentPaymentScheduleStatusEnum::PENDING->value,
            InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ])
        ->sum('nominal_angsuran');
        $totalInstallment = (int) round($totalInstallment);

        $totalPoints = Poin::where('pengguna_id', $userId)
            ->sum('jml_perolehan');

        return [
            'total_saving'      => $totalSaving,
            'total_installment' => $totalInstallment,
            'total_points'      => $totalPoints,
        ];
    }

    public function getTabungan(int $anggotaId): \Illuminate\Support\Collection
    {
        return TransaksiSimpanan::whereHas(
            'akunSimpanan.anggota',
            fn($q) => $q->where('anggota_id', $anggotaId)
        )
        ->with('akunSimpanan')
        ->latest('tgl_transaksi')
        ->limit(5)
        ->get()
        ->map(fn($trx) => [
            'date'    => Carbon::parse($trx->tgl_transaksi)->format('d/m/Y'),
            'product' => $trx->akunSimpanan->jenis_simpanan,
            'type'    => $trx->tipe_transaksi,
            'amount'  => 'Rp ' . number_format($trx->nominal_simpanan, 0, ',', '.'),
        ]);
    }
}