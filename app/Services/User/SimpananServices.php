<?php

namespace App\Services\User;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Anggota;
use App\Models\RekeningAnggota;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Services\Admin\JurnalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SimpananServices
{
    public function __construct(private JurnalService $jurnalService)
    {
    }

    public function storeWithdrawal(array $validated, string $userId): array
    {
        $anggota = Anggota::with('user')->findOrFail($validated['anggota_id']);
        $akunSimpanan = AkunSimpanan::with(['ibadah', 'berjangka'])->findOrFail($validated['akun_simpanan_id']);
        $savingBalance = $akunSimpanan->saldo;

        if ((int) $akunSimpanan->anggota_id !== (int) $anggota->id) {
            throw ValidationException::withMessages([
                'akun_simpanan_id' => 'Rekening simpanan tidak ditemukan untuk anggota ini'
            ]);
        }

        if ($savingBalance < $validated['amount']) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo tidak cukup untuk penarikan sebesar Rp ' . number_format($validated['amount'])
            ]);
        }

        if ($anggota->status === MemberStatusEnum::ACTIVE->value && in_array($akunSimpanan->jenis_simpanan, [SavingTypeEnum::SIMPANAN_POKOK->value, SavingTypeEnum::SIMPANAN_WAJIB->value])) {
            throw ValidationException::withMessages([
                'akun_simpanan_id' => $akunSimpanan->jenis_simpanan . ' tidak dapat ditarik selama status keanggotaan masih aktif.'
            ]);
        }

        $savingType = (string)($akunSimpanan->jenis_simpanan ?? '');
        $typeLower = mb_strtolower($savingType);

        if (str_contains($typeLower, 'berjangka')) {
            $tenorMonths = (int) ($akunSimpanan->berjangka?->tenor ?? 0);
            if ($tenorMonths > 0 && $akunSimpanan->created_at) {
                $maturityDate = Carbon::parse($akunSimpanan->created_at)->addMonths($tenorMonths)->startOfDay();
                if (Carbon::today()->lt($maturityDate)) {
                    throw ValidationException::withMessages([
                        'akun_simpanan_id' => 'Tabungan berjangka belum jatuh tempo. Pencairan dapat dilakukan mulai ' . $maturityDate->format('d/m/Y'),
                    ]);
                }
            }
        }

        if (str_contains($typeLower, 'ibadah')) {
            $targetAmount = (float) ($akunSimpanan->ibadah?->target_amount ?? 0);
            if ($targetAmount > 0 && (float) $savingBalance < $targetAmount) {
                throw ValidationException::withMessages([
                    'akun_simpanan_id' => 'Tabungan ibadah belum mencapai target minimal Rp ' . number_format($targetAmount, 0, ',', '.'),
                ]);
            }
        }

        [$transaction, $saldoSebelum] = DB::transaction(function () use ($validated, $anggota, $akunSimpanan, $savingType, $userId) {
            $lockedSavingAccount = AkunSimpanan::query()
                ->whereKey($akunSimpanan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $saldoSebelum = $lockedSavingAccount->saldo;
            if ($saldoSebelum === null) {
                $saldoSebelum = (float) ($lockedSavingAccount->saldo ?? 0);
            } else {
                $saldoSebelum = (float) $saldoSebelum;
            }

            if ($saldoSebelum < (float) $validated['amount']) {
                throw new \RuntimeException('Saldo tidak cukup untuk penarikan.');
            }

            $transaction = TransaksiSimpanan::create([
                'kode_transaksi_simpanan' => $this->generateWithdrawalTransactionCode($savingType),
                'akun_simpanan_id' => $lockedSavingAccount->id,
                'saldo_setelah_transaksi' => $saldoSebelum - $validated['amount'],
                'nominal_simpanan' => $validated['amount'],
                'tipe_transaksi' => TransactionTypeEnum::WITHDRAWAL->value,
                'metode_pembayaran_simpanan' => $validated['method'],
                'tgl_transaksi' => $validated['withdrawal_date'],
                'deskripsi_simpanan' => $validated['notes'] ?? '',
                'updated_by' => $userId,
            ]);

            if ($validated['method'] === 'Non-Tunai') {
                RekeningAnggota::updateOrCreate(
                    [
                        'anggota_id' => $anggota->id,
                        'no_rekening' => $validated['no_rekening'],
                    ],
                    [
                        'nama_bank' => $validated['nama_bank'],
                        'atas_nama' => $validated['atas_nama'],
                    ]
                );

                $transaction->update([
                    'no_rekening' => $validated['no_rekening'],
                ]);
            }

            $lockedSavingAccount->update([
                'saldo' => $saldoSebelum - $validated['amount'],
            ]);

            return [$transaction, $saldoSebelum];
        });

        $strukData = [
            'transaction_id' => $transaction->id,
            'no_transaksi' => $transaction->kode_transaksi_simpanan,
            'tanggal' => $transaction->tgl_transaksi,
            'pengurus' => auth()->user()->nama ?? 'Pengurus',
            'nama_anggota' => $anggota->user?->nama ?? '-',
            'no_anggota' => $anggota->user?->kode_pengguna ?? '-',
            'jenis' => $savingType !== '' ? $savingType : '-',
            'metode' => $validated['method'],
            'nominal' => $validated['amount'],
            'saldo_sebelum' => $saldoSebelum,
            'saldo_sesudah' => $saldoSebelum - $validated['amount'],
            'nama_bank' => $validated['nama_bank'] ?? '',
            'atas_nama' => $validated['atas_nama'] ?? '',
            'no_rekening' => $validated['no_rekening'] ?? '',
        ];

        try {
            $receiptPath = $this->storeReceiptWithdrawalPdf($transaction, $strukData);
            if ($receiptPath) {
                $transaction->update([
                    'struk_simpanan' => $receiptPath,
                ]);
            }
        } catch (\Throwable $receiptException) {
            report($receiptException);
        }

        return [
            'struk' => $strukData,
            'receipt' => $receiptPath ? Storage::url($receiptPath) : null,
        ];
    }

    private function storeReceiptWithdrawalPdf(TransaksiSimpanan $transaction, array $strukData): ?string
    {
        try {
            $pdf = Pdf::loadView('exports.withdrawal_receipt', [
                'struk' => $strukData,
            ])->setPaper([0, 0, 226.77, 600], 'portrait');
            
            $directory = 'member_docs/receipts/' . now()->format('Y-m');
            Storage::disk('public')->makeDirectory($directory);
            
            $filename = 'struk-withdrawal-' . $transaction->id . '.pdf';
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            if (!Storage::disk('public')->exists($path)) {
                try {
                    $full = storage_path('app/public/' . $path);
                    $dir = dirname($full);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($full, $pdf->output());
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if (Storage::disk('public')->exists($path) || file_exists(storage_path('app/public/' . $path))) {
                return $path;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function generateWithdrawalTransactionCode(string $savingType): string
    {
        $yymm = Carbon::now()->format('ym');
        $categoryPrefix = $this->getTrxPrefix($savingType);
        $prefix = $categoryPrefix . $yymm;

        $latestTransaction = TransaksiSimpanan::where('tipe_transaksi', TransactionTypeEnum::WITHDRAWAL->value)
            ->where('kode_transaksi_simpanan', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('kode_transaksi_simpanan')
            ->first();

        $lastNumber = 0;
        if ($latestTransaction) {
            preg_match('/(\d{4})$/', (string) $latestTransaction->kode_transaksi_simpanan, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
        }

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function getTrxPrefix(string $category): string
    {
        return match ($category) {
            'Tabungan Anggota' => 'TA',
            'Simpanan Pokok' => 'SP',
            'Simpanan Wajib' => 'SW',
            'Tabungan Berjangka' => 'TB',
            'Tabungan Ibadah' => 'TI',
            default => 'ST',
        };
    }
}
