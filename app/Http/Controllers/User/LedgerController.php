<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LedgerController extends Controller
{
    /**
     * running balance calculation
     */
    private function transformTransactions($transactions, $includeId = false)
    {
        $accountBalances = [];

        return $transactions->map(function ($transaction) use (&$accountBalances, $includeId) {
            $isDeposit = in_array(strtolower($transaction->type), ['penyetoran', 'deposit'], true);
            $amount = (float) $transaction->amount;
            $firstDoc = $transaction->savingTransactionDoc?->first();

            $savingAccountId = (string) ($transaction->saving_account_id ?? '');
            if (!array_key_exists($savingAccountId, $accountBalances)) {
                $accountBalances[$savingAccountId] = (float) ($transaction->savingAccount?->balance ?? 0);
            }

            $saldoSesudah = (float) $accountBalances[$savingAccountId];
            $transactionEffect = $isDeposit ? $amount : -$amount;
            $saldoSebelum = $saldoSesudah - $transactionEffect;
            $accountBalances[$savingAccountId] = $saldoSebelum;

            $linkedAccount = $transaction->account;
            if (!$linkedAccount && $transaction->savingAccount?->user?->accounts) {
                $linkedAccount = $transaction->savingAccount->user->accounts
                    ->firstWhere('account_number', $transaction->account_number)
                    ?? $transaction->savingAccount->user->accounts->first();
            }

            $result = [
                'no_transaksi' => $transaction->transaction_code,
                'tanggal_raw' => optional($transaction->transaction_date)?->toISOString(),
                'tanggal' => optional($transaction->transaction_date)->format('d/m/Y'),
                'produk' => $transaction->savingAccount->type ?? 'N/A',
                'jenis' => $transaction->type,
                'jenis_simpanan' => $transaction->savingAccount->type ?? 'N/A',
                'metode' => $transaction->method ?? 'N/A',
                'petugas' => $transaction->updatedBy?->name ?? 'System',
                'nama_anggota' => $transaction->savingAccount?->user?->name ?? '-',
                'no_anggota' => $transaction->savingAccount?->user?->member_code ?? '-',
                'debit' => $isDeposit ? $amount : 0,
                'kredit' => !$isDeposit ? $amount : 0,
                'saldo' => $saldoSesudah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'nominal_transaksi' => $amount,
                'status' => $transaction->status,
                'bank_name' => $linkedAccount?->bank_name ?? '',
                'account_name' => $linkedAccount?->account_name ?? '',
                'account_number' => $linkedAccount?->account_number ?? ($transaction->account_number ?? ''),
                'tenor' => $transaction->savingAccount?->tenor_months,
                'target' => $transaction->savingAccount?->target_amount,
                'struk_nama' => $firstDoc?->name,
                'struk_attachment' => $firstDoc?->attachment
                    ? asset('storage/' . ltrim($firstDoc->attachment, '/'))
                    : null,
            ];

            if ($includeId) {
                $result['id'] = $transaction->id;
            }

            return $result;
        });
    }

    private function buildLedgerTransactionQuery(int|string $userId, ?string $month, ?string $search)
    {
        $query = SavingTransaction::query()
            ->with(['savingAccount.user.accounts', 'updatedBy', 'savingTransactionDoc', 'account'])
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

        return $query;
    }

    private function buildSavingSummaryAndMeta(int|string $userId): array
    {
        $savingAccounts = SavingAccount::where('user_id', $userId)->get();
        $savingSummary = [
            'simpanan_pokok' => 0,
            'simpanan_wajib' => 0,
            'tabungan_anggota' => 0,
            'tabungan_berjangka' => 0,
            'tabungan_ibadah' => 0,
        ];
        $savingMeta = [
            'tabungan_berjangka' => [
                'maturity_date' => null,
            ],
            'tabungan_ibadah' => [
                'minimum_target' => null,
            ],
        ];

        foreach ($savingAccounts as $account) {
            $accountType = Str::lower((string) $account->type);

            $typeKey = match ($accountType) {
                'simpanan pokok' => 'simpanan_pokok',
                'simpanan wajib' => 'simpanan_wajib',
                'simpanan sukarela', 'tabungan anggota' => 'tabungan_anggota',
                'tabungan berjangka' => 'tabungan_berjangka',
                'tabungan ibadah' => 'tabungan_ibadah',
                default => Str::snake($accountType),
            };

            if (!array_key_exists($typeKey, $savingSummary)) {
                $savingSummary[$typeKey] = 0;
            }

            $savingSummary[$typeKey] += (float) $account->balance;

            if ($typeKey === 'tabungan_berjangka') {
                $tenorMonths = (int) ($account->tenor_months ?? 0);

                if ($tenorMonths > 0 && $account->created_at) {
                    $maturityDate = Carbon::parse($account->created_at)
                        ->addMonths($tenorMonths)
                        ->startOfDay();

                    $currentMaturityDate = $savingMeta['tabungan_berjangka']['maturity_date'];
                    if (!$currentMaturityDate || $maturityDate->lt(Carbon::parse($currentMaturityDate))) {
                        $savingMeta['tabungan_berjangka']['maturity_date'] = $maturityDate->format('Y-m-d');
                    }
                }
            }

            if ($typeKey === 'tabungan_ibadah') {
                $targetAmount = (float) ($account->target_amount ?? 0);
                $currentMinimumTarget = $savingMeta['tabungan_ibadah']['minimum_target'];

                if ($targetAmount > 0 && (!$currentMinimumTarget || $targetAmount < $currentMinimumTarget)) {
                    $savingMeta['tabungan_ibadah']['minimum_target'] = $targetAmount;
                }
            }
        }

        return [$savingSummary, $savingMeta];
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

        $query = $this->buildLedgerTransactionQuery($userId, $month, $search);

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
            'no_anggota' => $member->member_code,
            'status' => $member->status,
            'tanggal_bergabung' => optional($member->created_at)->format('d F Y'),
        ];

        [$savingSummary, $savingMeta] = $this->buildSavingSummaryAndMeta($userId);

        return Inertia::render('User/Ledger/List', [
            'transactions' => $transactions,
            'memberInfo' => $memberInfo,
            'savings' => $savingSummary,
            'savingMeta' => $savingMeta,
            'filters' => [
                'search' => $search ?? '',
                'month' => $month ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Export ledger data to XLS
     */
    public function export(Request $request)
    {
        $userId = auth()->id();
        $month = $request->get('month');
        $search = $request->get('search');

        $query = $this->buildLedgerTransactionQuery($userId, $month, $search);
        $query->orderBy('transaction_date', 'desc');

        $transactions = $query->get();
        $rows = $this->transformTransactions($transactions, false);
        $member = auth()->user();

        $filename = 'ledger_' . $member->member_code . '_' . now()->format('Ymd_His') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($rows, $member) {
            echo "\xEF\xBB\xBF";
            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; width: 100%; }';
            echo 'th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; font-size: 11px; }';
            echo 'th { background-color: #f3f4f6; font-weight: bold; }';
            echo '.num { text-align: right; }';
            echo '.pos { color: #059669; }';
            echo '.neg { color: #dc2626; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';
            echo '<tr><th colspan="6" style="background:#d9f99d;color:#065f46;font-size:16px;">Buku Besar Personal</th></tr>';
            echo '<tr><td colspan="6"><strong>Nama Anggota:</strong> ' . htmlspecialchars((string) $member->name) . '</td></tr>';
            echo '<tr><td colspan="6"><strong>No Anggota:</strong> ' . htmlspecialchars((string) $member->member_code) . '</td></tr>';
            echo '<tr><td colspan="6"><strong>Tanggal Export:</strong> ' . now()->format('d/m/Y H:i') . '</td></tr>';
            echo '<tr><td colspan="6"></td></tr>';

            echo '<tr>';
            echo '<th>Tanggal</th>';
            echo '<th>Produk</th>';
            echo '<th>Jenis</th>';
            echo '<th>Metode</th>';
            echo '<th>Petugas</th>';
            echo '<th>Nominal</th>';
            echo '</tr>';

            foreach ($rows as $row) {
                $debit = (float) ($row['debit'] ?? 0);
                $kredit = (float) ($row['kredit'] ?? 0);
                $nominal = $debit - $kredit;
                $nominalClass = $nominal > 0 ? 'pos' : ($nominal < 0 ? 'neg' : '');
                $nominalText = $nominal > 0
                    ? '+ ' . number_format($nominal, 0, ',', '.')
                    : ($nominal < 0
                        ? '- ' . number_format(abs($nominal), 0, ',', '.')
                        : '-');

                echo '<tr>';
                echo '<td>' . htmlspecialchars((string) ($row['tanggal'] ?? '-')) . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['produk'] ?? '-')) . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['jenis'] ?? '-')) . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['metode'] ?? '-')) . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['petugas'] ?? '-')) . '</td>';
                echo '<td class="num ' . $nominalClass . '">' . $nominalText . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, 200, $headers);
    }
}
