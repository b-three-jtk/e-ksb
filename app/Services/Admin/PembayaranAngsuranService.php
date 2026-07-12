<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Models\Akun;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Angsuran;
use App\Models\PembayaranAngsuran;
use App\Models\DokumenAnggota;
use App\Models\RekeningAnggota;
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

            Carbon::setLocale('id');
            $now = now();
            $hari = $now->translatedFormat('l');
            $tanggal = $now->format('d');
            $bulan = $now->translatedFormat('F');
            $tahun = $now->format('Y');

            $strukData = [
                'no_transaksi' => $transCode,
                'hari' => $hari,
                'tanggal' => $tanggal,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'no_anggota' => $pembiayaan->anggota->user->kode_pengguna,
                'nama_anggota' => $pembiayaan->anggota->user->nama,
                'financing_transaction_code' => $pembiayaan->kode_pembiayaan,
                'product_name' => $pembiayaan->objekPembiayaan->nama_barang ?? '-',
                'total_paid_amount' => $calculatedData['total_paid_amount'],
                'metode' => $validatedData['method'],
                'repayment_total' => $calculatedData['repayment_total'],
                'tenor' => $pembiayaan->tenor,
                'satuan_tenor' => $pembiayaan->satuan_tenor ?: 'Bulan',
                'nama_pengurus' => auth()->user()->nama,
                'jabatan_pengurus' => auth()->user()->roles->first()->name ?? 'Pengurus',
                'alamat' => $pembiayaan->anggota->alamat_domisili ?? $pembiayaan->anggota->alamat_ktp ?? '-',
                'harga_perolehan' => $pembiayaan->harga_perolehan,
                'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                'no_telp' => $pembiayaan->anggota->user->no_telp,
                'qimah_ismiyyah' => $pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan,
                'qimah_haliyyah' => $calculatedData['qimah_haliyyah'],
                'logo' => $src,
            ];

            $pdf = Pdf::loadView('exports.repayment_receipt', $strukData);
            $filePath = 'receipts/repayment/' . $transCode . '.pdf';

            Storage::disk('public')->put($filePath, $pdf->output());

            $buktiPembayaranPath = null;
            if (isset($validatedData['bukti_pembayaran'])) {
                $buktiPembayaranPath = $validatedData['bukti_pembayaran']->store('receipts/transfer', 'public');
            }

            $transaction = PembayaranAngsuran::create([
                'kode_transaksi_pembayaran' => $transCode,
                'jumlah_angsuran_dibayar' => $calculatedData['repayment_total'],
                'pokok_dibayar' => $remainingPrincipal,
                'margin_dibayar' => $marginSettlement,
                'metode_pembayaran' => $validatedData['method'],
                'no_rekening' => $validatedData['no_rekening'] ?? null,
                'bukti_pembayaran' => $buktiPembayaranPath,
                'is_pelunasan_lebih_cepat' => true,
                'tgl_pembayaran' => now(),
                'angsuran_id' => $angsuran->id,
                'updated_by' => $userId,
                'struk_pembayaran' => $filePath,
                'status' => 'Menunggu Verifikasi',
            ]);

            Akun::where(
                'nama_akun',
                'Kas'
            )->firstOrFail();

            Akun::where(
                'nama_akun',
                'Piutang Murabahah'
            )->firstOrFail();

            Akun::where(
                'nama_akun',
                'Pendapatan Margin Murabahah'
            )->firstOrFail();

            $pembiayaan->update([
                'status' => FinancingReqStatusEnum::PAID->value,
            ]);

            $data['pembiayaan_id'] = $angsuran->pembiayaan_id;
            $data['struk_pembayaran'] = $transaction->struk_pembayaran ? asset('storage/' . $transaction->struk_pembayaran) : null;

            return $data;
        });
    }

    public function verifyPayment(string $paymentId, string $verifiedById): PembayaranAngsuran
    {
        $payment = PembayaranAngsuran::lockForUpdate()->findOrFail($paymentId);

        if ($payment->status === 'Diverifikasi') {
            throw new \RuntimeException('Pembayaran ini sudah diverifikasi sebelumnya.');
        }

        $payment->update([
            'status'      => 'Diverifikasi',
            'verified_by' => $verifiedById,
            'verified_at' => now(),
        ]);

        return $payment->fresh();
    }

    public function getCreatePaymentData(Pembiayaan $pembiayaan): array
    {
        $pembiayaan->load([
            'anggota.user',
            'anggota.bankAccounts',
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
        $totalTerbayar = PembayaranAngsuran::whereHas('angsuran', fn($q) =>
            $q->where('pembiayaan_id', $pembiayaan->id)
        )->sum('jumlah_angsuran_dibayar');

        $sisa         = $hargaJual - $totalTerbayar;
        $paymentCount = PembayaranAngsuran::where('angsuran_id', $angsuran?->id)->count();

        return [
            'id'                      => $pembiayaan->id,
            'anggota_id'              => $pembiayaan->anggota_id,
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
                'bank_accounts' => $pembiayaan->anggota?->bankAccounts?->map(fn($r) => [
                'no_rekening' => $r->no_rekening,
                'nama_bank'   => $r->nama_bank,
                'atas_nama'   => $r->atas_nama,
            ])->values() ?? [],
            'installment_per_month'   => $angsuran?->nominal_angsuran ?? 0,
            'remaining_balance'       => max($sisa, 0),
            'next_installment_number' => $angsuran?->angsuran_ke,
            'current_due_date'        => $angsuran?->tgl_jatuh_tempo?->format('Y-m-d'),
            'payment_count'           => $paymentCount + 1,
            'next_due_date'           => $nextInstallment?->tgl_jatuh_tempo?->format('Y-m-d'),
            'tanggal_akhir_periode'   => \App\Models\PengaturanUmum::where('key', 'tanggal_akhir_periode')->value('value'),
            'pembiayaan_id'            => $pembiayaan->id,
            'angsuran_id'          => $angsuran?->id,
        ];
    }

    public function generateTransactionCode(): string
    {
        $prefix = 'TPA';
        $yymm   = now()->format('ym');
        $lastNo = PembayaranAngsuran::where('kode_transaksi_pembayaran', 'like', "{$prefix}{$yymm}%")
            ->count();
        $seq    = str_pad((string)($lastNo + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$yymm}{$seq}";
    }

    public function storeRekeningAnggota(array $validated)
    {
        return RekeningAnggota::create([
            'anggota_id'  => $validated['anggota_id'],
            'no_rekening' => $validated['no_rekening'],
            'nama_bank'   => $validated['nama_bank'],
            'atas_nama'   => $validated['atas_nama'],
        ]);
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
            $tenor = $pembiayaan->tenor > 0 ? $pembiayaan->tenor : 1;
            $marginPerMonth    = round($pembiayaan->margin_keuntungan / $tenor, 2);
            $principalPerMonth = round($validated['jumlah_angsuran_dibayar'] - $marginPerMonth, 2);
        }

        $buktiPembayaranPath = null;
        if (
            ($validated['metode_pembayaran'] ?? null) === 'Non-Tunai'
            && isset($validated['bukti_pembayaran'])
        ) {
            $buktiPembayaranPath = $validated['bukti_pembayaran']->store('bukti_pembayaran', 'public');
        }

        $payment = PembayaranAngsuran::create([
            'kode_transaksi_pembayaran' => $this->generateTransactionCode(),
            'metode_pembayaran'         => $validated['metode_pembayaran'],
            'is_pelunasan_lebih_cepat'     => false,
            'jumlah_angsuran_dibayar'                => $validated['jumlah_angsuran_dibayar'],
            'pokok_dibayar'       => $principalPerMonth,
            'margin_dibayar'          => $marginPerMonth,
            'tgl_pembayaran'           => $validated['tgl_pembayaran'],
            'angsuran_id'         => $validated['angsuran_id'],
            'no_rekening'               => $validated['no_rekening'] ?? null,
            'bukti_pembayaran'          => $buktiPembayaranPath,
            'updated_by'             => auth()->id(),
            'status'                 => 'Menunggu Verifikasi',
        ]);

            $angsuran = Angsuran::findOrFail($validated['angsuran_id']);
            $paymentDate = Carbon::parse($validated['tgl_pembayaran']);
            $dueDate     = $angsuran->tgl_jatuh_tempo;

            $status = $paymentDate->startOfDay()->gt($dueDate->copy()->startOfDay())
                ? InstallmentPaymentScheduleStatusEnum::OVERDUE->value
                : InstallmentPaymentScheduleStatusEnum::PAID->value;

            $angsuran->update(['status' => $status]);

        $totalTagihan  = ($pembiayaan->harga_perolehan - ($pembiayaan->uang_muka ?? 0)) + $pembiayaan->margin_keuntungan;
        $totalTerbayar = PembayaranAngsuran::whereHas('angsuran', fn($q) =>
            $q->where('pembiayaan_id', $pembiayaan->id)
        )->sum('jumlah_angsuran_dibayar');

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
                'tanggal_angsuran' => Carbon::parse($payment->tgl_pembayaran)->translatedFormat('d F Y'),
                'nomor_pembiayaan' => $pembiayaan->kode_pembiayaan,
                'no_anggota'       => $pembiayaan->anggota?->user?->kode_pengguna,
                'diterima_dari'    => $pembiayaan->anggota?->user?->nama,
                'sejumlah_uang'    => $payment->jumlah_angsuran_dibayar,
                'items'            => [[
                    'no'         => 1,
                    'keterangan' => 'Angsuran ke ' . $angsuran->angsuran_ke,
                    'jumlah'     => $payment->jumlah_angsuran_dibayar,
                ]],
                'harga_perolehan' => $pembiayaan->harga_perolehan,
                'margin'          => $payment->margin_dibayar,
                'harga_jual'      => $hargaJual,
                'total_angsuran'  => $payment->jumlah_angsuran_dibayar,
                'sisa_hutang'     => max($sisa, 0),
                'status'          => max($sisa, 0) <= 0 ? 'Lunas' : 'Belum Lunas',
                'jatuh_tempo'     => $nextInstallment
                    ? $nextInstallment->tgl_jatuh_tempo->translatedFormat('d F Y')
                    : '-',
                'catatan'         => 'Dasar akad yang digunakan adalah akad murabahah yang merupakan kontrak jual beli syariah.',
                'tanggal_cetak'   => now()->translatedFormat('d F Y'),
            ];

        $isLunas = $pembiayaan->status === 'Lunas';

            if ($isLunas) {
                $now = now();
                $hari = $now->translatedFormat('l');
                $tanggal = $now->format('d');
                $bulan = $now->translatedFormat('F');
                $tahun = $now->format('Y');

                $strukData = [
                    'no_transaksi' => $payment->kode_transaksi_pembayaran,
                    'hari' => $hari,
                    'tanggal' => $tanggal,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'no_anggota' => $pembiayaan->anggota->user->kode_pengguna,
                    'nama_anggota' => $pembiayaan->anggota->user->nama,
                    'financing_transaction_code' => $pembiayaan->kode_pembiayaan,
                    'product_name' => $pembiayaan->financingItem->nama ?? '-',
                    'total_paid_amount' => $hargaJual,
                    'metode' => $payment->metode_pembayaran,
                    'repayment_total' => $payment->jumlah_angsuran_dibayar,
                    'tenor' => $pembiayaan->tenor ?? 0,
                    'nama_pengurus' => auth()->user()->nama,
                    'jabatan_pengurus' => auth()->user()->roles->first()->nama ?? 'Pengurus',
                    'alamat' => $pembiayaan->anggota->alamat_domisili ?? $pembiayaan->anggota->residential_address ?? '-',
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                    'no_telp' => $pembiayaan->anggota->user->no_telp,
                    'qimah_ismiyyah' => $hargaJual,
                    'qimah_haliyyah' => $hargaJual,
                    'logo' => $logo,
                ];

                $pdf = Pdf::loadView('exports.repayment_receipt', $strukData);
            } else {
                $receipt = [
                    'logo'           => $logo,
                    'metode_pembayaran' => $payment->metode_pembayaran,
                    'organization'   => [
                        'name'    => 'Koperasi Syariah Berkah',
                        'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                    ],
                    'petugas'          => auth()->user()->nama,
                    'tanggal_angsuran' => Carbon::parse($payment->tgl_pembayaran)->translatedFormat('d F Y'),
                    'nomor_pembiayaan' => $pembiayaan->kode_pembiayaan,
                    'no_anggota'       => $pembiayaan->anggota?->user?->kode_pengguna,
                    'diterima_dari'    => $pembiayaan->anggota?->user?->nama,
                    'sejumlah_uang'    => $payment->jumlah_angsuran_dibayar,
                    'items'            => [[
                        'no'         => 1,
                        'keterangan' => 'Angsuran ke ' . $angsuran->angsuran_ke,
                        'jumlah'     => $payment->jumlah_angsuran_dibayar,
                    ]],
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin'          => $payment->margin_dibayar,
                    'harga_jual'      => $hargaJual,
                    'total_angsuran'  => $payment->jumlah_angsuran_dibayar,
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
            }

            $fileName = 'receipts/' . $pembiayaan->anggota->id . '/receipt-' . time() . '.pdf';

            Storage::disk('public')->put($fileName, $pdf->output());

            DokumenAnggota::create([
                'anggota_id'      => $pembiayaan->anggota_id,
                'nama_dokumen'       => 'Kwitansi Pembayaran ' . $payment->kode_transaksi_pembayaran,
                'lampiran_dokumen' => $fileName,
            ]);

            $payment->update(['struk_pembayaran' => $fileName]);

            return $fileName;

        } catch (\Throwable $th) {
            Log::error('PDF generation failed: ' . $th->getMessage());
            return null;
        }
    }

    public function rescheduleInstallments(Pembiayaan $pembiayaan, string $installmentId, string $newDueDate): void
    {
        $installment = Angsuran::where('pembiayaan_id', $pembiayaan->id)
            ->findOrFail($installmentId);

        $installment->update([
            'tgl_jatuh_tempo' => Carbon::parse($newDueDate),
        ]);
    }
}
