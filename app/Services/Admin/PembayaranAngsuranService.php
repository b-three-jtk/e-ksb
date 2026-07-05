<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Models\Account;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Angsuran;
use App\Models\InstallmentPaymentTransaction;
use App\Models\MemberDoc;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranAngsuranService
{
    public function calculateDetails(Pembiayaan $pembiayaan): array
    {
        $tenor = $pembiayaan->tenor == 0 ? 1 : $pembiayaan->tenor;

        $basePrincipal = $pembiayaan->harga_perolehan - $pembiayaan->uang_muka;
        $marginAmount = $pembiayaan->margin_keuntungan;
        $totalPaidInstallments = $pembiayaan->angsuran->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();

        // menggunakan metode margin Flat
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
            'pembiayaan'               => $pembiayaan,
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
            $angsuran = Angsuran::with('pembiayaan.anggota.user', 'pembiayaan.objekPembiayaan')
                ->findOrFail($validatedData['angsuran_id']);

            $pembiayaan = $angsuran->pembiayaan;

            $calculatedData = $this->calculateDetails($pembiayaan);

            $remainingPrincipal =
                ($pembiayaan->harga_perolehan - $pembiayaan->uang_muka)
                - $calculatedData['principal_paid'];

            $marginSettlement =
                $calculatedData['repayment_total']
                - $remainingPrincipal;

            Angsuran::where('pembiayaan_id', $angsuran->pembiayaan_id)
                ->where('tgl_jatuh_tempo', '>=', now())
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
                'no_anggota' => $pembiayaan->anggota->user->kode_pengguna,
                'nama_anggota' => $pembiayaan->anggota->user->nama,
                'no_telp' => $pembiayaan->anggota->user->no_telp,
                'kode_pembiayaan' => $pembiayaan->kode_pembiayaan,
                'product_name' => $pembiayaan->objekPembiayaan->nama_barang ?? '-',
                'total_paid_amount' => $calculatedData['total_paid_amount'],
                'metode' => $validatedData['method'],
                'repayment_total' => $calculatedData['repayment_total'],
                'pengurus' => auth()->user()->nama,
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
                'margin_keuntungan' => $marginSettlement,
                'metode_pembayaran' => $validatedData['method'],
                'is_early_repayment' => true,
                'payment_date' => now(),
                'angsuran_id' => $angsuran->id,
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

            $pembiayaan->update([
                'status' => FinancingReqStatusEnum::PAID->value,
            ]);

            $data['pembiayaan_id'] = $angsuran->pembiayaan_id;
            $data['installment_payment_receipt'] = $transaction->installment_payment_receipt ? asset('storage/' . $transaction->installment_payment_receipt) : null;

            return $data;
        });
    }

    public function getCreatePaymentData(Pembiayaan $pembiayaan): array
    {
        $pembiayaan->load([
            'anggota.user',
            'objekPembiayaan.jenisBarang',
            'angsuran',
        ]);

        $paidStatuses = [
            InstallmentPaymentScheduleStatusEnum::PAID->value,
            InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ];

        $angsuran = Angsuran::where('pembiayaan_id', $pembiayaan->id)
            ->whereNotIn('status', $paidStatuses)
            ->orderBy('angsuran_ke')
            ->first();

        $nextInstallment = Angsuran::where('pembiayaan_id', $pembiayaan->id)
            ->where('angsuran_ke', '>', $angsuran?->angsuran_ke)
            ->orderBy('angsuran_ke')
            ->first();

        $hargaJual     = $pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('angsuran', fn($q) =>
            $q->where('pembiayaan_id', $pembiayaan->id)
        )->sum('nominal');

        $sisa         = $hargaJual - $totalTerbayar;
        $paymentCount = InstallmentPaymentTransaction::where('angsuran_id', $angsuran?->id)->count();

        return [
            'id'                      => $pembiayaan->id,
            'transaction_code'        => $pembiayaan->kode_pembiayaan,
            'product_name'            => $pembiayaan->objekPembiayaan?->nama_barang,
            'jenis_barang'            => $pembiayaan->objekPembiayaan?->jenisBarang?->nama_jenis_barang,
            'product_spesifikasi_barang'   => $pembiayaan->objekPembiayaan?->spesifikasi_barang,
            'color'                   => '-',
            'kuantitas'                     => $pembiayaan->objekPembiayaan?->kuantitas,
            'user' => [
                'nama'      => $pembiayaan->anggota?->user?->nama,
                'kode_pengguna' => $pembiayaan->anggota?->user?->kode_pengguna,
            ],
            'installment_per_month'   => $angsuran?->nominal_angsuran ?? 0,
            'remaining_balance'       => max($sisa, 0),
            'next_installment_number' => $angsuran?->angsuran_ke,
            'current_due_date'        => $angsuran?->tgl_jatuh_tempo?->format('Y-m-d'),
            'payment_count'           => $paymentCount + 1,
            'next_due_date'           => $nextInstallment?->tgl_jatuh_tempo?->format('Y-m-d'),
            'pembiayaan_id'            => $pembiayaan->id,
            'angsuran_id'          => $angsuran?->id,
        ];
    }

    public function generateTransactionCode(): string
    {
        $prefix = 'TPA';
        $yymm   = now()->format('ym');
        $lastNo = InstallmentPaymentTransaction::where('installment_trans_code', 'like', "{$prefix}{$yymm}%")
            ->count();
        $seq    = str_pad((string)($lastNo + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$yymm}{$seq}";
    }

    public function processPayment(array $validated): array
    {
        $pembiayaan = Pembiayaan::with([
            'anggota.user',
            'objekPembiayaan.jenisBarang',
            'angsuran',
        ])->findOrFail($validated['pembiayaan_id']);

        if ($pembiayaan->metode_pembayaran === \App\Enums\FinancingPaymentMethodEnum::TANGGUH->value) {
            $principalPerMonth = $pembiayaan->harga_perolehan - ($pembiayaan->uang_muka ?? 0);
            $marginPerMonth    = $pembiayaan->margin_keuntungan;
        } else {
            $marginPerMonth    = round($pembiayaan->margin_keuntungan / $pembiayaan->tenor, 2);
            $principalPerMonth = round($validated['nominal'] - $marginPerMonth, 2);
        }

        $payment = InstallmentPaymentTransaction::create([
            'installment_trans_code' => $this->generateTransactionCode(),
            'metode_pembayaran'         => $validated['metode_pembayaran'],
            'is_early_repayment'     => false,
            'nominal'                => $validated['nominal'],
            'principal_amount'       => $principalPerMonth,
            'margin_keuntungan'          => $marginPerMonth,
            'payment_date'           => $validated['payment_date'],
            'angsuran_id'         => $validated['angsuran_id'],
            'updated_by'             => auth()->id(),
        ]);

            $angsuran = Angsuran::findOrFail($validated['angsuran_id']);
            $paymentDate = Carbon::parse($validated['payment_date']);
            $dueDate     = $angsuran->tgl_jatuh_tempo;

            $status = $paymentDate->startOfDay()->gt($dueDate->copy()->startOfDay())
                ? InstallmentPaymentScheduleStatusEnum::OVERDUE->value
                : InstallmentPaymentScheduleStatusEnum::PAID->value;

            $angsuran->update(['status' => $status]);

        $totalTagihan  = ($pembiayaan->harga_perolehan - ($pembiayaan->uang_muka ?? 0)) + $pembiayaan->margin_keuntungan;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('angsuran', fn($q) =>
            $q->where('pembiayaan_id', $pembiayaan->id)
        )->sum('nominal');

        $sisa = $totalTagihan - $totalTerbayar;

        if ($sisa <= 0) {
            $pembiayaan->update(['status' => FinancingReqStatusEnum::PAID->value]);
        }

            $nextInstallment = Angsuran::where('pembiayaan_id', $pembiayaan->id)
                ->where('angsuran_ke', '>', $angsuran->angsuran_ke)
                ->orderBy('angsuran_ke')
                ->first();

            $pembiayaan->load('anggota.user');

        $hargaJual = $totalTagihan;
        return compact('pembiayaan', 'payment', 'angsuran', 'nextInstallment', 'hargaJual', 'sisa');
    }

    public function generateAndStoreReceipt(array $paymentData): ?string
    {
        [
            'pembiayaan'       => $pembiayaan,
            'payment'         => $payment,
            'angsuran'     => $angsuran,
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
                'metode_pembayaran' => $payment->metode_pembayaran,
                'organization'   => [
                    'name'    => 'Koperasi Syariah Berkah',
                    'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                ],
                'petugas'          => auth()->user()->nama,
                'tanggal_angsuran' => Carbon::parse($payment->payment_date)->translatedFormat('d F Y'),
                'nomor_pembiayaan' => $pembiayaan->kode_pembiayaan,
                'no_anggota'       => $pembiayaan->anggota?->user?->kode_pengguna,
                'diterima_dari'    => $pembiayaan->anggota?->user?->nama,
                'sejumlah_uang'    => $payment->nominal,
                'items'            => [[
                    'no'         => 1,
                    'keterangan' => 'Angsuran ke ' . $angsuran->angsuran_ke,
                    'jumlah'     => $payment->nominal,
                ]],
                'harga_perolehan' => $pembiayaan->harga_perolehan,
                'margin'          => $pembiayaan->margin_keuntungan,
                'harga_jual'      => $hargaJual,
                'total_angsuran'  => $payment->nominal,
                'sisa_hutang'     => max($sisa, 0),
                'status'          => max($sisa, 0) <= 0 ? 'Lunas' : 'Belum Lunas',
                'jatuh_tempo'     => $nextInstallment
                    ? $nextInstallment->tgl_jatuh_tempo->translatedFormat('d F Y')
                    : '-',
                'catatan'         => 'Dasar akad yang digunakan adalah akad murabahah yang merupakan kontrak jual beli syariah.',
                'tanggal_cetak'   => now()->translatedFormat('d F Y'),
            ];

            $pdf = Pdf::loadView('exports.financing_payment_receipt', ['receipt' => $receipt])
                ->setPaper('a5', 'landscape')
                ->setOptions(['isRemoteEnabled' => true]);

            $fileName = 'receipts/' . $pembiayaan->anggota->id . '/receipt-' . time() . '.pdf';

            Storage::disk('public')->put($fileName, $pdf->output());

            MemberDoc::create([
                'anggota_id'      => $pembiayaan->anggota_id,
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

    public function rescheduleInstallments(Pembiayaan $pembiayaan, string $installmentId, string $newDueDate): void
    {
        $currentInstallment = Angsuran::findOrFail($installmentId);
        $newDate            = Carbon::parse($newDueDate);

        Angsuran::where('pembiayaan_id', $pembiayaan->id)
            ->where('angsuran_ke', '>=', $currentInstallment->angsuran_ke)
            ->orderBy('angsuran_ke')
            ->get()
            ->each(function ($item, $index) use ($newDate) {
                $item->update(['tgl_jatuh_tempo' => $newDate->copy()->addMonths($index)]);
            });
    }
}
