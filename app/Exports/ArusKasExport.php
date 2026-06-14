<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class ArusKasExport implements FromCollection, WithStyles, WithColumnWidths
{
    public function __construct(
        protected $rows,
        protected string $periode
    ) {}

    public function collection()
    {
        return collect([
            ['Koperasi Syariah Berkah'],
            ['Laporan Arus Kas'],
            ["Periode : {$this->periode}"],
            [],
            ['Tanggal', 'Akun', 'Jenis Akun', 'Debit', 'Kredit'],
        ])->concat($this->rows);
    }

    public function styles(Worksheet $sheet)
    {
        // Merge judul
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');

        // Judul rata tengah
        $sheet->getStyle('A1:A3')
            ->getAlignment()
            ->setHorizontal('center');

        // Bold
        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
        $sheet->getStyle('B1:B4')->getFont()->setBold(true);
        $sheet->getStyle('C1:C4')->getFont()->setBold(true);
        $sheet->getStyle('D1:D4')->getFont()->setBold(true);
        $sheet->getStyle('E1:E4')->getFont()->setBold(true);

        // Header tabel
        $sheet->getStyle('A4:E4')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'D9EAD3'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 20,
            'D' => 18,
            'E' => 18,
        ];
    }
}