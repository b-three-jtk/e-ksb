<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Admin\JournalService;
use App\Enums\AccountCategoryEnum;
use App\Enums\PositionEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class CashflowController extends Controller
{

    private function baseQuery(Request $request)
    {
        $query = JournalEntry::query()
            ->join(
                'accounts',
                'journal_entries.no_ref_account',
                '=',
                'accounts.no_ref_account'
            )
            ->select([
                'journal_entries.*',
                'accounts.account_name',
                'accounts.account_category',
            ]);

        // Search
        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'accounts.account_name',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'journal_entries.journal_group_id',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'journal_entries.no_ref_account',
                    'like',
                    '%' . $request->search . '%'
                );
            });
        }

        // Filter periode
        if ($request->filled('periode')) {

            switch ($request->periode) {

                case '1_minggu':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subWeek()
                    );
                    break;

                case '1_bulan':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subMonth()
                    );
                    break;

                case '3_bulan':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subMonths(3)
                    );
                    break;

                case '1_tahun':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subYear()
                    );
                    break;

                case 'custom':

                    if (
                        $request->filled('date_from')
                        && $request->filled('date_to')
                    ) {

                        $query->whereBetween(
                            'journal_entries.transaction_date',
                            [
                                $request->date_from,
                                $request->date_to,
                            ]
                        );
                    }

                    break;
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->baseQuery($request);

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {

                $q->where(
                    'accounts.account_name', 'like', '%' . $request->search . '%')
                    ->orWhere('journal_entries.journal_group_id', 'like', '%' . $request->search . '%')
                    ->orWhere('journal_entries.no_ref_account', 'like', '%' . $request->search . '%');
            });
        }

        // Filter periode
        if ($request->filled('periode')) {

            switch ($request->periode) {

                case '1_minggu':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subWeek()
                    );
                    break;

                case '1_bulan':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subMonth()
                    );
                    break;

                case '3_bulan':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subMonths(3)
                    );
                    break;

                case '1_tahun':
                    $query->whereDate(
                        'journal_entries.transaction_date',
                        '>=',
                        now()->subYear()
                    );
                    break;

                case 'custom':
                    if (
                        $request->filled('date_from')
                        && $request->filled('date_to')
                    ) {
                        $query->whereBetween(
                            'journal_entries.transaction_date',
                            [
                                $request->date_from,
                                $request->date_to
                            ]
                        );
                    }
                    break;
            }
        }

        $sortMap = [
            'tanggal' => 'journal_entries.transaction_date',
            'no_jurnal' => 'journal_entries.journal_group_id',
        ];

        $sortBy = $sortMap[$request->get('sort_by', 'tanggal')]
            ?? 'journal_entries.transaction_date';

        $sortDir = $request->get('sort_dir', 'desc');

        $journalEntries = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate(
                $request->get('per_page', 10)
            )
            ->withQueryString();

        $counter = 1;
        $lastJournal = null;

        $transactions = $journalEntries->through(
            function ($item) use (&$counter, &$lastJournal) {

                $nomor = null;

                if ($lastJournal !== $item->journal_group_id) {
                    $nomor = $counter++;
                    $lastJournal = $item->journal_group_id;
                }

                return [
                    'id' => $item->id,

                    'no' => $nomor,

                    'no_jurnal' => $item->journal_group_id,

                    'tanggal' => Carbon::parse(
                        $item->transaction_date
                    )->format('d/m/Y'),

                    'akun' =>
                        $item->no_ref_account .
                        ' - ' .
                        $item->account_name,

                    'jenis_akun' =>
                        $item->account_category,

                    'debit' =>
                        $item->position === PositionEnum::DEBIT->value
                            ? $item->nominal
                            : null,

                    'kredit' =>
                        $item->position === PositionEnum::CREDIT->value
                            ? $item->nominal
                            : null,
                ];
            }
        );    

        $kasAccount = Account::where(
            'account_name',
            'Kas'
        )->firstOrFail();

        $kasMasukQuery = JournalEntry::query()
            ->where(
                'no_ref_account',
                $kasAccount->no_ref_account
            )
            ->where(
                'position',
                PositionEnum::DEBIT->value
            );

        $totalKasMasuk = $kasMasukQuery->sum(
            'nominal'
        );

        $kasKeluarQuery = JournalEntry::query()
            ->where(
                'no_ref_account',
                $kasAccount->no_ref_account
            )
            ->where(
                'position',
                PositionEnum::CREDIT->value
            );

        $totalKasKeluar = $kasKeluarQuery->sum(
            'nominal'
        );

        $saldoKas = $totalKasMasuk - $totalKasKeluar;

        $summary = [
            [
                'title' => 'Total Kas Tersedia',
                'value' => 'Rp' . number_format(
                    $saldoKas,
                    0,
                    ',',
                    '.'
                ),
                'percentage' => 0,
            ],
            [
                'title' => 'Total Kas Keluar',
                'value' => 'Rp' . number_format(
                    $totalKasKeluar,
                    0,
                    ',',
                    '.'
                ),
                'percentage' => 0,
            ],
            [
                'title' => 'Total Kas Masuk',
                'value' => 'Rp' . number_format(
                    $totalKasMasuk,
                    0,
                    ',',
                    '.'
                ),
                'percentage' => 0,
            ],
        ];

        return Inertia::render(
            'Admin/CashFlow/List',
            [
                'transactions' => $transactions,

                'summary' => $summary,

                'filters' => $request->only([
                    'search',
                    'per_page',
                    'periode',
                    'date_from',
                    'date_to',
                    'sort_by',
                    'sort_dir',
                ]),

                'akunOptions' => Account::select(
                    'no_ref_account as nomor_akun',
                    'account_name as nama_akun'
                )
                    ->orderBy('no_ref_account')
                    ->get(),
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nominal' => ['required', 'numeric', 'min:1'],
            'akun_debit' => ['required', 'exists:accounts,no_ref_account'],
            'akun_kredit' => ['required', 'exists:accounts,no_ref_account'],
        ]);

        if (
            $validated['akun_debit']
            == $validated['akun_kredit']
        ) {
            return back()->withErrors([
                'akun_kredit' =>
                    'Akun debit dan kredit tidak boleh sama'
            ]);
        }

        $debitAccount = Account::where(
            'no_ref_account',
            $validated['akun_debit']
        )->firstOrFail();

        $creditAccount = Account::where(
            'no_ref_account',
            $validated['akun_kredit']
        )->firstOrFail();

        app(JournalService::class)->create(
            [
                [
                    'account' => $debitAccount->no_ref_account,
                    'position' => PositionEnum::DEBIT->value,
                    'nominal' => $validated['nominal'],
                ],
                [
                    'account' => $creditAccount->no_ref_account,
                    'position' => PositionEnum::CREDIT->value,
                    'nominal' => $validated['nominal'],
                ],
            ],
            now()->toDateString(),
            auth()->id()
        );

        return back()->with(
            'success',
            'Alokasi kas berhasil diposting'
        );
    }

    public function exportCsv(Request $request)
    {
        $filename =
            'arus_kas_' .
            now()->format('Ymd_His') .
            '.csv';

        $transactions = $this->baseQuery($request)
            ->orderBy(
                'journal_entries.transaction_date',
                'desc'
            )
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' =>
                "attachment; filename={$filename}",
        ];

        $callback = function () use ($transactions) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Laporan Arus Kas'
            ]);

            fputcsv($handle, []);

            fputcsv($handle, [
                'No Jurnal',
                'Tanggal',
                'Akun',
                'Jenis Akun',
                'Debit',
                'Kredit',
            ]);

            foreach ($transactions as $trx) {

                fputcsv($handle, [

                    $trx->journal_group_id,

                    Carbon::parse(
                        $trx->transaction_date
                    )->format('d/m/Y'),

                    $trx->no_ref_account .
                    ' - ' .
                    $trx->account_name,

                    $trx->account_category,

                    $trx->position === PositionEnum::DEBIT->value
                        ? $trx->nominal
                        : '',

                    $trx->position === PositionEnum::CREDIT->value
                        ? $trx->nominal
                        : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }
}
