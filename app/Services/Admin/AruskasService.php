<?php

namespace App\Services\Admin;

use App\Models\Akun;
use App\Models\DetailJurnal;
use App\Enums\PositionEnum;
use App\Enums\AkunCategoryEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AruskasService
{

    private const CASH_ACCOUNT = '101';

    private const CASH_FLOW_MAPPING = [
        '201' => [
            'activity' => 'financing',
            'in' => 'Penerimaan Tabungan Anggota',
            'out' => 'Penarikan Tabungan Anggota',
        ],

        '202' => [
            'activity' => 'financing',
            'in' => 'Penerimaan Tabungan Berjangka',
            'out' => 'Pencairan Tabungan Berjangka',
        ],

        '203' => [
            'activity' => 'financing',
            'in' => 'Penerimaan Tabungan Ibadah',
            'out' => 'Pencairan Tabungan Ibadah',
        ],

        '301' => [
            'activity' => 'financing',
            'in' => 'Penerimaan Simpanan Pokok',
            'out' => 'Penarikan Simpanan Pokok',
        ],

        '302' => [
            'activity' => 'financing',
            'in' => 'Penerimaan Simpanan Wajib',
            'out' => 'Penarikan Simpanan Wajib',
        ],

        '102' => [
            'activity' => 'operating',
            'in' => null,
            'out' => 'Penyaluran Dana Pembiayaan Murabahah',
        ],

        '104' => [
            'activity' => 'operating',
            'in' => 'Penerimaan Angsuran Murabahah',
            'out' => null,
        ],

        '204' => [
            'activity' => 'operating',
            'in' => 'Penerimaan Uang Muka Murabahah',
            'out' => null,
        ],
    ];

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
    
    public function getCashFlowReport(array $filters): array
    {
        $journals = $this->buildBaseQuery($filters)
            ->get()
            ->groupBy('jurnal_id');

        $operating = [];
        $investing = [];
        $financing = [];

        foreach ($journals as $entries) {

            $result = $this->classifyJournal($entries);

            if (!$result) {
                continue;
            }

            switch ($result['activity']) {

                case 'operating':
                    if (!isset($operating[$result['description']])) {
                        $operating[$result['description']] = 0;
                    }

                    $operating[$result['description']] +=
                        $result['cash_in']
                            ? $result['amount']
                            : -$result['amount'];
                break;

                case 'investing':
                    if (!isset($investing[$result['description']])) {
                        $investing[$result['description']] = 0;
                    }

                    $investing[$result['description']] +=
                        $result['cash_in']
                            ? $result['amount']
                            : -$result['amount'];
                break;

                case 'financing':
                    if (!isset($financing[$result['description']])) {
                        $financing[$result['description']] = 0;
                    }

                    $financing[$result['description']] +=
                        $result['amount']
                        * ($result['cash_in'] ? 1 : -1);
                break;
            }
        }

        $operatingItems = collect($operating)
            ->map(fn($amount, $desc) => [
                'description' => $desc,
                'amount' => $amount,
            ])
            ->values();

        $investingItems = collect($investing)
            ->map(fn($amount, $desc) => [
                'description' => $desc,
                'amount' => $amount,
            ])
            ->values();

        $financingItems = collect($financing)
            ->map(fn($amount, $desc) => [
                'description' => $desc,
                'amount' => $amount,
            ])
            ->values();

        $openingBalance = $this->calculateOpeningBalance($filters);

        $operatingNet = $operatingItems->sum('amount');
        $investingNet = $investingItems->sum('amount');
        $financingNet = $financingItems->sum('amount');

        $netCash = $operatingNet + $investingNet + $financingNet;

        return [

            'opening_balance' => $openingBalance,

            'operating' => [
                'items' => $operatingItems,
                'net' => $operatingNet,
            ],

            'investing' => [
                'items' => $investingItems,
                'net' => $investingNet,
            ],

            'financing' => [
                'items' => $financingItems,
                'net' => $financingNet,
            ],

            'net_cash' => $netCash,

            'closing_balance' => $openingBalance + $netCash,
        ];
    }

    private function classifyJournal($entries): ?array
    {
        $cashEntry = $entries->firstWhere('no_ref_akun', self::CASH_ACCOUNT);

        if (!$cashEntry) {
            return null;
        }

        $accountCodes = $entries->pluck('no_ref_akun')->toArray();

        $cashIn = $cashEntry->posisi_akun === PositionEnum::DEBIT->value;

        if (in_array('104', $accountCodes) && in_array('401', $accountCodes)) {
            return [
                'activity' => 'operating',
                'description' => 'Penerimaan Angsuran Murabahah',
                'cash_in' => true,
                'amount' => $cashEntry->nominal,
            ];
        }

        if (in_array('204', $accountCodes)) {
            return [
                'activity' => 'operating',
                'description' => 'Penerimaan Uang Muka Murabahah',
                'cash_in' => true,
                'amount' => $cashEntry->nominal,
            ];
        }

        if (in_array('102', $accountCodes)) {
            return [
                'activity' => 'operating',
                'description' => 'Penyaluran Dana Pembiayaan Murabahah',
                'cash_in' => false,
                'amount' => $cashEntry->nominal,
            ];
        }

        foreach (self::CASH_FLOW_MAPPING as $account => $mapping) {
            if (!in_array($account, $accountCodes)) {
                continue;
            }

            return [
                'activity' => $mapping['activity'],
                'description' => $cashIn ? $mapping['in'] : $mapping['out'],
                'cash_in' => $cashIn,
                'amount' => $cashEntry->nominal,
            ];
        }

        return null;
    }

    private function calculateOpeningBalance(array $filters): float
    {
        if (($filters['periode'] ?? null) !== 'custom' || empty($filters['date_from'])) {
            return 0;
        }

        $query = DetailJurnal::query()
            ->join('jurnal', 'detail_jurnal.jurnal_id', '=', 'jurnal.id')
            ->where('detail_jurnal.no_ref_akun', self::CASH_ACCOUNT)
            ->whereDate('jurnal.tgl_transaksi', '<', $filters['date_from']);

        $debit = (clone $query)
            ->where('detail_jurnal.posisi_akun', PositionEnum::DEBIT->value)
            ->sum('detail_jurnal.nominal');

        $credit = (clone $query)
            ->where('detail_jurnal.posisi_akun', PositionEnum::CREDIT->value)
            ->sum('detail_jurnal.nominal');

        return $debit - $credit;
    }
}