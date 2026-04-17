<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SavingTypeEnum;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SavingTransaction;
use App\Http\Controllers\Controller;

class SavingController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private function baseQuery(Request $request)
    {
        $search = $request->input('search');
        $tab    = $request->input('tab', 'semua');

        $typeMap = [
            'pokok'              => SavingTypeEnum::SIMPANAN_POKOK->value,
            'wajib'              => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'tabungan_anggota'   => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'tabungan_berjangka' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'tabungan_ibadah'    => SavingTypeEnum::TABUNGAN_IBADAH->value,
        ];

        return SavingTransaction::with(['savingAccount.user'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('savingAccount', function ($sa) use ($typeMap, $tab) {
                    $sa->where('type', $typeMap[$tab]);
                });
            })
            // Filter grup: 'simpanan' → 2 tipe simpanan
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('type', [
                        SavingTypeEnum::SIMPANAN_POKOK->value,
                        SavingTypeEnum::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('type', [
                        SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                        SavingTypeEnum::TABUNGAN_BERJANGKA->value,
                        SavingTypeEnum::TABUNGAN_IBADAH->value,
                    ]);
                });
            });
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search');
        $tab     = $request->input('tab', 'semua');
        $sortBy  = $request->input('sort_by', 'transaction_date');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['transaction_date'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'transaction_date';
        }

        $query = $this->baseQuery($request)->orderBy($sortBy, $sortDir);

        $transactions = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($trx) {
                return [
                    'id'           => $trx->id,
                    'no_transaksi' => str_pad($trx->saving_transaction_code, 6, '0', STR_PAD_LEFT),
                    'tanggal'      => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'anggota'      => $trx->savingAccount->user->member_code
                                    . ' - '
                                    . $trx->savingAccount->user->name,
                    'nominal'      => $trx->transaction_type === 'Penarikan'
                                    ? -$trx->saving_amount
                                    : $trx->saving_amount,
                    'produk'       => $trx->savingAccount->saving_type, // nama lengkap
                    'jenis'        => $trx->saving_type,
                ];
            });

        $summaryBase     = $this->baseQuery($request);
        $totalMasuk      = (clone $summaryBase)->where('transaction_type', 'Penyetoran')->sum('saving_amount');
        $totalKeluar     = (clone $summaryBase)->where('transaction_type', 'Penarikan')->sum('saving_amount');
        $totalPerputaran = $totalMasuk + $totalKeluar;

        $summary = [
            [
                'title'      => 'Total Kas',
                'value'      => 'Rp ' . number_format($totalMasuk - $totalKeluar, 0, ',', '.'),
                'percentage' => $totalMasuk > 0
                    ? round((($totalMasuk - $totalKeluar) / $totalMasuk) * 100)
                    : 0,
            ],
            [
                'title'      => 'Total Simpanan Masuk',
                'value'      => 'Rp ' . number_format($totalMasuk, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalMasuk / $totalPerputaran) * 100)
                    : 0,
            ],
            [
                'title'      => 'Total Simpanan Keluar',
                'value'      => 'Rp ' . number_format($totalKeluar, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalKeluar / $totalPerputaran) * 100)
                    : 0,
            ],
        ];

        return Inertia::render('Admin/Savings/List', [
            'transactions' => $transactions,
            'summary'      => $summary,
            'filters'      => [
                'search'   => $search,
                'per_page' => $perPage,
                'tab'      => $tab,
                'sort_by'  => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }


    private function exportTitle(string $tab): string
    {
        return match ($tab) {
            'simpanan'           => 'Data Semua Simpanan',
            'pokok'              => 'Data Simpanan Pokok',
            'wajib'              => 'Data Simpanan Wajib',
            'tabungan'           => 'Data Semua Tabungan',
            'tabungan_anggota'   => 'Data Tabungan Anggota',
            'tabungan_berjangka' => 'Data Tabungan Berjangka',
            'tabungan_ibadah'    => 'Data Tabungan Ibadah',
            default              => 'Data Simpanan & Tabungan',
        };
    }

    public function exportCsv(Request $request)
    {
        $tab      = $request->input('tab', 'semua');
        $title    = $this->exportTitle($tab);
        $filename = Str::slug($title) . '_' . now()->format('Ymd_His') . '.csv';

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($transactions, $title) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [$title]);
            fputcsv($handle, []);
            fputcsv($handle, ['No Transaksi', 'Tanggal', 'Anggota', 'Produk', 'Jenis', 'Nominal']);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    str_pad($trx->id, 6, '0', STR_PAD_LEFT),
                    $trx->transaction_date->format('d/m/Y'),
                    $trx->savingAccount->user->member_code . ' - ' . $trx->savingAccount->user->name,
                    $trx->savingAccount->saving_type,
                    $trx->transaction_type,
                    $trx->transaction_type === 'Penarikan' ? -$trx->saving_amount : $trx->saving_amount,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $tab   = $request->input('tab', 'semua');
        $title = $this->exportTitle($tab);

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.saving', [
            'transactions' => $transactions,
            'title'        => $title,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            Str::slug($title) . '_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = SavingTransaction::with('savingAccount.user', 'account')->find($id);

        return inertia('Admin/Savings/Show', [
            'data' => $data,
        ]);
    }
}
