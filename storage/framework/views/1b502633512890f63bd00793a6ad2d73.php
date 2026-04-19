<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice <?php echo e($pesanan->kode_pesanan); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 18mm 20mm 18mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #ffffff;
            line-height: 1.6;
            width: 100%;
            max-width: 100%;
        }

        /* ── LAYOUT HELPERS ─────────────────────────── */
        .w100 {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .w100 td, .w100 th {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .gap10 { height: 10px; font-size: 1px; line-height: 1px; }
        .gap18 { height: 18px; font-size: 1px; line-height: 1px; }
        .gap26 { height: 26px; font-size: 1px; line-height: 1px; }

        /* ── HEADER ─────────────────────────────────── */
        .brand {
            font-size: 20px;
            font-weight: 700;
            color: #111111;
            letter-spacing: -0.3px;
            line-height: 1.15;
        }
        .brand-sub {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 3px;
        }
        .invoice-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #9ca3af;
            text-align: right;
        }
        .invoice-no {
            font-size: 11px;
            font-weight: 700;
            color: #111111;
            text-align: right;
            margin-top: 5px;
        }
        .invoice-info {
            font-size: 9.5px;
            color: #6b7280;
            text-align: right;
            line-height: 1.7;
            margin-top: 2px;
        }

        /* ── RULE ───────────────────────────────────── */
        .rule-bold {
            width: 100%;
            border: none;
            border-top: 1.5px solid #111111;
        }
        .rule-light {
            width: 100%;
            border: none;
            border-top: 1px solid #e5e7eb;
        }

        /* ── PARTIES ────────────────────────────────── */
        .party-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 6px;
        }
        .party-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #111111;
            margin-bottom: 3px;
            word-wrap: break-word;
        }
        .party-detail {
            font-size: 9.5px;
            color: #6b7280;
            line-height: 1.75;
            word-wrap: break-word;
        }

        /* ── ITEMS TABLE ────────────────────────────── */
        .items-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.items thead th {
            padding: 7px 8px 8px 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            word-wrap: break-word;
        }
        table.items thead th.r { text-align: right; }
        table.items thead th.c { text-align: center; }

        table.items tbody tr { page-break-inside: avoid; }
        table.items tbody td {
            padding: 10px 8px;
            font-size: 10.5px;
            color: #1a1a1a;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.items tbody td.r { text-align: right; }
        table.items tbody td.c { text-align: center; }

        .prod-name { font-weight: 600; color: #111111; }
        .prod-meta { font-size: 9px; color: #9ca3af; margin-top: 2px; }

        /* ── TOTALS ─────────────────────────────────── */
        table.totals {
            width: 42%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-left: auto;
        }
        table.totals td {
            padding: 5px 0;
            font-size: 10.5px;
            word-wrap: break-word;
        }
        table.totals td.tl { color: #9ca3af; text-align: left; width: 50%; }
        table.totals td.tr { color: #374151; text-align: right; width: 50%; }
        table.totals tr.grand td {
            padding-top: 9px;
            border-top: 1.5px solid #111111;
            font-size: 12.5px;
            font-weight: 700;
            color: #111111;
        }

        /* ── NOTE ───────────────────────────────────── */
        .note-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .note-text {
            font-size: 10px;
            color: #6b7280;
            word-wrap: break-word;
        }

        /* ── FOOTER ─────────────────────────────────── */
        .footer-text {
            font-size: 9.5px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.7;
        }
    </style>
</head>
<body>

    
    <table class="w100">
        <tr>
            <td style="width:50%; vertical-align:top; padding:0">
                <div class="brand">JD-KOI</div>
                <div class="brand-sub">Toko Ikan Koi Premium</div>
            </td>
            <td style="width:50%; vertical-align:top; padding:0">
                <div class="invoice-label">Invoice</div>
                <div class="invoice-no"><?php echo e($pesanan->kode_pesanan); ?></div>
                <div class="invoice-info">
                    <?php echo e($pesanan->created_at->format('d M Y')); ?> &bull; <?php echo e($pesanan->created_at->format('H:i')); ?> WIB<br>
                    <?php echo e($pesanan->status === 'confirmed' ? 'Terkonfirmasi' : 'Selesai'); ?>

                </div>
            </td>
        </tr>
    </table>

    <div class="gap18"></div>
    <hr class="rule-bold">
    <div class="gap26"></div>

    
    <table class="w100">
        <tr>
            <td style="width:48%; vertical-align:top; padding:0 12px 0 0">
                <div class="party-label">Dari</div>
                <div class="party-name">JD-KOI</div>
                <div class="party-detail">
                    Spesialis Ikan Koi Premium<br>
                    +62 823-8744-4002<br>
                    admin@jd-koi.com
                </div>
            </td>
            <td style="width:52%; vertical-align:top; padding:0 0 0 12px">
                <div class="party-label">Kepada</div>
                <div class="party-name"><?php echo e($customer->name); ?></div>
                <div class="party-detail">
                    <?php echo e($customer->email); ?><br>
                    <?php if($customer->no_telepon): ?><?php echo e($customer->no_telepon); ?><br><?php endif; ?>
                    <?php if($customer->alamat): ?><?php echo e($customer->alamat); ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="gap26"></div>

    
    <table class="items">
        <colgroup>
            <col style="width:40%">
            <col style="width:15%">
            <col style="width:22%">
            <col style="width:23%">
        </colgroup>
        <thead>
            <tr>
                <th>Produk</th>
                <th class="c">Jumlah</th>
                <th class="r">Harga</th>
                <th class="r">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="prod-name"><?php echo e($pesanan->katalogKoi->koi->name); ?></div>
                    <div class="prod-meta">
                        <?php if($pesanan->katalogKoi->koi->breeder->name_breeder ?? null): ?>
                            <?php echo e($pesanan->katalogKoi->koi->breeder->name_breeder); ?>

                        <?php endif; ?>
                        <?php if($pesanan->katalogKoi->koi->code ?? null): ?>
                            &bull; <?php echo e($pesanan->katalogKoi->koi->code); ?>

                        <?php endif; ?>
                    </div>
                </td>
                <td class="c"><?php echo e($pesanan->qty); ?> ekor</td>
                <td class="r">Rp <?php echo e(number_format($pesanan->harga_satuan, 0, ',', '.')); ?></td>
                <td class="r">Rp <?php echo e(number_format($pesanan->harga_satuan * $pesanan->qty, 0, ',', '.')); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="gap18"></div>

    
    <table class="totals">
        <tr class="grand">
            <td class="tl">Total</td>
            <td class="tr">Rp <?php echo e(number_format($pesanan->total_harga, 0, ',', '.')); ?></td>
        </tr>
    </table>

    
    <?php if($pesanan->catatan): ?>
    <div class="gap26"></div>
    <hr class="rule-light">
    <div class="gap10"></div>
    <div class="note-label">Catatan</div>
    <div class="note-text"><?php echo e($pesanan->catatan); ?></div>
    <?php endif; ?>

    <div class="gap26"></div>
    <hr class="rule-light">
    <div class="gap18"></div>

    
    <div class="footer-text">
        Terima kasih telah berbelanja di JD-KOI.<br>
        Dicetak <?php echo e(now()->format('d M Y, H:i')); ?> WIB
    </div>

</body>
</html>
<?php /**PATH C:\laragon\www\koi-umeda-data-management\resources\views/customer/nota.blade.php ENDPATH**/ ?>