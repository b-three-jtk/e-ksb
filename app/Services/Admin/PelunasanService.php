<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\PositionEnum;
use App\Models\Account;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financing;
use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PelunasanService
{
    public function calculateDetails(Financing $financing): array
    {
        $tenor = $financing->tenor;

        $basePrincipal = $financing->cost_price - $financing->down_payment;
        $marginAmount = $financing->margin_amount;
        $totalPaidInstallments = $financing->installment->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();

        // Asumsi menggunakan metode margin Flat
        $principalPerMonth = $basePrincipal / $tenor;
        $marginPerMonth = $marginAmount / $tenor;
        $installmentPerMonth = $principalPerMonth + $marginPerMonth;

        $tsamanNaqdy = $basePrincipal + $marginPerMonth;

        // Total yang sudah dibayarkan nasabah
        $principalPaid = $principalPerMonth * $totalPaidInstallments;
        $marginPaid = $marginPerMonth * $totalPaidInstallments;
        $totalPaidAmount = $installmentPerMonth * $totalPaidInstallments;

        // --- MENGHITUNG QIMAH HALIYYAH ---
        // Jika lunas di bulan ke-0, margin minimal yang diakui adalah 1 bulan
        $monthsPassedForMargin = max($totalPaidInstallments + 1, 1);
        $marginDiakui = $marginPerMonth * $monthsPassedForMargin;
        $qimahHaliyyah = $basePrincipal + $marginDiakui;

        // --- MENGHITUNG TOTAL PELUNASAN ---
        // Sisa Harga PUPMSJT = Qimah Haliyyah - total yang sudah dibayar
        $repaymentTotal = $qimahHaliyyah - $totalPaidAmount;

        // Jika total pelunasan < Sisa Pokok, maka gunakan Sisa Pokok
        $sisaPokok = $basePrincipal - $principalPaid;
        if ($repaymentTotal < $sisaPokok) {
            $repaymentTotal = $sisaPokok;
        }

        return [
            'financing'               => $financing,
            'total_paid_installments' => $totalPaidInstallments,
            'principal_per_month'     => $principalPerMonth,
            'margin_per_month'        => $marginPerMonth,
            'tsaman_naqdy'            => $tsamanNaqdy,
            'qimah_ismiyyah'          => $basePrincipal + $marginAmount,
            'margin_berjalan'         => $marginPaid,
            'installment_per_month'   => $installmentPerMonth,
            'qimah_haliyyah'          => $qimahHaliyyah,
            'total_paid_amount'       => $totalPaidAmount,
            'repayment_total'         => $repaymentTotal,
            'principal_paid'          => $principalPaid,
            'margin_paid'             => $marginPaid,
        ];
    }

    public function processRepayment($validatedData, string $userId)
    {
        return DB::transaction(function () use ($validatedData, $userId) {
            $data = [];
            $installment = Installment::with('financing.member.user', 'financing.financingItem')
                ->findOrFail($validatedData['installment_id']);

            $financing = $installment->financing;

            Installment::where('financing_id', $installment->financing_id)
                ->where('due_date', '>=', now())
                ->update(['status' => InstallmentPaymentScheduleStatusEnum::PAID->value]);

            $calculatedData = $this->calculateDetails($financing);

            $remainingPrincipal =
                ($financing->cost_price - $financing->down_payment)
                - $calculatedData['principal_paid'];

            $marginSettlement =
                $calculatedData['repayment_total']
                - $remainingPrincipal;

            $transCode = 'LP' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

            // logo
            $logoPath = public_path('images/logo/logo-icon.svg');

            $src = '';
            if (file_exists($logoPath)) {
                $data_logo = file_get_contents($logoPath);
                $src = 'data:image/svg+xml;base64,' . base64_encode($data_logo);
            }

            $strukData = [
                'no_transaksi' => $transCode,
                'tanggal' => now(),
                'no_anggota' => $financing->member->user->user_code,
                'nama_anggota' => $financing->member->user->name,
                'no_telp' => $financing->member->user->phone_number,
                'financing_transaction_code' => $financing->financing_transaction_code,
                'product_name' => $financing->financingItem->name,
                'total_paid_amount' => $calculatedData['total_paid_amount'],
                'metode' => $validatedData['method'],
                'repayment_total' => $calculatedData['repayment_total'],
                'pengurus' => auth()->user()->name,
                'qimah_haliyyah' => $calculatedData['qimah_haliyyah'],
                'logo' => $src,
            ];

            $pdf = Pdf::loadView('exports.repayment_receipt', $strukData);
            $filePath = 'receipts/repayment/' . $transCode . '.pdf';

            Storage::disk('public')->put($filePath, $pdf->output());

            $transaction = InstallmentPaymentTransaction::create([
                'installment_trans_code' => $transCode,
                'nominal' => $calculatedData['repayment_total'],
                'payment_method' => $validatedData['method'],
                'is_early_repayment' => true,
                'payment_date' => now(),
                'installment_id' => $installment->id,
                'updated_by' => $userId,
                'installment_payment_receipt' => $filePath,
            ]);

            $kas = Account::where(
                'account_name',
                'Kas'
            )->firstOrFail();

            $piutangMurabahah = Account::where(
                'account_name',
                'Piutang Murabahah'
            )->firstOrFail();

            $pendapatanMargin = Account::where(
                'account_name',
                'Pendapatan Margin Murabahah'
            )->firstOrFail();

            app(JournalService::class)->create(
            [
                [
                    'account' => $kas->no_ref_account,
                    'position' => PositionEnum::DEBIT->value,
                    'nominal' => $calculatedData['repayment_total'],
                ],
                [
                    'account' => $piutangMurabahah->no_ref_account,
                    'position' => PositionEnum::CREDIT->value,
                    'nominal' => $remainingPrincipal,
                ],
                [
                    'account' => $pendapatanMargin->no_ref_account,
                    'position' => PositionEnum::CREDIT->value,
                    'nominal' => $marginSettlement,
                ],
            ],
            now()->toDateString(),
            auth()->id()
            );

            $financing->update([
                'status' => FinancingReqStatusEnum::PAID->value,
            ]);

            $data['financing_id'] = $installment->financing_id;
            $data['installment_payment_receipt'] = $transaction->installment_payment_receipt ? asset('storage/' . $transaction->installment_payment_receipt) : null;

            return $data;
        });
    }
}
