<?php

namespace App\Services\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Enums\PositionEnum;
use App\Enums\AccountCategoryEnum;
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
        $query = JournalEntry::query()
            ->join('accounts', 'journal_entries.no_ref_account', '=', 'accounts.no_ref_account')
            ->select([
                'journal_entries.*',
                'accounts.account_name',
                'accounts.account_category',
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('accounts.account_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('journal_entries.journal_group_id', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('journal_entries.no_ref_account', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['periode'])) {
            switch ($filters['periode']) {
                case '1_minggu':
                    $query->whereDate('journal_entries.transaction_date', '>=', now()->subWeek());
                    break;
                case '1_bulan':
                    $query->whereDate('journal_entries.transaction_date', '>=', now()->subMonth());
                    break;
                case '3_bulan':
                    $query->whereDate('journal_entries.transaction_date', '>=', now()->subMonths(3));
                    break;
                case '1_tahun':
                    $query->whereDate('journal_entries.transaction_date', '>=', now()->subYear());
                    break;
                case 'custom':
                    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                        $query->whereBetween('journal_entries.transaction_date', [
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
            'tanggal'   => 'journal_entries.transaction_date',
            'no_jurnal' => 'journal_entries.journal_group_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal'] ?? 'journal_entries.transaction_date';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query = $this->buildBaseQuery($filters);

        $journalEntries = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('journal_entries.created_at', $sortDir)
            ->orderBy('journal_entries.journal_group_id', $sortDir)
            ->orderBy('journal_entries.id', 'asc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $allGroups = $this->buildBaseQuery($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('journal_entries.created_at', $sortDir)
            ->orderBy('journal_entries.journal_group_id', $sortDir)
            ->distinct()
            ->pluck('journal_entries.journal_group_id')
            ->values();

        $firstGroupOnPage = collect($journalEntries->items())->first()?->journal_group_id;
        $groupStartIndex  = $firstGroupOnPage ? ($allGroups->search($firstGroupOnPage) + 1) : 1;

        $groupCounter  = $groupStartIndex;
        $lastJournal   = null;
        $currentNumber = null;

        $transactions = $journalEntries->through(
            function ($item) use (&$groupCounter, &$lastJournal, &$currentNumber) {
                if ($lastJournal !== $item->journal_group_id) {
                    $currentNumber = $groupCounter++;
                    $lastJournal   = $item->journal_group_id;
                }

                return [
                    'id'         => $item->id,
                    'no'         => $currentNumber,
                    'no_jurnal'  => $item->journal_group_id,
                    'tanggal'    => Carbon::parse($item->transaction_date)->format('d/m/Y'),
                    'akun'       => $item->no_ref_account . ' - ' . $item->account_name,
                    'jenis_akun' => $item->account_category,
                    'debit'      => $item->position === PositionEnum::DEBIT->value  ? $item->nominal : null,
                    'kredit'     => $item->position === PositionEnum::CREDIT->value ? $item->nominal : null,
                ];
            }
        );

        return [$journalEntries, $transactions];
    }

    public function getKasSummary(): array
    {
        $kasAccount = Account::where('account_name', 'Kas')->firstOrFail();

        $totalKasMasuk = JournalEntry::where('no_ref_account', $kasAccount->no_ref_account)
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $totalKasKeluar = JournalEntry::where('no_ref_account', $kasAccount->no_ref_account)
            ->where('position', PositionEnum::CREDIT->value)
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
            'tanggal'   => 'journal_entries.transaction_date',
            'no_jurnal' => 'journal_entries.journal_group_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal']
            ?? 'journal_entries.transaction_date';

        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $this->buildBaseQuery($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('journal_entries.created_at', $sortDir)
            ->orderBy('journal_entries.journal_group_id', $sortDir)
            ->orderBy('journal_entries.id')
            ->get()
            ->map(fn ($trx) => [
                Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                $trx->no_ref_account . ' - ' . $trx->account_name,
                $trx->account_category,
                $trx->position === PositionEnum::DEBIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
                $trx->position === PositionEnum::CREDIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
            ]);
    }
    
    public function getCashFlowReport(array $filters): array
    {
        $journals = $this->buildBaseQuery($filters)
            ->get()
            ->groupBy('journal_group_id');

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
        $cashEntry = $entries->firstWhere(
            'no_ref_account',
            self::CASH_ACCOUNT
        );

        if (!$cashEntry) {
            return null;
        }

        $accountCodes = $entries
            ->pluck('no_ref_account')
            ->toArray();

        $cashIn = $cashEntry->position === PositionEnum::DEBIT->value;

        if (
            in_array('104', $accountCodes)
            && in_array('401', $accountCodes)
        ) {
            return [
                'activity' => 'operating',
                'description' => 'Penerimaan Angsuran Murabahah',
                'cash_in' => true,
                'amount' => $cashEntry->nominal,
            ];
        }

        /**
         * Uang Muka
         */
        if (in_array('204', $accountCodes)) {

            return [
                'activity' => 'operating',
                'description' => 'Penerimaan Uang Muka Murabahah',
                'cash_in' => true,
                'amount' => $cashEntry->nominal,
            ];
        }

        /**
         * Dana Alokasi
         */
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
                'description' => $cashIn
                    ? $mapping['in']
                    : $mapping['out'],
                'cash_in' => $cashIn,
                'amount' => $cashEntry->nominal,
            ];
        }

        return null;
    }

    private function calculateOpeningBalance(array $filters): float
    {
        $query = JournalEntry::query()
            ->where('no_ref_account', self::CASH_ACCOUNT);

        if (
            ($filters['periode'] ?? null) === 'custom'
            && !empty($filters['date_from'])
        ) {
            $query->whereDate(
                'transaction_date',
                '<',
                $filters['date_from']
            );
        } else {
            return 0;
        }

        $debit = (clone $query)
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $credit = (clone $query)
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        return $debit - $credit;
    }
}