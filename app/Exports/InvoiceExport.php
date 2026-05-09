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
    private \Illuminate\Support\Collection $kois;
    private string $customerName;
    private float $grandTotal;
    private float $totalFee;
    private bool $isAdmin;

    // A=margin, B=No, C=Foto, D=Nama Ikan, E=Kode/Tgl, F=Qty, G=JPY, H=IDR, I=Total
    private const COL_WIDTHS = [
        'A' => 2,
        'B' => 5,
        'C' => 16,
        'D' => 30,
        'E' => 18,
        'F' => 6,
        'G' => 16,
        'H' => 18,
        'I' => 18,
    ];

    // px → pt conversion (1px ≈ 0.75pt)
    private const IMG_PX     = 90;
    private const ROW_HEIGHT = 68; // pt ≈ 90px

    public function __construct(string $invoiceNumber, \Illuminate\Support\Collection $kois, string $customerName, float $grandTotal, float $totalFee, float $totalShipping = 0, bool $isAdmin = true)
    {
        $this->invoiceNumber = $invoiceNumber;
        $this->kois          = $kois;
        $this->customerName  = $customerName;
        $this->grandTotal    = $grandTotal;
        $this->totalFee      = $totalFee;
        $this->isAdmin       = $isAdmin;
    }

    public function title(): string
    {
        return 'Invoice ' . $this->invoiceNumber;
    }

    public static function afterSheet(AfterSheet $event): void
    {
        $event->getConcernable()->build($event->sheet->getDelegate());
    }

    public function build(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        // ── Column widths ──────────────────────────────────────────────
        foreach (self::COL_WIDTHS as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }

        // ── Row 1: Logo + Nama Perusahaan ─────────────────────────────
        $ws->getRowDimension(1)->setRowHeight(46);
        $ws->mergeCells('D1:G1');
        $ws->mergeCells('H1:I1');
        $ws->setCellValue('D1', 'JAPAN DIRECT KOI CENTER');
        $ws->setCellValue('H1', 'INVOICE');

        // Kolom A putih (tidak ada fill), background hitam mulai dari B
        $this->style($ws, 'B1:I1', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        ]);
        $this->style($ws, 'D1:G1', [
            'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->style($ws, 'H1:I1', [
            'font'      => ['bold' => true, 'size' => 26, 'color' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        if (file_exists(public_path('JD-KOI.PNG'))) {
            $this->addImage($ws, 'C1', public_path('JD-KOI.PNG'), 74, 18, 2);
        }

        // ── Row 2: Alamat + No. Invoice ────────────────────────────────
        $ws->getRowDimension(2)->setRowHeight(14);
        $ws->mergeCells('D2:G2');
        $ws->mergeCells('H2:I2');
        $ws->setCellValue('D2', 'Citeko, Cisarua, Puncak  ·  +6287868882200');
        $ws->setCellValue('H2', $this->invoiceNumber);

        $this->style($ws, 'B2:I2', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        ]);
        $this->style($ws, 'D2:G2', [
            'font'      => ['size' => 9, 'color' => ['rgb' => 'AAAAAA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP],
        ]);
        $this->style($ws, 'H2:I2', [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Row 3: Garis merah di bawah header ────────────────────────
        $ws->getRowDimension(3)->setRowHeight(5);
        $ws->mergeCells('B3:I3');
        $this->style($ws, 'B3:I3', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
        ]);

        // ── Row 4: Invoice To + Tanggal ────────────────────────────────
        $ws->getRowDimension(4)->setRowHeight(24);
        $ws->mergeCells('B4:E4');
        $ws->mergeCells('F4:I4');
        $ws->setCellValue('B4', 'Invoice to: ' . $this->customerName);
        $ws->setCellValue('F4', 'Date: ' . now()->format('d F Y'));

        $this->style($ws, 'B4:I4', [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A1A']],
        ]);
        $this->style($ws, 'B4:E4', [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->style($ws, 'F4:I4', [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'C0392B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Row 5: Spacer ──────────────────────────────────────────────
        $ws->getRowDimension(5)->setRowHeight(5);

        // ── Row 6: Header tabel ────────────────────────────────────────
        $ws->getRowDimension(6)->setRowHeight(20);
        $ws->setCellValue('B6', 'No.');
        $ws->setCellValue('C6', 'Foto');
        $ws->setCellValue('D6', 'Nama Ikan');
        $ws->setCellValue('E6', 'Kode / Tanggal');
        $ws->setCellValue('F6', 'Qty');
        $ws->setCellValue('G6', 'Harga (JPY)');
        $ws->setCellValue('H6', 'Harga (IDR)');
        $ws->setCellValue('I6', 'Total (IDR)');

        $this->style($ws, 'B6:I6', [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1A252F']]],
        ]);

        // ── Rows 7+: Data koi ──────────────────────────────────────────
        $row = 7;
        foreach ($this->kois as $index => $koi) {
            $qty     = max(1, intval($koi->sale_qty ?? 1));
            $unitJpy = floatval($koi->unit_price ?? 0);
            $kurs    = floatval($koi->kurs ?? 0);
            $unitIdr = $kurs > 0 && $unitJpy > 0
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

            $bg        = $index % 2 === 0 ? 'FFFFFF' : 'F8F9FA';
            $hasShip   = $koi->shipping_cost && $koi->shipping_qty;
            $mainRow   = $row;
            $lastRow   = $row + ($hasShip ? 1 : 0);

            // ── Main koi row ──────────────────────────────────
            $ws->getRowDimension($row)->setRowHeight(self::ROW_HEIGHT);
            $ws->setCellValue('B' . $row, $index + 1);
            $ws->setCellValue('D' . $row, $koi->name . ($this->isAdmin && $feePct > 0 ? "\n(Fee {$feePct}%)" : ''));
            $ws->setCellValue('E' . $row, $koi->code . ($tanggal ? "\n" . $tanggal : ''));
            $ws->setCellValue('F' . $row, $qty);
            $ws->setCellValue('G' . $row, $unitJpy > 0 ? '¥' . number_format($unitJpy, 0) : '-');
            $ws->setCellValue('H' . $row, $unitIdr > 0 ? 'Rp ' . number_format($unitIdr, 0, ',', '.') : '-');
            $ws->setCellValue('I' . $row, $totalIdr > 0 ? 'Rp ' . number_format($totalIdr, 0, ',', '.') : '-');

            $this->style($ws, 'B' . $row . ':I' . $row, [
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'font'      => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $this->style($ws, 'B' . $row, [
                'font'      => ['color' => ['rgb' => '888888'], 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $this->style($ws, 'D' . $row, [
                'font'      => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $this->style($ws, 'E' . $row, [
                'font'      => ['size' => 9, 'color' => ['rgb' => '555555']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $this->style($ws, 'F' . $row, [
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $this->style($ws, 'I' . $row, [
                'font'      => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Foto koi
            $firstImage = $koi->images->sortBy('sort_order')->first();
            if ($firstImage && $firstImage->image_path) {
                $imgPath = public_path('storage/' . $firstImage->image_path);
                if (file_exists($imgPath)) {
                    $this->addImage($ws, 'C' . $row, $imgPath, self::IMG_PX - 4, 3, 3);
                }
            }

            $row++;

            // ── Sub-row: Biaya Shipping (di dalam kotak koi) ──
            if ($hasShip) {
                $shippingTotal = round(floatval($koi->shipping_cost) / intval($koi->shipping_qty) * $qty);
                $perEkor       = round(floatval($koi->shipping_cost) / intval($koi->shipping_qty));
                $label         = 'Biaya Shipping' . ($koi->shipping_note ? ' · ' . $koi->shipping_note : '');

                $ws->getRowDimension($row)->setRowHeight(16);
                $ws->setCellValue('D' . $row, $label);
                $ws->setCellValue('F' . $row, $qty . ' ekor');
                $ws->setCellValue('H' . $row, 'Rp ' . number_format($perEkor, 0, ',', '.') . '/ekor');
                $ws->setCellValue('I' . $row, 'Rp ' . number_format($shippingTotal, 0, ',', '.'));

                $this->style($ws, 'B' . $row . ':I' . $row, [
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8F0']],
                    'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => 'E67E22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $this->style($ws, 'D' . $row, [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $this->style($ws, 'I' . $row, [
                    'font' => ['bold' => true, 'italic' => false, 'size' => 9, 'color' => ['rgb' => 'E67E22']],
                ]);

                $row++;
            }

            // ── Merge No & Foto vertikal jika ada sub-row ─────
            if ($hasShip) {
                $ws->mergeCells('B' . $mainRow . ':B' . $lastRow);
                $ws->mergeCells('C' . $mainRow . ':C' . $lastRow);
                $this->style($ws, 'B' . $mainRow . ':B' . $lastRow, [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }

            // ── Outer border mengelilingi seluruh blok koi ────
            $this->style($ws, 'B' . $mainRow . ':I' . $lastRow, [
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
        }

        // ── Spacer ─────────────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(8);
        $row++;

        // ── Subtotal (disabled) ────────────────────────────────────────
        // $ws->getRowDimension($row)->setRowHeight(20);
        // $ws->mergeCells('B' . $row . ':F' . $row);
        // $ws->mergeCells('G' . $row . ':H' . $row);
        // $ws->setCellValue('B' . $row, 'Thank you for your purchase!');
        // $ws->setCellValue('G' . $row, 'Subtotal');
        // $ws->setCellValue('I' . $row, 'Rp ' . number_format($this->grandTotal - $this->totalFee, 0, ',', '.'));
        // $this->style($ws, 'B' . $row . ':F' . $row, [
        //     'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
        //     'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        // ]);
        // $this->style($ws, 'G' . $row . ':I' . $row, [
        //     'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']],
        //     'font'      => ['bold' => true, 'size' => 10],
        //     'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        //     'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        // ]);
        // $row++;

        // ── Total Fee (admin only) ────────────────────────────────────
        if ($this->isAdmin && $this->totalFee > 0) {
            $ws->getRowDimension($row)->setRowHeight(20);
            $ws->mergeCells('G' . $row . ':H' . $row);
            $ws->setCellValue('G' . $row, 'Total Fee');
            $ws->setCellValue('I' . $row, 'Rp ' . number_format($this->totalFee, 0, ',', '.'));
            $this->style($ws, 'G' . $row . ':I' . $row, [
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']],
                'font'      => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $row++;
        }

        // ── Grand Total ───────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(24);
        $ws->mergeCells('G' . $row . ':H' . $row);
        $ws->setCellValue('G' . $row, 'TOTAL');
        $ws->setCellValue('I' . $row, 'Rp ' . number_format($this->grandTotal, 0, ',', '.'));
        $this->style($ws, 'G' . $row . ':I' . $row, [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '922B21']]],
        ]);
        $row++;

        // ── Spacer ─────────────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(12);
        $row++;

        // ── Payment Info ───────────────────────────────────────────────
        $ws->getRowDimension($row)->setRowHeight(18);
        $ws->mergeCells('C' . $row . ':I' . $row);
        $ws->setCellValue('C' . $row, 'Payment Information');
        $this->style($ws, 'C' . $row, [
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '2C3E50']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $row++;

        foreach ([
            'Name'    => 'Kurniadi Prakasa Pieth',
            'Bank'    => 'BCA / Mandiri / GoPay / OVO',
            'No. Rek' => 'BCA: 2533 2222 34  |  Mandiri: 1690009 44789',
        ] as $label => $value) {
            $ws->getRowDimension($row)->setRowHeight(16);
            $ws->setCellValue('C' . $row, $label);
            $ws->mergeCells('D' . $row . ':I' . $row);
            $ws->setCellValue('D' . $row, $value);
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
        $ws->getPageMargins()
            ->setTop(0.4)->setBottom(0.4)
            ->setLeft(0.4)->setRight(0.4);

        // Print area
        $ws->getPageSetup()->setPrintArea('A1:I' . ($row - 1));
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
        $drawing->setResizeProportional(true);
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
