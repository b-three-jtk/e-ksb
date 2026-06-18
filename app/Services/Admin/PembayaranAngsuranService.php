<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\PositionEnum;
use App\Models\Account;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financing;
use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use App\Models\MemberDoc;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranAngsuranService
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

            $calculatedData = $this->calculateDetails($financing);

            $remainingPrincipal =
                ($financing->cost_price - $financing->down_payment)
                - $calculatedData['principal_paid'];

            $marginSettlement =
                $calculatedData['repayment_total']
                - $remainingPrincipal;

            Installment::where('financing_id', $installment->financing_id)
                ->where('due_date', '>=', now())
                ->update(['status' => InstallmentPaymentScheduleStatusEnum::PAID->value]);

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
                'principal_amount' => $remainingPrincipal,
                'margin_amount' => $marginSettlement,
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

            $financing->update([
                'status' => FinancingReqStatusEnum::PAID->value,
            ]);

            $data['financing_id'] = $installment->financing_id;
            $data['installment_payment_receipt'] = $transaction->installment_payment_receipt ? asset('storage/' . $transaction->installment_payment_receipt) : null;

            return $data;
        });
    }

    public function getCreatePaymentData(Financing $financing): array
    {
        $financing->load([
            'member.user',
            'financingItem.productType',
            'installment',
        ]);

        $paidStatuses = [
            InstallmentPaymentScheduleStatusEnum::PAID->value,
            InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ];

        $installment = Installment::where('financing_id', $financing->id)
            ->whereNotIn('status', $paidStatuses)
            ->orderBy('installment_no')
            ->first();

        $nextInstallment = Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>', $installment?->installment_no)
            ->orderBy('installment_no')
            ->first();

        $hargaJual     = $financing->cost_price + $financing->margin_amount;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('installment', fn($q) =>
            $q->where('financing_id', $financing->id)
        )->sum('nominal');

        $sisa         = $hargaJual - $totalTerbayar;
        $paymentCount = InstallmentPaymentTransaction::where('installment_id', $installment?->id)->count();

        return [
            'id'                      => $financing->id,
            'transaction_code'        => $financing->financing_transaction_code,
            'product_name'            => $financing->financingItem?->name,
            'product_type'            => $financing->financingItem?->productType?->product_type_name,
            'product_specification'   => $financing->financingItem?->specification,
            'color'                   => '-',
            'qty'                     => $financing->financingItem?->qty,
            'user' => [
                'name'      => $financing->member?->user?->name,
                'user_code' => $financing->member?->user?->user_code,
            ],
            'installment_per_month'   => $installment?->amount ?? 0,
            'remaining_balance'       => max($sisa, 0),
            'next_installment_number' => $installment?->installment_no,
            'current_due_date'        => $installment?->due_date?->format('Y-m-d'),
            'payment_count'           => $paymentCount + 1,
            'next_due_date'           => $nextInstallment?->due_date?->format('Y-m-d'),
            'financing_id'            => $financing->id,
            'installment_id'          => $installment?->id,
        ];
    }

    public function processPayment(array $validated): array
    {
        $financing = Financing::with([
            'member.user',
            'financingItem.productType',
            'installment',
        ])->findOrFail($validated['financing_id']);

        $marginPerMonth    = round($financing->margin_amount / $financing->tenor, 2);
        $principalPerMonth = round($validated['nominal'] - $marginPerMonth, 2);

        $payment = InstallmentPaymentTransaction::create([
            'installment_trans_code' => 'INS' . strtoupper(substr(uniqid(), -7)),
            'payment_method'         => $validated['payment_method'],
            'is_early_repayment'     => false,
            'nominal'                => $validated['nominal'],
            'principal_amount'       => $principalPerMonth,
            'margin_amount'          => $marginPerMonth,
            'payment_date'           => $validated['payment_date'],
            'installment_id'         => $validated['installment_id'],
            'updated_by'             => auth()->id(),
        ]);

        $installment = Installment::findOrFail($validated['installment_id']);
        $paymentDate = Carbon::parse($validated['payment_date']);
        $dueDate     = $installment->due_date;

        $status = $paymentDate->startOfDay()->gt($dueDate->copy()->startOfDay())
            ? InstallmentPaymentScheduleStatusEnum::OVERDUE->value
            : InstallmentPaymentScheduleStatusEnum::PAID->value;

        $installment->update(['status' => $status]);

        $totalTagihan  = ($financing->cost_price - ($financing->down_payment ?? 0)) + $financing->margin_amount;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('installment', fn($q) =>
            $q->where('financing_id', $financing->id)
        )->sum('nominal');

        $sisa = $totalTagihan - $totalTerbayar;

        if ($sisa <= 0) {
            $financing->update(['status' => FinancingReqStatusEnum::PAID->value]);
        }

        $nextInstallment = Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>', $installment->installment_no)
            ->orderBy('installment_no')
            ->first();

        $financing->load('member.user');

        $hargaJual = $totalTagihan;
        return compact('financing', 'payment', 'installment', 'nextInstallment', 'hargaJual', 'sisa');
    }

    public function generateAndStoreReceipt(array $paymentData): ?string
    {
        [
            'financing'       => $financing,
            'payment'         => $payment,
            'installment'     => $installment,
            'nextInstallment' => $nextInstallment,
            'hargaJual'       => $hargaJual,
            'sisa'            => $sisa,
        ] = $paymentData;

        try {
            Carbon::setLocale('id');

            $logoPath = public_path('images/logo/logo-icon.svg');
            $logo = file_exists($logoPath)
                ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
                : '';

            $receipt = [
                'logo'           => $logo,
                'payment_method' => $payment->payment_method,
                'organization'   => [
                    'name'    => 'Koperasi Syariah Berkah',
                    'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                ],
                'petugas'          => auth()->user()->name,
                'tanggal_angsuran' => Carbon::parse($payment->payment_date)->translatedFormat('d F Y'),
                'nomor_pembiayaan' => $financing->financing_transaction_code,
                'no_anggota'       => $financing->member?->user?->user_code,
                'diterima_dari'    => $financing->member?->user?->name,
                'sejumlah_uang'    => $payment->nominal,
                'items'            => [[
                    'no'         => 1,
                    'keterangan' => 'Angsuran ke ' . $installment->installment_no,
                    'jumlah'     => $payment->nominal,
                ]],
                'harga_perolehan' => $financing->cost_price,
                'margin'          => $financing->margin_amount,
                'harga_jual'      => $hargaJual,
                'total_angsuran'  => $payment->nominal,
                'sisa_hutang'     => max($sisa, 0),
                'status'          => max($sisa, 0) <= 0 ? 'Lunas' : 'Belum Lunas',
                'jatuh_tempo'     => $nextInstallment
                    ? $nextInstallment->due_date->translatedFormat('d F Y')
                    : '-',
                'catatan'         => 'Dasar akad yang digunakan adalah akad murabahah yang merupakan kontrak jual beli syariah.',
                'tanggal_cetak'   => now()->translatedFormat('d F Y'),
            ];

            $pdf = Pdf::loadView('exports.financing_payment_receipt', ['receipt' => $receipt])
                ->setPaper('a5', 'landscape')
                ->setOptions(['isRemoteEnabled' => true]);

            $fileName = 'receipts/' . $financing->member->id . '/receipt-' . time() . '.pdf';

            Storage::disk('public')->put($fileName, $pdf->output());

            MemberDoc::create([
                'member_id'      => $financing->member_id,
                'doc_name'       => 'Kwitansi Pembayaran ' . $payment->installment_trans_code,
                'doc_attachment' => $fileName,
            ]);

            $payment->update(['installment_payment_receipt' => $fileName]);

            return $fileName;

        } catch (\Throwable $th) {
            Log::error('PDF generation failed: ' . $th->getMessage());
            return null;
        }
    }

    public function rescheduleInstallments(Financing $financing, string $installmentId, string $newDueDate): void
    {
        $currentInstallment = Installment::findOrFail($installmentId);
        $newDate            = Carbon::parse($newDueDate);

        Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>=', $currentInstallment->installment_no)
            ->orderBy('installment_no')
            ->get()
            ->each(function ($item, $index) use ($newDate) {
                $item->update(['due_date' => $newDate->copy()->addMonths($index)]);
            });
    }
}
