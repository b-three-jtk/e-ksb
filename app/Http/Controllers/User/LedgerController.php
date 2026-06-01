<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    /**
     * running balance calculation
     */
    private function transformTransactions($transactions, $includeId = false)
    {
        $accountBalances = [];

        return $transactions->map(function ($transaction) use (&$accountBalances, $includeId) {
            $isDeposit = in_array(strtolower((string) $transaction->transaction_type), ['penyetoran', 'deposit'], true);
            $amount = (float) ($transaction->saving_amount ?? 0);
            $transactionDate = $transaction->transaction_date ? Carbon::parse($transaction->transaction_date) : null;

            $savingAccountId = (string) ($transaction->saving_account_id ?? '');
            if (!array_key_exists($savingAccountId, $accountBalances)) {
                $accountBalances[$savingAccountId] = (float) ($transaction->savingAccount?->balance ?? 0);
            }

            $saldoSesudah = (float) $accountBalances[$savingAccountId];
            $transactionEffect = $isDeposit ? $amount : -$amount;
            $saldoSebelum = $saldoSesudah - $transactionEffect;
            $accountBalances[$savingAccountId] = $saldoSebelum;

            $linkedAccount = $transaction->memberBankAccount;
            if (!$linkedAccount && $transaction->savingAccount?->member?->bankAccounts) {
                $linkedAccount = $transaction->savingAccount->member->bankAccounts
                    ->firstWhere('account_number', $transaction->account_number)
                    ?? $transaction->savingAccount->member->bankAccounts->first();
            }

            $receiptPath = (string) ($transaction->saving_transaction_receipt ?? '');

            $result = [
                'no_transaksi' => $transaction->saving_transaction_code,
                'tanggal_raw' => $transactionDate?->toISOString(),
                'tanggal' => $transactionDate?->format('d/m/Y') ?? '-',
                'produk' => $transaction->savingAccount?->saving_type ?? 'N/A',
                'jenis' => $transaction->transaction_type,
                'jenis_simpanan' => $transaction->savingAccount?->saving_type ?? 'N/A',
                'metode' => $transaction->saving_payment_method ?? 'N/A',
                'petugas' => $transaction->updatedBy?->name ?? 'System',
                'nama_anggota' => $transaction->savingAccount?->member?->user?->name ?? '-',
                'no_anggota' => $transaction->savingAccount?->member?->user?->user_code ?? '-',
                'debit' => $isDeposit ? $amount : 0,
                'kredit' => !$isDeposit ? $amount : 0,
                'saldo' => $saldoSesudah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'nominal_transaksi' => $amount,
                'status' => null,
                'bank_name' => $linkedAccount?->bank_name ?? '',
                'account_name' => $linkedAccount?->account_name ?? '',
                'account_number' => $linkedAccount?->account_number ?? ($transaction->account_number ?? ''),
                'tenor' => $transaction->savingAccount?->saving_tenor,
                'target' => $transaction->savingAccount?->target_amount,
                'struk_nama' => $receiptPath !== '' ? basename($receiptPath) : null,
                'struk_attachment' => $receiptPath !== ''
                    ? asset('storage/' . ltrim($receiptPath, '/'))
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
            ->with(['savingAccount.member.bankAccounts', 'savingAccount', 'updatedBy', 'memberBankAccount'])
            ->whereHas('savingAccount.member', function ($q) use ($userId) {
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
                $q->whereRaw('LOWER(transaction_type) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereRaw('LOWER(saving_payment_method) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereHas('savingAccount', function ($subQ) use ($searchLower) {
                        $subQ->whereRaw('LOWER(saving_type) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        return $query;
    }

    private function buildSavingSummaryAndMeta(int|string $userId): array
    {
        $savingAccounts = SavingAccount::query()
            ->whereHas('member', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get();
        $savingSummary = [
            'total_saldo' => 0,
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
            $accountType = Str::lower((string) ($account->saving_type ?? ''));
            $rawBalance = (float) ($account->balance ?? 0);
            $currentBalance = max(0, $rawBalance);

            $savingSummary['total_saldo'] += $currentBalance;

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

            $savingSummary[$typeKey] += $currentBalance;

            if ($typeKey === 'tabungan_berjangka') {
                $tenorMonths = (int) ($account->saving_tenor ?? 0);

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
        $month = $request->input('month');
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

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
            'no_anggota' => $member->user_code,
            'status' => $member->status,
            'tanggal_bergabung' => $member->joined_date->format('d F Y'),
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
     * Export ledger data to PDF (bank statement format)
     */
    public function export(Request $request)
    {
        $userId = auth()->id();
        $month = $request->get('month');
        $search = $request->get('search');

        $query = $this->buildLedgerTransactionQuery($userId, $month, $search);
        $query->orderBy('transaction_date', 'asc');

        $transactions = $query->get();
        $rows = $this->transformTransactions($transactions, false);
        $member = auth()->user();

        // Calculate summary data
        $totalDebit = $rows->sum('debit');
        $totalKredit = $rows->sum('kredit');
        $endingBalance = $totalDebit - $totalKredit;

        // Determine date range
        $startDate = $rows->min('tanggal_raw') ? Carbon::parse($rows->min('tanggal_raw')) : now();
        $endDate = $rows->max('tanggal_raw') ? Carbon::parse($rows->max('tanggal_raw')) : now();

        $memberInfo = [
            'nama' => $member->name,
            'no_anggota' => $member->user_code,
            'status' => $member->status,
            'tanggal_bergabung' => optional($member->created_at)->format('d F Y'),
        ];

        $filename = 'Mutasi_Simpanan_' . $member->user_code . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('exports.ledger_statement', [
            'transactions' => $rows,
            'member' => $memberInfo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDebit' => $totalDebit,
            'totalKredit' => $totalKredit,
            'endingBalance' => $endingBalance,
        ]);

        return $pdf->download($filename);
    }
}
