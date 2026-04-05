<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Koi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class ProfitLossReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $year;
    protected $koiBreakdown;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;

        $sales = Sale::with(['koi:id,code,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $purchases = Purchase::with(['koi:id,code,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $this->koiBreakdown = new Collection();
        $allKoiIds = collect();
        $allKoiIds = $allKoiIds->merge($sales->pluck('koi_id'))->merge($purchases->pluck('koi_id'))->unique();

        foreach ($allKoiIds as $koiId) {
            $koi = Koi::find($koiId);
            $koiSales = $sales->where('koi_id', $koiId);
            $koiPurchases = $purchases->where('koi_id', $koiId);

            $qtyBuy = $koiPurchases->sum('qty');
            $qtySell = $koiSales->sum('qty');
            $totalBuy = $koiPurchases->sum('price_idr');
            $totalSell = $koiSales->sum('sell_idr');
            $profit = $totalSell - $totalBuy;

            $this->koiBreakdown->push((object) [
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'qty_buy' => $qtyBuy,
                'qty_sell' => $qtySell,
                'total_buy' => $totalBuy,
                'total_sell' => $totalSell,
                'profit' => $profit,
            ]);
        }
    }

    public function collection()
    {
        return $this->koiBreakdown;
    }

    public function headings(): array
    {
        return [
            'Koi Code',
            'Koi Name',
            'Qty Beli',
            'Qty Jual',
            'Total Beli (IDR)',
            'Total Jual (IDR)',
            'Profit (IDR)',
        ];
    }

    public function map($row): array
    {
        return [
            $row->koi_code,
            $row->koi_name,
            $row->qty_buy,
            $row->qty_sell,
            number_format($row->total_buy, 0, ',', '.'),
            number_format($row->total_sell, 0, ',', '.'),
            number_format($row->profit, 0, ',', '.'),
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
