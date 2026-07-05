<?php

namespace App\Services\Admin;

use App\Models\Akun;
use App\Models\DetailJurnal;
use App\Enums\PositionEnum;
use App\Enums\AkunCategoryEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AruskasService
{
    public function buildBaseQuery(array $filters)
    {
        $query = DetailJurnal::query()
            ->join('jurnal', 'detail_jurnal.jurnal_id', '=', 'jurnal.id')
            ->join('akun', 'detail_jurnal.no_ref_akun', '=', 'akun.no_ref_akun')
            ->select([
                'detail_jurnal.*',
                'jurnal.tgl_transaksi',
                'akun.nama_akun',
                'akun.kategori_akun',
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('akun.nama_akun', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('detail_jurnal.jurnal_id', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('detail_jurnal.no_ref_akun', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['periode'])) {
            switch ($filters['periode']) {
                case '1_minggu':
                    $query->whereDate('jurnal.tgl_transaksi', '>=', now()->subWeek());
                    break;
                case '1_bulan':
                    $query->whereDate('jurnal.tgl_transaksi', '>=', now()->subMonth());
                    break;
                case '3_bulan':
                    $query->whereDate('jurnal.tgl_transaksi', '>=', now()->subMonths(3));
                    break;
                case '1_tahun':
                    $query->whereDate('jurnal.tgl_transaksi', '>=', now()->subYear());
                    break;
                case 'custom':
                    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                        $query->whereBetween('jurnal.tgl_transaksi', [
                            $filters['date_from'],
                            $filters['date_to'],
                        ]);
                    }
                    break;
            }
        }

        return $query;
    }

    public function getTransactions(array $filters): array
    {
        $sortMap = [
            'tanggal'   => 'jurnal.tgl_transaksi',
            'no_jurnal' => 'detail_jurnal.jurnal_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal'] ?? 'jurnal.tgl_transaksi';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query = $this->buildBaseQuery($filters);

        $detailJurnal = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('detail_jurnal.created_at', $sortDir)
            ->orderBy('detail_jurnal.jurnal_id', $sortDir)
            ->orderBy('detail_jurnal.id', 'asc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $allGroups = $this->buildBaseQuery($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('detail_jurnal.created_at', $sortDir)
            ->orderBy('detail_jurnal.jurnal_id', $sortDir)
            ->distinct()
            ->pluck('detail_jurnal.jurnal_id')
            ->values();

        $firstGroupOnPage = collect($detailJurnal->items())->first()?->jurnal_id;
        $groupStartIndex  = $firstGroupOnPage ? ($allGroups->search($firstGroupOnPage) + 1) : 1;

        $groupCounter  = $groupStartIndex;
        $lastJournal   = null;
        $currentNumber = null;

        $transactions = $detailJurnal->through(
            function ($item) use (&$groupCounter, &$lastJournal, &$currentNumber) {
                if ($lastJournal !== $item->jurnal_id) {
                    $currentNumber = $groupCounter++;
                    $lastJournal   = $item->jurnal_id;
                }

                return [
                    'id'         => $item->id,
                    'no'         => $currentNumber,
                    'no_jurnal'  => $item->jurnal_id,
                    'tanggal'    => Carbon::parse($item->tgl_transaksi)->format('d/m/Y'),
                    'akun'       => $item->no_ref_akun . ' - ' . $item->nama_akun,
                    'jenis_akun' => $item->kategori_akun,
                    'debit'      => $item->posisi_akun === PositionEnum::DEBIT->value  ? $item->nominal : null,
                    'kredit'     => $item->posisi_akun === PositionEnum::CREDIT->value ? $item->nominal : null,
                ];
            }
        );

        return [$detailJurnal, $transactions];
    }

    public function getKasSummary(): array
    {
        $kasAkun = Akun::where('nama_akun', 'Kas')->firstOrFail();

        $totalKasMasuk = DetailJurnal::where('no_ref_akun', $kasAkun->no_ref_akun)
            ->where('posisi_akun', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $totalKasKeluar = DetailJurnal::where('no_ref_akun', $kasAkun->no_ref_akun)
            ->where('posisi_akun', PositionEnum::CREDIT->value)
            ->sum('nominal');

        $saldoKas = $totalKasMasuk - $totalKasKeluar;

        return [
            [
                'title'      => 'Total Kas Tersedia',
                'value'      => 'Rp' . number_format($saldoKas, 0, ',', '.'),
                'percentage' => 0,
            ],
            [
                'title'      => 'Total Kas Keluar',
                'value'      => 'Rp' . number_format($totalKasKeluar, 0, ',', '.'),
                'percentage' => 0,
            ],
            [
                'title'      => 'Total Kas Masuk',
                'value'      => 'Rp' . number_format($totalKasMasuk, 0, ',', '.'),
                'percentage' => 0,
            ],
        ];
    }

    public function buildCsvRows(array $filters): \Illuminate\Support\Collection
    {
        $sortMap = [
            'tanggal'   => 'jurnal.tgl_transaksi',
            'no_jurnal' => 'detail_jurnal.jurnal_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal']
            ?? 'jurnal.tgl_transaksi';

        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $this->buildBaseQuery($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('detail_jurnal.created_at', $sortDir)
            ->orderBy('detail_jurnal.jurnal_id', $sortDir)
            ->orderBy('detail_jurnal.id')
            ->get()
            ->map(fn ($trx) => [
                Carbon::parse($trx->tgl_transaksi)->format('d/m/Y'),
                $trx->no_ref_akun . ' - ' . $trx->nama_akun,
                $trx->kategori_akun,
                $trx->posisi_akun === PositionEnum::DEBIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
                $trx->posisi_akun === PositionEnum::CREDIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
            ]);
    }
}