<?php

namespace App\Services;

use App\Models\Pembiayaan;
use Carbon\Carbon;

class PembiayaanService
{
    public function getPersonalpembiayaan(string $anggotaId, int $perPage = 10, string $search = '')
    {
        return Pembiayaan::query()
            ->with(['objekPembiayaan.jenisBarang'])
            ->where('anggota_id', $anggotaId)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan', 'Pembayaran Tangguh'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereRaw(
                    'LOWER(kode_pembiayaan) LIKE ?',
                    ['%' . mb_strtolower($search) . '%']
                );
            })
            ->orderByDesc('tgl_akad')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Pembiayaan $pembiayaan) => $this->mapFinancingForList($pembiayaan));
    }

    public function getActiveFinancing(string $anggotaId): ?array
    {
        $activeFinancingModel = Pembiayaan::query()
            ->with(['objekPembiayaan.jenisBarang'])
            ->where('anggota_id', $anggotaId)
            ->where('status', 'Angsuran Berjalan')
            ->orderByDesc('tgl_akad')
            ->orderByDesc('created_at')
            ->first();

        return $activeFinancingModel ? $this->mapFinancingForList($activeFinancingModel) : null;
    }

    public function mapFinancingForList(Pembiayaan $pembiayaan): array
    {
        $productName = null;

        if ($pembiayaan->objekPembiayaan) {
            $productName = $pembiayaan->objekPembiayaan->nama_barang;
        }

        return [
            'id' => $pembiayaan->id,
            'transaction_code' => $pembiayaan->kode_pembiayaan,
            'tgl_akad' => $pembiayaan->tgl_akad,
            'product_name' => $productName,
            'status' => $pembiayaan->status,
            'remaining_balance' => 0,
            'loan' => null,
        ];
    }

    public function computepembiayaanummary(Pembiayaan $pembiayaan): void
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
            'collateral',
            'angsuran' => function ($q) {
                $q->orderBy('angsuran_ke');
            },
            'wakalah',
        ])->findOrFail($id);
    }
}
