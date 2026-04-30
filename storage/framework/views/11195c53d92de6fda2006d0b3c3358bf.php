<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Transaksi Koi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; background: #fff; }

        @page { margin: 25mm 15mm 20mm 15mm; }

        /* ── Header ── */
        .doc-header {
            border-bottom: 3px solid #1e7e34;
            padding-bottom: 10px;
            margin-bottom: 18px;
            display: table;
            width: 100%;
        }
        .doc-header-left  { display: table-cell; vertical-align: middle; }
        .doc-header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .doc-title { font-size: 20px; font-weight: bold; color: #1e7e34; }
        .doc-subtitle { font-size: 10px; color: #666; margin-top: 3px; }

        /* ── Summary box ── */
        .summary-box {
            background: #f0f7f0;
            border: 1px solid #b2d8b2;
            border-left: 4px solid #1e7e34;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #444;
        }
        .summary-box strong { color: #1e7e34; }

        /* ── Koi card ── */
        .koi-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 22px;
            overflow: hidden;
        }
        .koi-card-header {
            background: #1e7e34;
            color: #fff;
            padding: 8px 12px;
            display: table;
            width: 100%;
        }
        .koi-card-header .card-code { display: table-cell; font-size: 14px; font-weight: bold; }
        .koi-card-header .card-name { display: table-cell; text-align: right; font-size: 11px; opacity: .85; }

        .koi-card-body { display: table; width: 100%; }

        /* Image column */
        .col-image {
            display: table-cell;
            width: 160px;
            vertical-align: top;
            padding: 10px;
            border-right: 1px solid #eee;
            background: #fafafa;
        }
        .koi-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: block;
        }
        .img-placeholder {
            width: 140px;
            height: 140px;
            background: #ececec;
            border: 1px dashed #bbb;
            border-radius: 4px;
            text-align: center;
            line-height: 140px;
            font-size: 10px;
            color: #aaa;
        }
        .img-caption {
            font-size: 9px;
            color: #888;
            text-align: center;
            margin-top: 4px;
        }

        /* Detail column */
        .col-detail {
            display: table-cell;
            vertical-align: top;
            padding: 10px 12px;
        }

        /* Two-column grid inside detail */
        .grid2 { display: table; width: 100%; margin-bottom: 10px; }
        .g2-left  { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
        .g2-right { display: table-cell; width: 50%; vertical-align: top; padding-left: 8px; border-left: 1px solid #eee; }

        /* Section */
        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #1e7e34;
            background: #e8f5e8;
            padding: 4px 8px;
            border-left: 3px solid #1e7e34;
            margin-bottom: 6px;
            margin-top: 4px;
        }

        /* Detail table */
        .dt { width: 100%; border-collapse: collapse; font-size: 10px; }
        .dt td { padding: 3px 4px; vertical-align: top; }
        .dt .lbl { width: 95px; color: #555; font-weight: 600; white-space: nowrap; }
        .dt .val { color: #222; }
        .dt tr { border-bottom: 1px solid #f0f0f0; }
        .dt tr:last-child { border-bottom: none; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-pending       { background:#fff3cd; color:#856404; }
        .badge-shipped       { background:#cce5ff; color:#004085; }
        .badge-azukari       { background:#d4edda; color:#155724; }
        .badge-dead          { background:#f8d7da; color:#721c24; }
        .badge-cancel        { background:#e2e3e5; color:#383d41; }
        .badge-refund        { background:#ffe5cc; color:#b85e0c; }
        .badge-dead_refund   { background:#f5c6cb; color:#721c24; }
        .badge-cancel_refund { background:#d3d4d6; color:#383d41; }

        /* Price highlight */
        .price-box {
            background: #f0f4ff;
            border: 1px solid #c5d3f0;
            border-left: 3px solid #2255bb;
            border-radius: 4px;
            padding: 6px 10px;
            margin-top: 4px;
        }

        /* Page break */
        .page-break { page-break-after: always; }

        /* Footer */
        .doc-footer {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 30px;
            font-size: 9px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- ── Document Header ── -->
    <div class="doc-header">
        <div class="doc-header-left">
            <div class="doc-title">Rekap Transaksi Koi</div>
            <div class="doc-subtitle">Koi Umeda Data Management System</div>
        </div>
        <div class="doc-header-right">
            <div style="font-size:10px; color:#555;">
                Tanggal cetak: <strong><?php echo e(now()->format('d-m-Y H:i')); ?></strong><br>
                Oleh: <strong><?php echo e($user->name ?? 'Pengguna'); ?></strong>
            </div>
        </div>
    </div>

    <!-- ── Summary ── -->
    <div class="summary-box">
        Total <strong><?php echo e(count($kois)); ?></strong> koi dalam dokumen ini.
        <?php if($isAdmin): ?> (Tampilan Admin — termasuk data customer & profit) <?php endif; ?>
    </div>

    <!-- ── Koi Cards ── -->
    <?php $__empty_1 = true; $__currentLoopData = $kois; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $koi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php if($index > 0 && $index % 3 == 0): ?>
            <div class="page-break"></div>
        <?php endif; ?>

        <?php
            $firstImage = $koi->images->first();
            $imagePath  = $firstImage ? storage_path('app/public/' . $firstImage->image_path) : null;
            $imageExists = $imagePath && file_exists($imagePath);

            $statusClass = match($koi->sale_status) {
                'pending'       => 'badge-pending',
                'shipped'       => 'badge-shipped',
                'azukari'       => 'badge-azukari',
                'dead'          => 'badge-dead',
                'cancel'        => 'badge-cancel',
                'refund'        => 'badge-refund',
                'dead_refund'   => 'badge-dead_refund',
                'cancel_refund' => 'badge-cancel_refund',
                default         => '',
            };
            $statusLabel = match($koi->sale_status) {
                'pending'       => 'Pending',
                'shipped'       => 'Shipped',
                'azukari'       => 'Azukari',
                'dead'          => 'Dead',
                'cancel'        => 'Cancel',
                'refund'        => 'Refund',
                'dead_refund'   => 'Dead Refund',
                'cancel_refund' => 'Cancel Refund',
                default         => ucfirst($koi->sale_status ?? '-'),
            };
        ?>

        <div class="koi-card">
            <!-- Card Header -->
            <div class="koi-card-header">
                <span class="card-code"><?php echo e($koi->code); ?></span>
                <span class="card-name"><?php echo e($koi->name ?? ''); ?></span>
            </div>

            <!-- Card Body -->
            <div class="koi-card-body">

                <!-- Image -->
                <div class="col-image">
                    <?php if($imageExists): ?>
                        <img src="<?php echo e($imagePath); ?>" class="koi-img" alt="<?php echo e($koi->code); ?>">
                    <?php else: ?>
                        <div class="img-placeholder">No Image</div>
                    <?php endif; ?>
                    <div class="img-caption"><?php echo e($koi->code); ?></div>
                </div>

                <!-- Detail -->
                <div class="col-detail">
                    <div class="grid2">

                        <!-- Left: Info Koi + Info Penjualan -->
                        <div class="g2-left">
                            <div class="section-title">Informasi Koi</div>
                            <table class="dt">
                                <tr>
                                    <td class="lbl">Jenis Koi</td>
                                    <td class="val"><?php echo e($koi->jenisKoi?->nama_jenis ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Breeder</td>
                                    <td class="val"><?php echo e($koi->breeder?->name_breeder ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Umur</td>
                                    <td class="val"><?php echo e($koi->age ? $koi->age . ' bulan' : '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Ukuran</td>
                                    <td class="val"><?php echo e($koi->size ? number_format($koi->size, 0) . ' cm' : '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Berat</td>
                                    <td class="val"><?php echo e($koi->weight ? $koi->weight . ' kg' : '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Total Berat</td>
                                    <td class="val"><?php echo e($koi->total_weight ? $koi->total_weight . ' kg' : '-'); ?></td>
                                </tr>
                            </table>

                            <div class="section-title">Informasi Penjualan</div>
                            <table class="dt">
                                <tr>
                                    <td class="lbl">Invoice</td>
                                    <td class="val"><strong><?php echo e($koi->sale_invoice ?? '-'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Tgl Penjualan</td>
                                    <td class="val"><?php echo e($koi->sale_tanggal?->format('d-m-Y') ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Tgl Pengiriman</td>
                                    <td class="val"><?php echo e($koi->sale_shipped_date?->format('d-m-Y') ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Status</td>
                                    <td class="val">
                                        <span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lbl">Qty Koi</td>
                                    <td class="val"><?php echo e($koi->sale_qty ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl">Qty Box</td>
                                    <td class="val"><?php echo e($koi->box_qty ?? '-'); ?></td>
                                </tr>
                                <?php if($isAdmin && $koi->customer): ?>
                                    <tr>
                                        <td class="lbl">Customer</td>
                                        <td class="val"><?php echo e($koi->customer->name); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Right: Harga -->
                        <?php
                            $unitPriceJpy  = $koi->unit_price ? (float) $koi->unit_price : null;
                            $stockJpy      = $koi->buy_jpy    ? (float) $koi->buy_jpy    : null;
                            $qty           = $koi->sale_qty   ? (int)   $koi->sale_qty   : 1;
                            $salesPriceJpy = $unitPriceJpy ? $unitPriceJpy * $qty : null;
                            $kursVal       = $koi->kurs ? (float) $koi->kurs : null;
                            $unitPriceIdr  = ($unitPriceJpy && $kursVal) ? round($unitPriceJpy * $kursVal) : null;
                            $stockPriceIdr = ($stockJpy && $kursVal)     ? round($stockJpy     * $kursVal) : null;
                            $salesPriceIdr = $koi->sell_idr ? (float) $koi->sell_idr : null;
                            $fee           = $koi->fee ? (float) $koi->fee : 0;
                            $profit        = $salesPriceIdr !== null && $stockPriceIdr !== null
                                             ? $salesPriceIdr - $stockPriceIdr - $fee
                                             : null;
                        ?>
                        <div class="g2-right">
                            <div class="section-title">Informasi Harga</div>
                            <div class="price-box">
                                <table class="dt">
                                    <tr>
                                        <td class="lbl">Stock JPY (modal)</td>
                                        <td class="val">
                                            <?php echo e($stockJpy ? '¥ ' . number_format($stockJpy, 0, ',', '.') : '-'); ?>

                                            <?php if($stockPriceIdr): ?>
                                                <br><span style="font-size:9px; color:#888;">≈ Rp <?php echo e(number_format($stockPriceIdr, 0, ',', '.')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="lbl">Unit Price (JPY)</td>
                                        <td class="val">
                                            <?php echo e($unitPriceJpy ? '¥ ' . number_format($unitPriceJpy, 0, ',', '.') : '-'); ?>

                                            <?php if($unitPriceIdr): ?>
                                                <br><span style="font-size:9px; color:#888;">≈ Rp <?php echo e(number_format($unitPriceIdr, 0, ',', '.')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="lbl">Sales Price (JPY)</td>
                                        <td class="val" style="font-weight:bold;">
                                            <?php echo e($salesPriceJpy ? '¥ ' . number_format($salesPriceJpy, 0, ',', '.') : '-'); ?>

                                            <?php if($salesPriceIdr): ?>
                                                <br><span style="font-size:9px; color:#888;">≈ Rp <?php echo e(number_format($salesPriceIdr, 0, ',', '.')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if($koi->kurs && $koi->kurs > 0): ?>
                                        <tr>
                                            <td class="lbl">Kurs</td>
                                            <td class="val">Rp <?php echo e(number_format($koi->kurs, 0, ',', '.')); ?> / ¥1</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if($isAdmin && $fee): ?>
                                        <tr>
                                            <td class="lbl">Fee</td>
                                            <td class="val">Rp <?php echo e(number_format($fee, 0, ',', '.')); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if($isAdmin && $profit !== null): ?>
                                        <tr style="border-top: 1px solid #2255bb;">
                                            <td class="lbl" style="color:<?php echo e($profit >= 0 ? '#1e7e34' : '#c0392b'); ?>; font-weight:bold;">Profit</td>
                                            <td class="val" style="color:<?php echo e($profit >= 0 ? '#1e7e34' : '#c0392b'); ?>; font-weight:bold;">
                                                Rp <?php echo e(number_format($profit, 0, ',', '.')); ?>

                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                    </div><!-- /grid2 -->
                </div><!-- /col-detail -->
            </div><!-- /card-body -->
        </div><!-- /koi-card -->

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p style="text-align:center; color:#999; padding:40px;">Tidak ada data transaksi untuk diunduh.</p>
    <?php endif; ?>

    <!-- ── Footer ── -->
    <div class="doc-footer">
        Koi Umeda Data Management System &nbsp;|&nbsp; Dicetak: <?php echo e(now()->format('d/m/Y H:i')); ?> &nbsp;|&nbsp; © <?php echo e(now()->year); ?>

    </div>

</body>
</html>
<?php /**PATH C:\laragon\www\koi-umeda-data-management\resources\views/pdf/rekap-transaksi.blade.php ENDPATH**/ ?>