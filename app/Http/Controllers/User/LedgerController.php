<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LedgerController extends Controller
{
    /**
     * running balance calculation
     */
    private function transformTransactions($transactions, $includeId = false)
    {
        $runningBalance = 0;
        return $transactions->map(function ($transaction) use (&$runningBalance, $includeId) {
            $isDeposit = in_array(strtolower($transaction->type), ['penyetoran', 'deposit']);
            $amount = (float) $transaction->amount;

            if ($isDeposit) {
                $runningBalance += $amount;
            } else {
                $runningBalance -= $amount;
                if ($runningBalance < 0) {
                    $runningBalance = 0;
                }
            }

            $result = [
                'tanggal' => optional($transaction->transaction_date)->format('d/m/Y'),
                'produk' => $transaction->savingAccount->type ?? 'N/A',
                'jenis' => $transaction->type,
                'metode' => $transaction->method ?? 'N/A',
                'petugas' => $transaction->updatedBy?->name ?? 'System',
                'debit' => $isDeposit ? $amount : 0,
                'kredit' => !$isDeposit ? $amount : 0,
                'saldo' => $runningBalance,
                'status' => $transaction->status,
            ];

            if ($includeId) {
                $result['id'] = $transaction->id;
            }

            return $result;
        });
    }

    /**
     * Display ledger page with transactions
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $month = $request->get('month');
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        $query = SavingTransaction::query()
            ->with(['savingAccount', 'updatedBy'])
            ->whereHas('savingAccount', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        // Filter berdasarkan bulan jika ada
        if ($month && $month !== '') {
            $parsedYear = null;
            $parsedMonth = null;

            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                [$y, $m] = explode('-', $month);
                $parsedYear = (int) $y;
                $parsedMonth = (int) $m;
            } elseif (preg_match('/^\d{1,2}$/', $month)) {
                $parsedYear = (int) now()->year;
                $parsedMonth = (int) $month;
            }

            if ($parsedYear && $parsedMonth) {
                $query->whereYear('transaction_date', $parsedYear)
                    ->whereMonth('transaction_date', $parsedMonth);
            }
        }

        // Search filter
        if ($search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(type) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereRaw('LOWER(method) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereHas('savingAccount', function ($subQ) use ($searchLower) {
                        $subQ->whereRaw('LOWER(type) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        // Sort berdasarkan tanggal
        $query->orderBy('transaction_date', 'desc');

        // Pagination
        $transactions = $query->paginate($perPage)->withQueryString();

        $data = $this->transformTransactions($transactions->getCollection(), true);
        $transactions->setCollection($data);

        // Get member info
        $member = auth()->user();
        $memberInfo = [
            'nama' => $member->name,
            'no_anggota' => $member->member_number,
            'status' => $member->status,
            'tanggal_bergabung' => optional($member->created_at)->format('d F Y'),
        ];

        // Get saving summary
        $savingAccounts = SavingAccount::where('user_id', $userId)->get();
        $savingSummary = [
            'simpanan_pokok' => 0,
            'simpanan_wajib' => 0,
            'simpanan_sukarela' => 0,
        ];

        foreach ($savingAccounts as $account) {
            if ($account->type === 'Simpanan Pokok') {
                $savingSummary['simpanan_pokok'] = $account->balance;
            } elseif ($account->type === 'Simpanan Wajib') {
                $savingSummary['simpanan_wajib'] = $account->balance;
            } elseif ($account->type === 'Simpanan Sukarela') {
                $savingSummary['simpanan_sukarela'] = $account->balance;
            }
        }

        return Inertia::render('User/Ledger/List', [
            'transactions' => $transactions,
            'memberInfo' => $memberInfo,
            'savings' => $savingSummary,
            'filters' => [
                'search' => $search ?? '',
                'month' => $month ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Export ledger data to Excel
     */
    public function export(Request $request)
    {
        $userId = auth()->id();
        $month = $request->get('month');
        $search = $request->get('search');

        $query = SavingTransaction::query()
            ->with(['savingAccount', 'updatedBy'])
            ->whereHas('savingAccount', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        if ($month && $month !== '') {
            $parsedYear = null;
            $parsedMonth = null;

            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                [$y, $m] = explode('-', $month);
                $parsedYear = (int) $y;
                $parsedMonth = (int) $m;
            } elseif (preg_match('/^\d{1,2}$/', $month)) {
                $parsedYear = (int) now()->year;
                $parsedMonth = (int) $month;
            }

            if ($parsedYear && $parsedMonth) {
                $query->whereYear('transaction_date', $parsedYear)
                    ->whereMonth('transaction_date', $parsedMonth);
            }
        }
        // Search filter
        if ($search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(type) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereRaw('LOWER(method) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereHas('savingAccount', function ($subQ) use ($searchLower) {
                        $subQ->whereRaw('LOWER(type) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }
        $query->orderBy('transaction_date', 'asc');

        $transactions = $query->get();

        $data = $this->transformTransactions($transactions, false);

        // Get member info
        $member = auth()->user();
        
        // Generate Excel file
        $filename = 'ledger_' . $member->member_number . '_' . date('YmdHis') . '.xls';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($data, $member) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial; }';
            echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
            echo 'th { background-color: #4472C4; color: white; font-weight: bold; }';
            echo '.number { text-align: right; }';
            echo '.currency { mso-number-format:"\#\,\#\#0"; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<table>';
            
            // Header info
            echo '<tr><th colspan="8" style="background-color: #2E5090; font-size: 16px;">Personal Ledger - ' . htmlspecialchars($member->name) . '</th></tr>';
            echo '<tr><td colspan="8"><strong>No. Anggota:</strong> ' . htmlspecialchars($member->member_number) . '</td></tr>';
            echo '<tr><td colspan="8"><strong>Tanggal Export:</strong> ' . now()->format('d/m/Y H:i') . '</td></tr>';
            echo '<tr><td colspan="8"></td></tr>';
            
            // Column headers
            echo '<tr>';
            echo '<th>Tanggal</th>';
            echo '<th>Produk</th>';
            echo '<th>Jenis</th>';
            echo '<th>Metode</th>';
            echo '<th>Petugas</th>';
            echo '<th>Debit</th>';
            echo '<th>Kredit</th>';
            echo '<th>Saldo</th>';
            echo '</tr>';
            
            // Data rows
            foreach ($data as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tanggal']) . '</td>';
                echo '<td>' . htmlspecialchars($row['produk']) . '</td>';
                echo '<td>' . htmlspecialchars($row['jenis']) . '</td>';
                echo '<td>' . htmlspecialchars($row['metode']) . '</td>';
                echo '<td>' . htmlspecialchars($row['petugas']) . '</td>';
                echo '<td class="number currency">' . ($row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-') . '</td>';
                echo '<td class="number currency">' . ($row['kredit'] > 0 ? number_format($row['kredit'], 0, ',', '.') : '-') . '</td>';
                echo '<td class="number currency">' . number_format($row['saldo'], 0, ',', '.') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            echo '</body>';
            echo '</html>';
        };

        return response()->stream($callback, 200, $headers);
    }
}
