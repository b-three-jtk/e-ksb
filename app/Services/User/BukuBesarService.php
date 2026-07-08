<?php

namespace App\Services\User;

use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BukuBesarService
{
    public function transformTransactions(Collection $transactions, bool $includeId = false): Collection
    {
        $accountBalances = [];

        return $transactions->map(function ($transaction) use (&$accountBalances, $includeId) {
            $isDeposit = in_array(strtolower((string) $transaction->tipe_transaksi), ['penyetoran', 'deposit'], true);
            $amount = (float) ($transaction->nominal_simpanan ?? 0);
            $transactionDate = $transaction->tgl_transaksi ? Carbon::parse($transaction->tgl_transaksi) : null;

            $akunSimpananId = (string) ($transaction->akun_simpanan_id ?? '');
            if (!array_key_exists($akunSimpananId, $accountBalances)) {
                $accountBalances[$akunSimpananId] = (float) ($transaction->akunSimpanan?->saldo ?? 0);
            }

            $saldoSesudah = (float) $accountBalances[$akunSimpananId];
            $transactionEffect = $isDeposit ? $amount : -$amount;
            $saldoSebelum = $saldoSesudah - $transactionEffect;
            $accountBalances[$akunSimpananId] = $saldoSebelum;

            $linkedAccount = $transaction->rekeningAnggota;
            if (!$linkedAccount && $transaction->akunSimpanan?->anggota?->bankAccounts) {
                $linkedAccount = $transaction->akunSimpanan->anggota->bankAccounts
                    ->firstWhere('no_rekening', $transaction->no_rekening)
                    ?? $transaction->akunSimpanan->anggota->bankAccounts->first();
            }

            $receiptPath = (string) ($transaction->struk_simpanan ?? '');

            $result = [
                'no_transaksi' => $transaction->kode_transaksi_simpanan,
                'tanggal_raw' => $transactionDate?->toISOString(),
                'tanggal' => $transactionDate?->format('d/m/Y') ?? '-',
                'produk' => $transaction->akunSimpanan?->jenis_simpanan ?? 'N/A',
                'jenis' => $transaction->tipe_transaksi,
                'jenis_simpanan' => $transaction->akunSimpanan?->jenis_simpanan ?? 'N/A',
                'metode' => $transaction->metode_pembayaran_simpanan ?? 'N/A',
                'petugas' => $transaction->updatedBy?->nama ?? 'System',
                'nama_anggota' => $transaction->akunSimpanan?->anggota?->user?->nama ?? '-',
                'no_anggota' => $transaction->akunSimpanan?->anggota?->user?->kode_pengguna ?? '-',
                'debit' => $isDeposit ? $amount : 0,
                'kredit' => !$isDeposit ? $amount : 0,
                'saldo' => $saldoSesudah,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'nominal_transaksi' => $amount,
                'status' => null,
                'nama_bank' => $linkedAccount?->nama_bank ?? '',
                'atas_nama' => $linkedAccount?->atas_nama ?? '',
                'no_rekening' => $linkedAccount?->no_rekening ?? ($transaction->no_rekening ?? ''),
                'tenor' => $transaction->akunSimpanan?->berjangka?->tenor,
                'target' => $transaction->akunSimpanan?->ibadah?->target_tabungan,
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

    public function buildTabunganTransactionQuery(int|string $userId, ?string $month, ?string $search): Builder
    {
        $query = TransaksiSimpanan::query()
            ->with(['akunSimpanan.anggota.bankAccounts', 'akunSimpanan', 'updatedBy', 'rekeningAnggota'])
            ->whereHas('akunSimpanan.anggota', function ($q) use ($userId) {
                $q->where('pengguna_id', $userId);
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
                $query->whereYear('tgl_transaksi', $parsedYear)
                    ->whereMonth('tgl_transaksi', $parsedMonth);
            }
        }

        if ($search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(tipe_transaksi) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereRaw('LOWER(metode_pembayaran_simpanan) LIKE ?', ['%' . $searchLower . '%'])
                    ->orWhereHas('akunSimpanan', function ($subQ) use ($searchLower) {
                        $subQ->whereRaw('LOWER(jenis_simpanan) LIKE ?', ['%' . $searchLower . '%']);
                    });
            });
        }

        return $query;
    }

    public function buildSavingSummaryAndMeta(int|string $userId): array
    {
        $akunSimpanan = AkunSimpanan::query()
            ->whereHas('anggota', function ($q) use ($userId) {
                $q->where('pengguna_id', $userId);
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

        foreach ($akunSimpanan as $account) {
            $accountType = Str::lower((string) ($account->jenis_simpanan ?? ''));
            $rawBalance = (float) ($account->saldo ?? 0);
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
                $tenorMonths = (int) ($account->berjangka?->tenor ?? 0);

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
                $targetAmount = (float) ($account->ibadah?->target_tabungan ?? 0);
                $currentMinimumTarget = $savingMeta['tabungan_ibadah']['minimum_target'];

                if ($targetAmount > 0 && (!$currentMinimumTarget || $targetAmount < $currentMinimumTarget)) {
                    $savingMeta['tabungan_ibadah']['minimum_target'] = $targetAmount;
                }
            }
        }

        return [$savingSummary, $savingMeta];
    }

    public function exportTabunganPdf(int|string $userId, ?string $month, ?string $search): array
    {
        $query = $this->buildTabunganTransactionQuery($userId, $month, $search);
        $query->orderBy('tgl_transaksi', 'asc');

        $transactions = $query->get();
        $rows = $this->transformTransactions($transactions, false);
        $anggota = Auth::user();

        $totalDebit = $rows->sum('debit');
        $totalKredit = $rows->sum('kredit');
        $endingBalance = $totalDebit - $totalKredit;

        $startDate = $rows->min('tanggal_raw') ? Carbon::parse($rows->min('tanggal_raw')) : now();
        $endDate = $rows->max('tanggal_raw') ? Carbon::parse($rows->max('tanggal_raw')) : now();

        $memberInfo = [
            'nama' => $anggota->nama,
            'no_anggota' => $anggota->kode_pengguna,
            'status' => $anggota->status,
            'tanggal_bergabung' => optional($anggota->created_at)->format('d F Y'),
        ];

        $filename = 'Mutasi_Simpanan_' . $anggota->kode_pengguna . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('exports.tabungan_statement', [
            'transactions' => $rows,
            'anggota'=> $memberInfo,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDebit' => $totalDebit,
            'totalKredit' => $totalKredit,
            'endingBalance' => $endingBalance,
        ]);

        return [
            'pdf' => $pdf,
            'filename' => $filename,
        ];
    }
}
