<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InvoiceExport implements WithTitle, WithEvents
{
    use RegistersEventListeners;

    private string $invoiceNumber;
    private $kois;
    private string $customerName;
    private float $grandTotal;
    private float $totalFee;

    // A=margin, B=No, C=Foto, D=Nama Ikan, E=Kode/Tgl, F=Qty, G=JPY, H=IDR, I=Total
    private const COL_WIDTHS = [
        'A' => 2,
        'B' => 5,
        'C' => 14,
        'D' => 26,
        'E' => 16,
        'F' => 5,
        'G' => 15,
        'H' => 17,
        'I' => 17,
    ];

    private const IMG_HEIGHT = 80;

    public function __construct(string $invoiceNumber, $kois, string $customerName, float $grandTotal, float $totalFee)
    {
        $this->invoiceNumber = $invoiceNumber;
        $this->kois          = $kois;
        $this->customerName  = $customerName;
        $this->grandTotal    = $grandTotal;
        $this->totalFee      = $totalFee;
    }

    public function title(): string
    {
        return 'Invoice ' . $this->invoiceNumber;
    }

    public static function afterSheet(AfterSheet $event): void
    {
        $ws     = $event->sheet->getDelegate();
        $export = $event->getConcernable();
        $export->build($ws);
    }

    public function build(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        foreach (self::COL_WIDTHS as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }

        // ── Row 1: Header ──────────────────────────────────────────────
        $ws->getRowDimension(1)->setRowHeight(55);
        $ws->mergeCells('B1:F1');
        $ws->mergeCells('G1:I1');

        $ws->setCellValue('B1', 'JAPAN DIRECT KOI CENTER');
        $ws->setCellValue('G1', 'INVOICE');

        $this->style($ws, 'A1:I1', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        ]);
        $this->style($ws, 'B1:F1', [
            'font'      => ['bold' => true, 'size' => 17, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->style($ws, 'G1:I1', [
            'font'      => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $this->addImage($ws, 'B1', public_path('JD-KOI.PNG'), 48, 4, 4);

        // ── Row 2: Alamat + No. Invoice ────────────────────────────────
        $ws->getRowDimension(2)->setRowHeight(20);
        $ws->mergeCells('B2:F2');
        $ws->mergeCells('G2:I2');

        $ws->setCellValue('B2', 'Citeko, Cisarua, Puncak  ·  +6287868882200');
        $ws->setCellValue('G2', $this->invoiceNumber);

        $this->style($ws, 'A2:I2', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        ]);
        $this->style($ws, 'B2:F2', [
            'font'      => ['size' => 9, 'color' => ['rgb' => 'AAAAAA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->style($ws, 'G2:I2', [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Row 3: Garis merah ─────────────────────────────────────────
        $ws->getRowDimension(3)->setRowHeight(4);
        $ws->mergeCells('B3:I3');
        $this->style($ws, 'B3:I3', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
        ]);

        // ── Row 4: Invoice To + Tanggal ────────────────────────────────
        $ws->getRowDimension(4)->setRowHeight(22);
        $ws->mergeCells('B4:F4');
        $ws->mergeCells('G4:I4');

        $ws->setCellValue('B4', 'Invoice to: ' . $this->customerName);
        $ws->setCellValue('G4', now()->format('d F Y'));

        $this->style($ws, 'B4:F4', [
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->style($ws, 'G4:I4', [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'C0392B'], 'underline' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Row 5: Spacer ──────────────────────────────────────────────
        $ws->getRowDimension(5)->setRowHeight(6);

        // ── Row 6: Header tabel ────────────────────────────────────────
        $ws->getRowDimension(6)->setRowHeight(18);
        $ws->setCellValue('B6', 'No.');
        $ws->setCellValue('C6', 'Foto');
        $ws->setCellValue('D6', 'Nama Ikan');
        $ws->setCellValue('E6', 'Kode / Tanggal');
        $ws->setCellValue('F6', 'Qty');
        $ws->setCellValue('G6', 'Harga JPY');
        $ws->setCellValue('H6', 'Harga IDR');
        $ws->setCellValue('I6', 'Total IDR');

        $this->style($ws, 'B6:I6', [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);

        // ── Rows 7+: Data koi ──────────────────────────────────────────
        $row = 7;
        foreach ($this->kois as $index => $koi) {
            $qty      = max(1, intval($koi->sale_qty ?? 1));
            $unitJpy  = floatval($koi->unit_price ?? 0);
            $kurs     = floatval($koi->kurs ?? 0);
            $unitIdr  = $kurs > 0 && $unitJpy > 0
                ? round($unitJpy * $kurs)
                : round(floatval($koi->sell_idr ?? 0) / $qty);
            $totalIdr = $unitIdr * $qty;
            $fee      = floatval($koi->fee ?? 0);
            $feePct   = $totalIdr > 0 && $fee > 0 ? round($fee / $totalIdr * 100) : 0;

            $tanggal = '';
            if ($koi->azukari_period) {
                $tanggal = $koi->azukari_period;
            } elseif ($koi->sale_tanggal) {
                $tanggal = \Carbon\Carbon::parse($koi->sale_tanggal)->format('d M Y');
            }

            $bg = $index % 2 === 0 ? 'FFFFFF' : 'F5F5F5';
            $ws->getRowDimension($row)->setRowHeight(self::IMG_HEIGHT * 0.75);

            $ws->setCellValue('B' . $row, $index + 1);
            $ws->setCellValue('D' . $row, $koi->name . ($feePct > 0 ? "\n[Fee {$feePct}%]" : ''));
            $ws->setCellValue('E' . $row, $koi->code . ($tanggal ? "\n" . $tanggal : ''));
            $ws->setCellValue('F' . $row, $qty);
            $ws->setCellValue('G' . $row, $unitJpy > 0 ? '¥' . number_format($unitJpy) : '-');
            $ws->setCellValue('H' . $row, $unitIdr > 0 ? number_format($unitIdr, 0, ',', '.') : '-');
            $ws->setCellValue('I' . $row, $totalIdr > 0 ? number_format($totalIdr, 0, ',', '.') : '-');

            $this->style($ws, 'B' . $row . ':I' . $row, [
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'font'      => ['size' => 10],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $this->style($ws, 'B' . $row, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['color' => ['rgb' => '888888']],
            ]);
            $this->style($ws, 'F' . $row, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $this->style($ws, 'G' . $row . ':I' . $row, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $this->style($ws, 'I' . $row, [
                'font' => ['bold' => true],
            ]);

            // Foto koi
            $firstImage = $koi->images->sortBy('sort_order')->first();
            if ($firstImage && $firstImage->image_path) {
                $imgPath = public_path('storage/' . $firstImage->image_path);
                if (file_exists($imgPath)) {
                    $this->addImage($ws, 'C' . $row, $imgPath, self::IMG_HEIGHT, 2, 2);
                }
            }

            $row++;
        }

        // ── Spacer ─────────────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(8);
        $row++;

        // ── Subtotal ───────────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(18);
        $ws->mergeCells('B' . $row . ':F' . $row);
        $ws->mergeCells('G' . $row . ':H' . $row);
        $ws->setCellValue('B' . $row, 'Thank you for your purchase!');
        $ws->setCellValue('G' . $row, 'Subtotal');
        $ws->setCellValue('I' . $row, 'IDR ' . number_format($this->grandTotal, 0, ',', '.'));
        $this->style($ws, 'B' . $row . ':F' . $row, [
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->style($ws, 'G' . $row . ':I' . $row, [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']],
            'font'      => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $row++;

        // ── Total Fee ─────────────────────────────────────────────────
        if ($this->totalFee > 0) {
            $ws->getRowDimension($row)->setRowHeight(18);
            $ws->mergeCells('G' . $row . ':H' . $row);
            $ws->setCellValue('G' . $row, 'Total Fee');
            $ws->setCellValue('I' . $row, 'IDR ' . number_format($this->totalFee, 0, ',', '.'));
            $this->style($ws, 'G' . $row . ':I' . $row, [
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']],
                'font'      => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $row++;
        }

        // ── Grand Total ───────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(22);
        $ws->mergeCells('G' . $row . ':H' . $row);
        $ws->setCellValue('G' . $row, 'TOTAL');
        $ws->setCellValue('I' . $row, 'IDR ' . number_format($this->grandTotal, 0, ',', '.'));
        $this->style($ws, 'G' . $row . ':I' . $row, [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $row++;

        // ── Spacer ─────────────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(10);
        $row++;

        // ── Payment Info ───────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(16);
        $ws->mergeCells('C' . $row . ':I' . $row);
        $ws->setCellValue('C' . $row, 'Payment Info');
        $this->style($ws, 'C' . $row, [
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '888888']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $row++;

        foreach ([
            'Name'    => 'Kurniadi Prakasa Pieth',
            'Account' => 'BCA / Mandiri / GoPay / OVO',
            'No. Rek' => 'BCA: 2533 2222 34 | Mandiri: 1690009 44789',
        ] as $label => $value) {
            $ws->getRowDimension($row)->setRowHeight(16);
            $ws->setCellValue('C' . $row, $label);
            $ws->mergeCells('D' . $row . ':I' . $row);
            $ws->setCellValue('D' . $row, ': ' . $value);
            $this->style($ws, 'C' . $row, [
                'font'      => ['size' => 9, 'color' => ['rgb' => '888888']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $this->style($ws, 'D' . $row . ':I' . $row, [
                'font'      => ['bold' => true, 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $row++;
        }

        // ── Print settings ─────────────────────────────────────────────
        $ws->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);
    }

    private function addImage(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws,
        string $coordinate,
        string $path,
        int $height,
        int $offsetX = 2,
        int $offsetY = 2
    ): void {
        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setHeight($height);
        $drawing->setCoordinates($coordinate);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY($offsetY);
        $drawing->setWorksheet($ws);
    }

    private function style(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws, string $range, array $style): void
    {
        $ws->getStyle($range)->applyFromArray($style);
    }
}
