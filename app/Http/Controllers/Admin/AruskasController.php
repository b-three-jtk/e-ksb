<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Enums\PositionEnum;
use App\Enums\UserRoleEnum;
use App\Services\Admin\AruskasService;
use App\Services\Admin\JurnalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AruskasController extends Controller
{
    public function __construct(
        protected AruskasService $aruskasService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'per_page', 'periode',
            'date_from', 'date_to', 'sort_by', 'sort_dir',
        ]);

        [, $transactions] = $this->aruskasService->getTransactions($filters);

        return Inertia::render('Admin/CashFlow/List', [
            'transactions' => $transactions,
            'summary'      => $this->aruskasService->getKasSummary(),
            'filters'      => $filters,
            'akunOptions'  => Account::select(
                                'no_ref_account as nomor_akun',
                                'account_name as nama_akun'
                              )
                              ->orderBy('no_ref_account')
                              ->get(),
            'can' => [
                'tambah_alokasi' => Auth::user()->hasRole(UserRoleEnum::BENDAHARA->value),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nominal'     => ['required', 'numeric', 'min:1'],
            'akun_debit'  => ['required', 'exists:accounts,no_ref_account'],
            'akun_kredit' => ['required', 'exists:accounts,no_ref_account'],
        ]);

        if ($validated['akun_debit'] === $validated['akun_kredit']) {
            return back()->withErrors([
                'akun_kredit' => 'Akun debit dan kredit tidak boleh sama',
            ]);
        }

        $debitAccount  = Account::where('no_ref_account', $validated['akun_debit'])->firstOrFail();
        $creditAccount = Account::where('no_ref_account', $validated['akun_kredit'])->firstOrFail();

        app(JurnalService::class)->create(
            [
                ['account' => $debitAccount->no_ref_account,  'position' => PositionEnum::DEBIT->value,  'nominal' => $validated['nominal']],
                ['account' => $creditAccount->no_ref_account, 'position' => PositionEnum::CREDIT->value, 'nominal' => $validated['nominal']],
            ],
            now()->toDateString(),
            auth()->id()
        );

        return back()->with('success', 'Alokasi kas berhasil diposting');
    }

    public function exportCsv(Request $request)
    {
        $filters  = $request->only(['search', 'periode', 'date_from', 'date_to']);
        $filename = 'arus_kas_' . now()->format('Ymd_His') . '.csv';
        $rows     = $this->aruskasService->buildCsvRows($filters);

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Koperasi Syariah Berkah']);
            fputcsv($handle, ['Laporan Arus Kas']);

            $periode = 'Semua Periode';
            if (
                !empty($filters['date_from']) &&
                !empty($filters['date_to'])
            ) {
                $periode =
                    Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y')
                    . ' s.d. '
                    . Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y');
            }

            fputcsv($handle, ["Periode : {$periode}"]);
            fputcsv($handle, []);
            fputcsv($handle, ['Tanggal', 'Akun', 'Jenis Akun', 'Debit', 'Kredit']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}