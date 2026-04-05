<?php

namespace App\Exports;

use App\Models\Koi;
use App\Models\Stock;
use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $year;
    protected $stocks;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();
        $this->stocks = new Collection();

        foreach ($kois as $koi) {
            $movements = StockMovement::where('koi_id', $koi->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $totalIn = $movements->where('type', 'in')->sum('qty');
            $totalOut = $movements->where('type', 'out')->sum('qty');
            $currentStock = Stock::where('koi_id', $koi->id)->first()?->qty ?? 0;
            $openingStock = $currentStock - $totalIn + $totalOut;

            $this->stocks->push((object) [
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'opening_stock' => max(0, $openingStock),
                'stock_in' => $totalIn,
                'stock_out' => $totalOut,
                'closing_stock' => $currentStock,
            ]);
        }
    }

    public function collection()
    {
        return $this->stocks;
    }

    public function headings(): array
    {
        return [
            'Koi Code',
            'Koi Name',
            'Stok Awal',
            'Stok Masuk',
            'Stok Keluar',
            'Stok Akhir',
        ];
    }

    public function map($row): array
    {
        return [
            $row->koi_code,
            $row->koi_name,
            $row->opening_stock,
            $row->stock_in,
            $row->stock_out,
            $row->closing_stock,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
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
            $sheet->getStyle("A2:F{$totalRows}")->applyFromArray([
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
