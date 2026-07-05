<?php

namespace App\Services\Admin;

use App\Models\Akun;
use App\Models\JournalEntry;
use App\Enums\PositionEnum;
use App\Enums\AkunCategoryEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AruskasService
{
    public function buildBaseQuery(array $filters)
    {
        $query = JournalEntry::query()
            ->join('akun', 'journal_entries.no_ref_akun', '=', 'akun.no_ref_akun')
            ->select([
                'journal_entries.*',
                'akun.nama_akun',
                'akun.kategori_akun',
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('akun.nama_akun', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('journal_entries.journal_group_id', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('journal_entries.no_ref_akun', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['periode'])) {
            switch ($filters['periode']) {
                case '1_minggu':
                    $query->whereDate('journal_entries.tgl_transaksi', '>=', now()->subWeek());
                    break;
                case '1_bulan':
                    $query->whereDate('journal_entries.tgl_transaksi', '>=', now()->subMonth());
                    break;
                case '3_bulan':
                    $query->whereDate('journal_entries.tgl_transaksi', '>=', now()->subMonths(3));
                    break;
                case '1_tahun':
                    $query->whereDate('journal_entries.tgl_transaksi', '>=', now()->subYear());
                    break;
                case 'custom':
                    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                        $query->whereBetween('journal_entries.tgl_transaksi', [
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
            'tanggal'   => 'journal_entries.tgl_transaksi',
            'no_jurnal' => 'journal_entries.journal_group_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal'] ?? 'journal_entries.tgl_transaksi';
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
                    'tanggal'    => Carbon::parse($item->tgl_transaksi)->format('d/m/Y'),
                    'akun'       => $item->no_ref_akun . ' - ' . $item->nama_akun,
                    'jenis_akun' => $item->kategori_akun,
                    'debit'      => $item->position === PositionEnum::DEBIT->value  ? $item->nominal : null,
                    'kredit'     => $item->position === PositionEnum::CREDIT->value ? $item->nominal : null,
                ];
            }
        );

        return [$journalEntries, $transactions];
    }

    public function getKasSummary(): array
    {
        $kasAkun = Akun::where('nama_akun', 'Kas')->firstOrFail();

        $totalKasMasuk = JournalEntry::where('no_ref_akun', $kasAkun->no_ref_akun)
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $totalKasKeluar = JournalEntry::where('no_ref_akun', $kasAkun->no_ref_akun)
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
            'tanggal'   => 'journal_entries.tgl_transaksi',
            'no_jurnal' => 'journal_entries.journal_group_id',
        ];

        $sortBy  = $sortMap[$filters['sort_by'] ?? 'tanggal']
            ?? 'journal_entries.tgl_transaksi';

        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $this->buildBaseQuery($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderBy('journal_entries.created_at', $sortDir)
            ->orderBy('journal_entries.journal_group_id', $sortDir)
            ->orderBy('journal_entries.id')
            ->get()
            ->map(fn ($trx) => [
                Carbon::parse($trx->tgl_transaksi)->format('d/m/Y'),
                $trx->no_ref_akun . ' - ' . $trx->nama_akun,
                $trx->kategori_akun,
                $trx->position === PositionEnum::DEBIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
                $trx->position === PositionEnum::CREDIT->value
                    ? number_format($trx->nominal, 0, ',', '.')
                    : '',
            ]);
    }
}