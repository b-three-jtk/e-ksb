<?php

namespace App\Services;

use App\Models\Pembiayaan;
use Carbon\Carbon;
use App\Enums\FinancingReqStatusEnum;

class PembiayaanService
{

    public function computePembiayaanSummary(Pembiayaan $pembiayaan): void
    {
        $pembiayaan->total_price = ($pembiayaan->harga_perolehan ?? 0)
            + ($pembiayaan->margin_keuntungan ?? 0)
            - ($pembiayaan->uang_muka ?? 0);

        $angsuran = $pembiayaan->angsuran;
        $hasInstallments = $angsuran && $angsuran->count() > 0;

        $pembiayaan->installment_per_month = $pembiayaan->tenor > 0
            ? $pembiayaan->total_price / $pembiayaan->tenor
            : 0;

        $dokumenPendukung = [
            'akad_document'    => getDocumentUrl($pembiayaan->dokumen_akad),
            'struk_pembelian' => getDocumentUrl($pembiayaan->struk_pembelian),
        ];

        if ($pembiayaan->wakalah) {
            $dokumenPendukung['akad_wakalah_document'] = getDocumentUrl($pembiayaan->wakalah->dokumen_akad);
        }

        $pembiayaan->setAttribute('documents', $dokumenPendukung);

        if ($hasInstallments) {
            $pembiayaan->total_paid = $angsuran
                ->sum(fn($i) => $i->payment?->jumlah_angsuran_dibayar ?? 0);
        } else {
            $pembiayaan->total_paid = 0;
        }

        $hasEarlyRepayment = $angsuran
            ? $angsuran->contains(fn($i) => $i->payment?->is_pelunasan_lebih_cepat)
            : false;

        $pembiayaan->remaining_balance = $hasEarlyRepayment ? 0 : max(0, $pembiayaan->total_price - $pembiayaan->total_paid);
    }

    public function computeNextDueDate(Pembiayaan $pembiayaan): void
    {
        $angsuran = $pembiayaan->angsuran;

        if (!$angsuran || !$pembiayaan->tgl_akad) {
            $pembiayaan->next_due_date = null;
            return;
        }

        $paidCount = $angsuran->count();

        $pembiayaan->next_due_date = $paidCount < $pembiayaan->tenor
            ? Carbon::parse($pembiayaan->tgl_akad)
                ->addMonthsNoOverflow($paidCount + 1)
                ->format('Y-m-d')
            : null;
    }

    public function getPembiayaanById($id)
    {
        return Pembiayaan::with([
            'anggota.user',
            'anggota.ahliWaris',
            'anggota.keuanganAnggota',
            'anggota.dokumenAnggota',
            'anggota.pekerjaanAnggota',
            'objekPembiayaan.jenisBarang',
            'jaminan',
            'angsuran' => function ($q) {
                $q->orderBy('angsuran_ke');
            },
            'wakalah',
        ])->findOrFail($id);
    }

    public function getActiveFinancing($anggotaId)
    {
        return Pembiayaan::with([
                'objekPembiayaan.jenisBarang',
                'angsuran' => function ($q) {
                    $q->orderBy('angsuran_ke');
                },
            ])
            ->where('anggota_id', $anggotaId)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->latest('tgl_akad')
            ->get()
            ->each(function ($pembiayaan) {
                $this->computePembiayaanSummary($pembiayaan);
                $this->computeNextDueDate($pembiayaan);
            });
    }
}
