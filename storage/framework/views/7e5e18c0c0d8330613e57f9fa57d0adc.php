<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo e($invoiceNumber); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1a1a1a; background: #fff; }
        @page { margin: 12mm 16mm 15mm 16mm; }

        .page-wrap { padding: 0 4mm; }

        /* ── Header ── */
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .h-left  { display: table-cell; vertical-align: middle; }
        .h-right { display: table-cell; vertical-align: middle; text-align: right; }

        .h-logo-wrap { display: table; }
        .logo-img {
            display: table-cell;
            vertical-align: middle;
            padding-right: 10px;
            width: 66px;
        }
        .logo-img img { width: 62px; height: 62px; }
        .company-name { display: table-cell; vertical-align: middle; text-align: center; }
        .company-name .name { font-size: 14px; font-weight: bold; color: #c0392b; }
        .company-name .addr { font-size: 8px; color: #666; margin-top: 2px; }

        .inv-label  { font-size: 26px; font-weight: bold; color: #c0392b; letter-spacing: 3px; }
        .inv-number { font-size: 13px; font-weight: bold; color: #333; }

        .divider { border: none; border-top: 2px solid #c0392b; margin: 8px 0; }

        /* ── Invoice meta ── */
        .meta { display: table; width: 100%; margin-bottom: 10px; }
        .meta-left  { display: table-cell; vertical-align: top; }
        .meta-right { display: table-cell; vertical-align: top; text-align: right; }
        .meta-label { font-size: 8px; color: #999; text-transform: uppercase; letter-spacing: .8px; }
        .meta-value { font-size: 11px; font-weight: bold; color: #222; margin-top: 1px; }
        .meta-date  { font-size: 9px; color: #555; margin-top: 2px; }

        /* ── Table ── */
        .inv-table { width: 100%; border-collapse: collapse; }

        .inv-table thead tr {
            background: #c0392b;
            color: #fff;
        }
        .inv-table thead th {
            padding: 7px 6px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #a93226;
        }
        .inv-table thead th.th-left { text-align: left; }

        .inv-table tbody tr { border-bottom: 1px solid #ddd; }
        .inv-table tbody tr:nth-child(odd)  { background: #fff; }
        .inv-table tbody tr:nth-child(even) { background: #f9f9f9; }

        .inv-table td {
            padding: 7px 6px;
            vertical-align: middle;
            border: 1px solid #e0e0e0;
            font-size: 9px;
        }
        .td-no    { text-align: center; color: #666; width: 20px; font-size: 8.5px; }
        .td-qty   { text-align: center; width: 28px; font-weight: bold; }
        .td-jpy   { text-align: right; width: 70px; }
        .td-idr   { text-align: right; width: 78px; }
        .td-total { text-align: right; width: 82px; font-weight: bold; }

        /* ── Item description ── */
        .desc-wrap { display: table; width: 100%; }
        .desc-img  {
            display: table-cell;
            vertical-align: top;
            width: 54px;
            padding-right: 7px;
        }
        .desc-img img {
            width: 50px; height: 50px;
            object-fit: cover;
            border-radius: 3px;
            border: 1px solid #ccc;
            display: block;
        }
        .desc-img .no-img {
            width: 50px; height: 50px;
            background: #ebebeb;
            border: 1px dashed #bbb;
            border-radius: 3px;
        }
        .desc-info { display: table-cell; vertical-align: top; }
        .desc-name { font-size: 9px; font-weight: bold; color: #1a1a1a; line-height: 1.4; }
        .desc-code { font-size: 8px; color: #888; margin-top: 1px; }
        .desc-period { font-size: 8px; color: #aaa; margin-top: 1px; }

        .desc-sub { margin-top: 4px; }
        .sub-row  { font-size: 8px; color: #555; margin-top: 2px; }
        .sub-row .lbl { color: #aaa; }

        .badge {
            display: inline-block;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 7.5px;
            font-weight: bold;
            margin-right: 3px;
        }
        .badge-fee  { background: #fff3cd; color: #7d6008; border: 1px solid #fde68a; }
        .badge-ship { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        /* ── Price sub-lines ── */
        .price-main { font-size: 9px; font-weight: bold; color: #1a1a1a; }
        .price-sub  { font-size: 7.5px; color: #888; margin-top: 2px; }
        .price-fee  { font-size: 7.5px; color: #c0392b; margin-top: 1px; }

        /* ── Total section ── */
        .total-wrap { display: table; width: 100%; margin-top: 14px; }
        .total-left  { display: table-cell; vertical-align: top; width: 52%; padding-right: 16px; }
        .total-right { display: table-cell; vertical-align: top; width: 48%; }

        .thankyou { font-size: 10px; font-weight: bold; color: #1a1a1a; margin-bottom: 6px; }

        .pay-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 10px;
        }
        .pay-title { font-size: 8.5px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .5px; }
        .pay-row   { display: table; width: 100%; margin-bottom: 3px; }
        .pay-key   { display: table-cell; width: 65px; font-size: 8px; color: #999; }
        .pay-val   { display: table-cell; font-size: 8px; font-weight: bold; color: #1a1a1a; }

        .sum-table { width: 100%; border-collapse: collapse; }
        .sum-table td { padding: 5px 8px; font-size: 9px; border: 1px solid #e0e0e0; }
        .sum-table .sum-label { color: #666; background: #f5f5f5; }
        .sum-table .sum-val   { text-align: right; font-weight: bold; }
        .sum-table .sum-grand td {
            background: #c0392b;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            border-color: #a93226;
        }
        .sum-table .sum-grand .sum-val { text-align: right; }

        /* ── Footer ── */
        .footer {
            margin-top: 16px;
            border-top: 1px solid #eee;
            padding-top: 6px;
            font-size: 7.5px;
            color: #bbb;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page-wrap">

    <!-- ── Header ── -->
    <div class="header">
        <div class="h-left">
            <div class="h-logo-wrap">
                <?php $logoPath = public_path('JD-KOI.PNG'); ?>
                <?php if(file_exists($logoPath)): ?>
                    <div class="logo-img"><img src="<?php echo e($logoPath); ?>" alt="Logo"></div>
                <?php endif; ?>
                <div class="company-name">
                    <div class="name">JAPAN DIRECT KOI CENTER</div>
                    <div class="addr">Citeko, Cisarua, Puncak &nbsp;·&nbsp; +6287868882200</div>
                </div>
            </div>
        </div>
        <div class="h-right">
            <div class="inv-label">INVOICE</div>
            <div class="inv-number"><?php echo e($invoiceNumber); ?></div>
        </div>
    </div>

    <hr class="divider">

    <!-- ── Invoice To & Date ── -->
    <div class="meta">
        <div class="meta-left">
            <div class="meta-label">Invoice to:</div>
            <div class="meta-value"><?php echo e($customerName); ?></div>
        </div>
        <div class="meta-right">
            <div class="meta-date"><?php echo e(\Carbon\Carbon::now()->format('d F Y')); ?></div>
        </div>
    </div>

    <!-- ── Table ── -->
    <table class="inv-table">
        <thead>
            <tr>
                <th class="th-left" style="width:20px">No.</th>
                <th class="th-left">Item Description</th>
                <th>Qty</th>
                <th>Price JPY</th>
                <th>Price IDR</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php $__currentLoopData = $kois; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $koi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $firstImage  = $koi->images->first();
            $imgPath     = $firstImage ? storage_path('app/public/' . $firstImage->image_path) : null;
            $imgExists   = $imgPath && file_exists($imgPath);

            $qty         = max(1, intval($koi->sale_qty ?? 1));
            $unitJpy     = floatval($koi->unit_price ?? 0);
            $kurs        = floatval($koi->kurs ?? 0);
            $unitIdr     = $kurs > 0 && $unitJpy > 0 ? round($unitJpy * $kurs) : round(floatval($koi->sell_idr ?? 0) / $qty);
            $totalIdr    = $unitIdr * $qty;
            $feeAmt      = floatval($koi->fee ?? 0);
            $feePct      = $totalIdr > 0 && $feeAmt > 0 ? round($feeAmt / $totalIdr * 100) : 0;
            $feePerUnit  = $qty > 0 ? round($feeAmt / $qty) : 0;

            $tanggal     = $koi->sale_tanggal     ? \Carbon\Carbon::parse($koi->sale_tanggal)->format('d M Y') : null;
            $shipped     = $koi->sale_shipped_date ? \Carbon\Carbon::parse($koi->sale_shipped_date)->format('d M Y') : null;

            $hasShip     = $koi->shipping_cost && $koi->shipping_qty;
            $shipPerEkor = $hasShip ? round(floatval($koi->shipping_cost) / intval($koi->shipping_qty)) : 0;
            $shipTotal   = $shipPerEkor * $qty;
        ?>
        <tr>
            <td class="td-no"><?php echo e($index + 1); ?></td>

            <!-- Item Description -->
            <td>
                <div class="desc-wrap">
                    <div class="desc-img">
                        <?php if($imgExists): ?>
                            <img src="<?php echo e($imgPath); ?>" alt="">
                        <?php else: ?>
                            <div class="no-img"></div>
                        <?php endif; ?>
                    </div>
                    <div class="desc-info">
                        <div class="desc-name"><?php echo e($koi->name); ?></div>
                        <div class="desc-code"><?php echo e($koi->code); ?></div>
                        <?php if($koi->azukari_period): ?>
                            <div class="desc-period"><?php echo e($koi->azukari_period); ?></div>
                        <?php elseif($tanggal || $shipped): ?>
                            <div class="desc-period">
                                <?php echo e($tanggal ?? ''); ?><?php if($tanggal && $shipped): ?> – <?php echo e($shipped); ?><?php elseif($shipped): ?><?php echo e($shipped); ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="desc-sub"></div>
                        <?php if($shipped): ?>
                            <div class="sub-row"><span class="lbl">Purchased Date: </span><?php echo e($tanggal ?? '-'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>

            <!-- Qty -->
            <td class="td-qty"><?php echo e($qty); ?></td>

            <!-- Price JPY -->
            <td class="td-jpy">
                <?php if($unitJpy > 0): ?>
                    <div class="price-main">¥<?php echo e(number_format($unitJpy)); ?></div>
                <?php else: ?>
                    <span style="color:#bbb">–</span>
                <?php endif; ?>
            </td>

            <!-- Price IDR -->
            <td class="td-idr">
                <?php if($unitIdr > 0): ?>
                    <div class="price-main"><?php echo e(number_format($unitIdr, 0, ',', '.')); ?></div>
                <?php else: ?>
                    <span style="color:#bbb">–</span>
                <?php endif; ?>
            </td>

            <!-- Total -->
            <td class="td-total">
                <?php if($totalIdr > 0): ?>
                    <div class="price-main"><?php echo e(number_format($totalIdr, 0, ',', '.')); ?></div>
                <?php else: ?>
                    <span style="color:#bbb">–</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if($hasShip): ?>
        <tr style="background:#f0f6ff;">
            <td class="td-no"></td>
            <td style="padding:4px 6px; font-size:8px; color:#1e40af; font-style:italic;">
                Biaya Shipping<?php echo e($koi->shipping_note ? ' · ' . $koi->shipping_note : ''); ?>

            </td>
            <td class="td-qty" style="font-size:8px; color:#1e40af; font-style:italic;"><?php echo e($qty); ?></td>
            <td class="td-jpy" style="font-size:8px; color:#aaa;">–</td>
            <td class="td-idr" style="font-size:8px; color:#1e40af; font-style:italic; text-align:right;">
                <?php echo e(number_format($shipPerEkor, 0, ',', '.')); ?>/ekor
            </td>
            <td class="td-total" style="font-size:8px; font-weight:bold; color:#1e40af; text-align:right;">
                <?php echo e(number_format($shipTotal, 0, ',', '.')); ?>

            </td>
        </tr>
        <?php endif; ?>
        <?php if($isAdmin && $feeAmt > 0): ?>
        <tr style="background:#fffdf0;">
            <td class="td-no"></td>
            <td style="padding:4px 6px; font-size:8px; color:#7d6008; font-style:italic;">
                Fee <?php echo e($feePct); ?>%
            </td>
            <td class="td-qty" style="font-size:8px; color:#7d6008; font-style:italic;"><?php echo e($qty); ?></td>
            <td class="td-jpy" style="font-size:8px; color:#aaa;">–</td>
            <td class="td-idr" style="font-size:8px; color:#7d6008; font-style:italic; text-align:right;">
                <?php echo e(number_format($feePerUnit, 0, ',', '.')); ?>/ekor
            </td>
            <td class="td-total" style="font-size:8px; font-weight:bold; color:#7d6008; text-align:right;">
                <?php echo e(number_format($feeAmt, 0, ',', '.')); ?>

            </td>
        </tr>
        <?php endif; ?>
        <?php
            $hasAzukari = $koi->azukari_period || $koi->azukari_qty || $koi->azukari_price_jpy;
        ?>
        <?php if($hasAzukari): ?>
        <?php
            $azuQty = intval($koi->azukari_qty ?? 0);
            $azuJpy = floatval($koi->azukari_price_jpy ?? 0);
            $azuIdr = $kurs > 0 && $azuJpy > 0 ? round($azuJpy * $kurs) : 0;
            $azuTotal = $azuIdr * $azuQty;
        ?>
        <tr style="background:#f0fdf4;">
            <td class="td-no"></td>
            <td style="padding:4px 6px; font-size:8px; color:#166534; font-style:italic;">
                Azukari<?php echo e($koi->azukari_period ? ' · ' . $koi->azukari_period : ''); ?>

            </td>
            <td class="td-qty" style="font-size:8px; color:#166534; font-style:italic;">
                <?php echo e($azuQty > 0 ? $azuQty : '-'); ?>

            </td>
            <td class="td-jpy" style="font-size:8px; color:#166534; font-style:italic; text-align:right;">
                <?php if($azuJpy > 0): ?> ¥<?php echo e(number_format($azuJpy)); ?> <?php else: ?> – <?php endif; ?>
            </td>
            <td class="td-idr" style="font-size:8px; color:#166534; font-style:italic; text-align:right;">
                <?php if($azuIdr > 0): ?> IDR <?php echo e(number_format($azuIdr, 0, ',', '.')); ?> <?php else: ?> – <?php endif; ?>
            </td>
            <td class="td-total" style="font-size:8px; color:#166534; font-weight:bold; text-align:right;">
                <?php if($azuTotal > 0): ?> IDR <?php echo e(number_format($azuTotal, 0, ',', '.')); ?> <?php else: ?> – <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <!-- ── Total & Payment ── -->
    <div class="total-wrap">
        <div class="total-left">
            <div class="thankyou">Thank you for your purchased</div>
            <div class="pay-box">
                <div class="pay-title">Payment info:</div>
                <div class="pay-row">
                    <div class="pay-key">Name</div>
                    <div class="pay-val">: Kurniadi Prakasa Pieth</div>
                </div>
                <div class="pay-row">
                    <div class="pay-key">Account</div>
                    <div class="pay-val">: BCA 2533 2222 34</div>
                </div>
                <div class="pay-row">
                    <div class="pay-key"></div>
                    <div class="pay-val">: Mandiri 16500 999 48789</div>
                </div>
                <div class="pay-row">
                    <div class="pay-key"></div>
                    <div class="pay-val">: Panin 151213 7001</div>
                </div>
            </div>
        </div>
        <div class="total-right">
            <table class="sum-table">
                
                
                
                
                <tr class="sum-grand">
                    <td class="sum-label">TOTAL</td>
                    <td class="sum-val">Rp <?php echo e(number_format($grandTotal, 0, ',', '.')); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Invoice <?php echo e($invoiceNumber); ?> &nbsp;·&nbsp; Dicetak <?php echo e(\Carbon\Carbon::now()->format('d M Y H:i')); ?>

    </div>

</div>
</body>
</html>
<?php /**PATH C:\laragon\www\koi-umeda-data-management\resources\views/pdf/invoice.blade.php ENDPATH**/ ?>