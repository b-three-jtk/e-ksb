<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\SavingAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\TransactionStatus;
use App\Models\SavingTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingTransactionValidationRequest;

class SavingController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private function baseQuery(Request $request)
    {
        $search = $request->input('search');
        $tab = $request->input('tab', 'semua');

        return SavingTransaction::with(['savingAccount.user.workUnit'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
                });
            })
            ->when($tab === 'permohonan', function ($q) {
                $q->where('status', TransactionStatus::PENDING);
            })
            ->when($tab !== 'permohonan', function ($q) {
                $q->where('status', TransactionStatus::COMPLETED);
            })
            ->when(in_array($tab, ['pokok', 'wajib', 'sukarela']), function ($q) use ($tab) {
                $map = [
                    'pokok' => 'Simpanan Pokok',
                    'wajib' => 'Simpanan Wajib',
                    'sukarela' => 'Simpanan Sukarela',
                ];

                $q->whereHas('savingAccount', function ($sa) use ($map, $tab) {
                    $sa->where('type', $map[$tab]);
                });
            });
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $tab = $request->input('tab', 'semua');

        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['transaction_date'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'transaction_date';
        }

        $query = $this->baseQuery($request)
        ->orderBy($sortBy, $sortDir);

        $transactions = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($trx) {
                return [
                    'id' => $trx->id,
                    'no_transaksi' => 'TRX-' . str_pad($trx->id, 6, '0', STR_PAD_LEFT),
                    'tanggal' => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'anggota' => $trx->savingAccount->user->member_number
                        . ' - '
                        . $trx->savingAccount->user->name,
                    'nominal' => $trx->type === 'Penarikan'
                        ? -$trx->amount
                        : $trx->amount,
                    'produk' => str_replace('Simpanan ', '', $trx->savingAccount->type),
                    'jenis' => $trx->type,
                ];
            });

        $summaryQuery = SavingTransaction::query()
            ->where('status', TransactionStatus::COMPLETED)
            ->when(in_array($tab, ['pokok', 'wajib', 'sukarela']), function ($q) use ($tab){
                $map = [
                    'pokok' => 'Simpanan Pokok',
                    'wajib' => 'Simpanan Wajib',
                    'sukarela' => 'Simpanan Sukarela',
                ];

                $q->whereHas('savingAccount', function ($sa) use ($map, $tab) {
                    $sa->where('type', $map[$tab]);
                });
            });

            $totalMasuk = (clone $summaryQuery)
                ->where('type', 'Penyetoran')
                ->sum('amount');

            $totalKeluar = (clone $summaryQuery)
                ->where('type', 'Penarikan')
                ->sum('amount');

            $totalPerputaran = $totalMasuk + $totalKeluar;

            $summary = [
            [
                'title' => 'Total Kas',
                'value' => 'Rp ' . number_format($totalMasuk - $totalKeluar, 0, ',', '.'),
                'percentage' => $totalMasuk > 0
                    ? round((($totalMasuk - $totalKeluar) / $totalMasuk) * 100)
                    : 0,
            ],
            [
                'title' => 'Total Simpanan Keluar',
                'value' => 'Rp ' . number_format($totalKeluar, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalKeluar / $totalPerputaran) * 100)
                    : 0,
            ],
            [
                'title' => 'Total Simpanan Masuk',
                'value' => 'Rp ' . number_format($totalMasuk, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalMasuk / $totalPerputaran) * 100)
                    : 0,
            ],
        ];

        return Inertia::render('Admin/Savings/List', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'tab' => $tab,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    private function exportTitle(string $tab): string
    {
        return match ($tab) {
            'pokok' => 'Data Simpanan Pokok',
            'wajib' => 'Data Simpanan Wajib',
            'sukarela' => 'Data Simpanan Sukarela',
            'permohonan' => 'Data Permohonan Penarikan / Penyetoran',
            default => 'Data Simpanan',
        };
    }

    public function exportCsv(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $title = $this->exportTitle($tab);

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $filename = Str::slug($title) . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($transactions, $title) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [$title]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No Transaksi',
                'Tanggal',
                'Anggota',
                'Produk',
                'Jenis',
                'Nominal',
            ]);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    'TRX-' . str_pad($trx->id, 6, '0', STR_PAD_LEFT),
                    $trx->transaction_date->format('d/m/Y'),
                    $trx->savingAccount->user->member_number . ' - ' . $trx->savingAccount->user->name,
                    $trx->savingAccount->type,
                    $trx->type,
                    $trx->type === 'Penarikan'
                        ? -$trx->amount
                        : $trx->amount,
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $title = $this->exportTitle($tab);

        $transactions = $this->baseQuery($request)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.saving', [
            'transactions' => $transactions,
            'title' => $title,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(
            Str::slug($title) . '_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = SavingTransaction::with( 'savingAccount.user.workUnit', 'account', 'savingTransactionDoc')->find($id);
        // dd($data->savingTransactionDoc);

        $data->savingTransactionDoc->first()->attachment = $data->savingTransactionDoc->first()->attachment
            ? asset('storage/' . $data->savingTransactionDoc->first()->attachment)
            : null;


        return inertia('Admin/Savings/Show', [
            'data' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function validateRequest(StoreSavingTransactionValidationRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $transaction = SavingTransaction::findOrFail($id);

            if ($data['status'] === 'accepted') {
                $transaction->status = TransactionStatus::COMPLETED;
            } elseif ($data['status'] === 'rejected') {
                $transaction->status = TransactionStatus::REJECTED;
                $transaction->description = $data['description'] ?? null;
            }

            $transaction->save();
            $account = SavingAccount::find($transaction->saving_account_id);
            if ($account) {
                $account->update(
                    ['balance' => $account->balance + ($transaction->type === 'Penarikan' ? -$transaction->amount : $transaction->amount)]
                );
            }

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back();
        }
    }
}
