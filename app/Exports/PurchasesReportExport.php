<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PurchasesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return Purchase::with(['koi:id,code,name'])
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->orderBy('date', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Koi Code',
            'Koi Name',
            'Qty',
            'Harga JPY',
            'Harga IDR',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date->format('Y-m-d'),
            $row->koi?->code ?? '-',
            $row->koi?->name ?? '-',
            $row->qty,
            number_format($row->price_jpy, 0, ',', '.'),
            number_format($row->price_idr, 0, ',', '.'),
            $row->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '366092'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $totalRows = $sheet->getHighestRow();
        if ($totalRows > 1) {
            $sheet->getStyle("A2:G{$totalRows}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);
        }

        return [];
    }
}
