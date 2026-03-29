<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SavingType;
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
        $tab    = $request->input('tab', 'semua');
    
        $typeMap = [
            'pokok'              => SavingType::SIMPANAN_POKOK->value,
            'wajib'              => SavingType::SIMPANAN_WAJIB->value,
            'tabungan_anggota'   => SavingType::TABUNGAN_ANGGOTA->value,
            'tabungan_berjangka' => SavingType::TABUNGAN_BERJANGKA->value,
            'tabungan_ibadah'    => SavingType::TABUNGAN_IBADAH->value,
            'tabungan_sosial'    => SavingType::TABUNGAN_SOSIAL->value,
        ];
    
        return SavingTransaction::with(['savingAccount.user'])
            ->where('status', TransactionStatus::COMPLETED)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('savingAccount', function ($sa) use ($typeMap, $tab) {
                    $sa->where('type', $typeMap[$tab]);
                });
            })
            // Filter grup: 'simpanan' → 3 tipe simpanan
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('type', [
                        SavingType::SIMPANAN_POKOK->value,
                        SavingType::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('type', [
                        SavingType::TABUNGAN_ANGGOTA->value,
                        SavingType::TABUNGAN_BERJANGKA->value,
                        SavingType::TABUNGAN_IBADAH->value,
                        SavingType::TABUNGAN_SOSIAL->value,
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
                    'no_transaksi' => str_pad($trx->id, 6, '0', STR_PAD_LEFT),
                    'tanggal'      => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'anggota'      => $trx->savingAccount->user->member_number
                                    . ' - '
                                    . $trx->savingAccount->user->name,
                    'nominal'      => $trx->type === 'Penarikan'
                                    ? -$trx->amount
                                    : $trx->amount,
                    'produk'       => $trx->savingAccount->type, // nama lengkap
                    'jenis'        => $trx->type,
                ];
            });
    
        $summaryBase     = $this->baseQuery($request);
        $totalMasuk      = (clone $summaryBase)->where('type', 'Penyetoran')->sum('amount');
        $totalKeluar     = (clone $summaryBase)->where('type', 'Penarikan')->sum('amount');
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
            'sukarela'           => 'Data Simpanan Sukarela',
            'tabungan'           => 'Data Semua Tabungan',
            'tabungan_anggota'   => 'Data Tabungan Anggota',
            'tabungan_berjangka' => 'Data Tabungan Berjangka',
            'tabungan_ibadah'    => 'Data Tabungan Ibadah',
            'tabungan_sosial'    => 'Data Tabungan Sosial',
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
                    $trx->savingAccount->user->member_number . ' - ' . $trx->savingAccount->user->name,
                    $trx->savingAccount->type,
                    $trx->type,
                    $trx->type === 'Penarikan' ? -$trx->amount : $trx->amount,
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
        $data = SavingTransaction::with('savingAccount.user', 'account', 'savingTransactionDoc')->find($id);

        if ($data->savingTransactionDoc && $data->savingTransactionDoc->isNotEmpty()) {
            $firstDoc = $data->savingTransactionDoc->first();
            if ($firstDoc && $firstDoc->attachment) {
                $firstDoc->attachment = asset('storage/' . $firstDoc->attachment);
            }
        }

        return inertia('Admin/Savings/Show', [
            'data' => $data,
        ]);
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
