<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanArusKasExport implements FromCollection, WithStyles, WithColumnWidths
{
    public function __construct(
        protected array $report,
        protected string $periode
    ) {}

    public function collection()
    {
        $rows = collect();

        $rows->push(['KOPERASI SYARIAH BERKAH']);
        $rows->push(['LAPORAN ARUS KAS']);
        $rows->push(["Untuk Periode {$this->periode}"]);
        $rows->push([]);

        // OPERASI
        $rows->push(['ARUS KAS DARI AKTIVITAS OPERASI', '']);

        foreach ($this->report['operating']['items'] as $item) {
            $rows->push([
                $item['description'],
                $item['amount']
            ]);
        }

        $rows->push([
            'Kas Bersih Aktivitas Operasi',
            $this->report['operating']['net']
        ]);

        $rows->push([]);

        // INVESTASI
        $rows->push(['ARUS KAS DARI AKTIVITAS INVESTASI', '']);

        if (count($this->report['investing']['items'])) {

            foreach ($this->report['investing']['items'] as $item) {
                $rows->push([
                    $item['description'],
                    $item['amount']
                ]);
            }

        } else {

            $rows->push([
                'Tidak ada transaksi investasi',
                0
            ]);

        }

        $rows->push([
            'Kas Bersih Aktivitas Investasi',
            $this->report['investing']['net']
        ]);

        $rows->push([]);

        // PENDANAAN
        $rows->push(['ARUS KAS DARI AKTIVITAS PENDANAAN', '']);

        foreach ($this->report['financing']['items'] as $item) {
            $rows->push([
                $item['description'],
                $item['amount']
            ]);
        }

        $rows->push([
            'Kas Bersih Aktivitas Pendanaan',
            $this->report['financing']['net']
        ]);

        $rows->push([]);
        $rows->push([
            'KENAIKAN (PENURUNAN) KAS',
            $this->report['net_cash']
        ]);

        $rows->push([
            'Kas Awal Periode',
            $this->report['opening_balance']
        ]);

        $rows->push([
            'Kas Akhir Periode',
            $this->report['closing_balance']
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // merge title
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A3:B3');

        $sheet->getStyle('A1:A3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A1:A3')->getFont()
            ->setBold(true);

        $green = 'D9EAD3';

        foreach ($sheet->getRowIterator() as $row) {

            $r = $row->getRowIndex();

            $value = $sheet->getCell("A$r")->getValue();

            if (str_contains((string)$value, 'ARUS KAS DARI AKTIVITAS')) {

                $sheet->mergeCells("A$r:B$r");

                $sheet->getStyle("A$r")->applyFromArray([
                    'fill'=>[
                        'fillType'=>Fill::FILL_SOLID,
                        'color'=>['rgb'=>$green],
                    ],
                    'font'=>[
                        'bold'=>true,
                    ],
                ]);
            }

            if (
                str_contains((string)$value,'Kas Bersih') ||
                str_contains((string)$value,'KENAIKAN') ||
                str_contains((string)$value,'Kas Awal') ||
                str_contains((string)$value,'Kas Akhir')
            ){

                $sheet->getStyle("A$r:B$r")->getFont()->setBold(true);

                $sheet->getStyle("A$r:B$r")->applyFromArray([
                    'fill'=>[
                        'fillType'=>Fill::FILL_SOLID,
                        'color'=>['rgb'=>'F3F3F3']
                    ]
                ]);
            }
        }

        $sheet->getStyle("A1:B$lastRow")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B5:B$lastRow")
            ->getNumberFormat()
            ->setFormatCode('"Rp" #,##0;[Red]-"Rp" #,##0');

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A'=>60,
            'B'=>22,
        ];
    }
}