<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class SimpananExport implements FromCollection, WithEvents, ShouldAutoSize
{
    public function __construct(
        protected $transactions,
        protected string $title
    ) {}

    public function collection()
    {
        $rows = collect();

        foreach ($this->transactions as $trx) {
            $rows->push([
                $trx->saving_transaction_code,
                Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                $trx->savingAccount->member->user->user_code
                    . ' - '
                    . $trx->savingAccount->member->user->name,
                $trx->savingAccount->saving_type ?? '-',
                $trx->transaction_type,
                $trx->transaction_type === 'Penarikan'
                    ? -$trx->saving_amount
                    : $trx->saving_amount,
            ]);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                // Judul
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'Koperasi Syariah Berkah');

                $sheet->mergeCells('A2:F2');
                $sheet->setCellValue('A2', $this->title);

                $sheet->mergeCells('A3:F3');
                $sheet->setCellValue('A3', '');

                // Header
                $sheet->setCellValue('A4', 'No Transaksi');
                $sheet->setCellValue('B4', 'Tanggal');
                $sheet->setCellValue('C4', 'Anggota');
                $sheet->setCellValue('D4', 'Produk');
                $sheet->setCellValue('E4', 'Jenis');
                $sheet->setCellValue('F4', 'Nominal');

                // Data
                $row = 6;

                foreach ($this->transactions as $trx) {

                    $sheet->setCellValue(
                        'A'.$row,
                        $trx->saving_transaction_code
                    );

                    $sheet->setCellValue(
                        'B'.$row,
                        Carbon::parse($trx->transaction_date)->format('d/m/Y')
                    );

                    $sheet->setCellValue(
                        'C'.$row,
                        $trx->savingAccount->member->user->user_code
                        . ' - '
                        . $trx->savingAccount->member->user->name
                    );

                    $sheet->setCellValue(
                        'D'.$row,
                        $trx->savingAccount->saving_type ?? '-'
                    );

                    $sheet->setCellValue(
                        'E'.$row,
                        $trx->transaction_type
                    );

                    $sheet->setCellValue(
                        'F'.$row,
                        $trx->transaction_type === 'Penarikan'
                            ? -$trx->saving_amount
                            : $trx->saving_amount
                    );

                    $row++;
                }

                // Style Judul
                $sheet->getStyle('A1:F2')->getFont()->setBold(true);
                $sheet->getStyle('A1:F2')->getAlignment()->setHorizontal('center');

                // Style Header
                $sheet->getStyle('A4:F4')->getFont()->setBold(true);

                // Format Rupiah
                $sheet->getStyle('F5:F'.$row)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Lebar kolom
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(40);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(20);
            },
        ];
    }
}