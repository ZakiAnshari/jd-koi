<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoiceNumber }}</title>
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
                @php $logoPath = public_path('JD-KOI.PNG'); @endphp
                @if(file_exists($logoPath))
                    <div class="logo-img"><img src="{{ $logoPath }}" alt="Logo"></div>
                @endif
                <div class="company-name">
                    <div class="name">JAPAN DIRECT KOI CENTER</div>
                    <div class="addr">Citeko, Cisarua, Puncak &nbsp;·&nbsp; +6287868882200</div>
                </div>
            </div>
        </div>
        <div class="h-right">
            <div class="inv-label">INVOICE</div>
            <div class="inv-number">{{ $invoiceNumber }}</div>
        </div>
    </div>

    <hr class="divider">

    <!-- ── Invoice To & Date ── -->
    <div class="meta">
        <div class="meta-left">
            <div class="meta-label">Invoice to:</div>
            <div class="meta-value">{{ $customerName }}</div>
        </div>
        <div class="meta-right">
            <div class="meta-date">{{ \Carbon\Carbon::now()->format('d F Y') }}</div>
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
        @foreach($kois as $index => $koi)
        @php
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
        @endphp
        <tr>
            <td class="td-no">{{ $index + 1 }}</td>

            <!-- Item Description -->
            <td>
                <div class="desc-wrap">
                    <div class="desc-img">
                        @if($imgExists)
                            <img src="{{ $imgPath }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="desc-info">
                        <div class="desc-name">{{ $koi->name }}</div>
                        <div class="desc-code">{{ $koi->code }}</div>
                        @if($koi->azukari_period)
                            <div class="desc-period">{{ $koi->azukari_period }}</div>
                        @elseif($tanggal || $shipped)
                            <div class="desc-period">
                                {{ $tanggal ?? '' }}@if($tanggal && $shipped) – {{ $shipped }}@elseif($shipped){{ $shipped }}@endif
                            </div>
                        @endif
                        <div class="desc-sub">
                            @if($feePct > 0)
                                <span class="badge badge-fee">Fee {{ $feePct }}%</span>
                            @endif
                            @if($shipped)
                                <span class="badge badge-ship">Shipping</span>
                            @endif
                        </div>
                        @if($shipped)
                            <div class="sub-row"><span class="lbl">Purchased Date: </span>{{ $tanggal ?? '-' }}</div>
                        @endif
                    </div>
                </div>
            </td>

            <!-- Qty -->
            <td class="td-qty">{{ $qty }}</td>

            <!-- Price JPY -->
            <td class="td-jpy">
                @if($unitJpy > 0)
                    <div class="price-main">¥{{ number_format($unitJpy) }}</div>
                    @if($feePct > 0)
                        <div class="price-sub">Fee {{ $feePct }}%</div>
                        <div class="price-fee">¥{{ number_format(round($unitJpy / $qty)) }}</div>
                    @endif
                @else
                    <span style="color:#bbb">–</span>
                @endif
            </td>

            <!-- Price IDR -->
            <td class="td-idr">
                @if($unitIdr > 0)
                    <div class="price-main">{{ number_format($unitIdr, 0, ',', '.') }}</div>
                    @if($feePerUnit > 0)
                        <div class="price-sub">{{ number_format($feePerUnit, 0, ',', '.') }}</div>
                        <div class="price-fee">{{ number_format($unitIdr - $feePerUnit, 0, ',', '.') }}</div>
                    @endif
                @else
                    <span style="color:#bbb">–</span>
                @endif
            </td>

            <!-- Total -->
            <td class="td-total">
                @if($totalIdr > 0)
                    <div class="price-main">{{ number_format($totalIdr, 0, ',', '.') }}</div>
                    @if($feeAmt > 0)
                        <div class="price-sub">{{ number_format($feeAmt, 0, ',', '.') }}</div>
                        <div class="price-fee">{{ number_format($totalIdr - $feeAmt, 0, ',', '.') }}</div>
                    @endif
                @else
                    <span style="color:#bbb">–</span>
                @endif
            </td>
        </tr>
        @endforeach
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
                    <div class="pay-val">: {{ $paymentName ?? '-' }}</div>
                </div>
                <div class="pay-row">
                    <div class="pay-key">Account</div>
                    <div class="pay-val">: {{ $paymentBank ?? '-' }}</div>
                </div>
                <div class="pay-row">
                    <div class="pay-key">No. Rek</div>
                    <div class="pay-val">: {{ $paymentAccount ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="total-right">
            <table class="sum-table">
                @php $koiSubtotal = $grandTotal - ($totalShipping ?? 0); @endphp
                <tr>
                    <td class="sum-label">Subtotal</td>
                    <td class="sum-val">IDR {{ number_format($koiSubtotal, 0, ',', '.') }}</td>
                </tr>
                @if(!empty($totalShipping) && $totalShipping > 0)
                @php
                    $shippingGroups = $kois->filter(fn($k) => $k->shipping_cost && $k->shipping_qty && $k->kurs)
                        ->groupBy(fn($k) => $k->shipping_cost . '|' . $k->shipping_qty . '|' . $k->kurs);
                @endphp
                <tr>
                    <td class="sum-label" style="vertical-align:top;">
                        Biaya Shipping
                        @foreach($shippingGroups as $key => $group)
                        @php
                            [$sc, $sq, $kr] = explode('|', $key);
                            $perEkor = round(floatval($sc) * floatval($kr) / intval($sq));
                            $groupQty = $group->sum(fn($k) => intval($k->sale_qty ?? 1));
                            $note = $group->first()->shipping_note;
                        @endphp
                        <div style="font-size:7px; color:#999; margin-top:2px;">
                            ¥{{ number_format($sc) }} ÷ {{ $sq }} ikan × {{ $groupQty }} terjual
                            @if($note) · {{ $note }}@endif
                        </div>
                        @endforeach
                    </td>
                    <td class="sum-val">IDR {{ number_format($totalShipping, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($totalFee > 0)
                <tr>
                    <td class="sum-label">Total Fee</td>
                    <td class="sum-val">IDR {{ number_format($totalFee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="sum-grand">
                    <td class="sum-label">Total</td>
                    <td class="sum-val">IDR {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Invoice {{ $invoiceNumber }} &nbsp;·&nbsp; Dicetak {{ \Carbon\Carbon::now()->format('d M Y H:i') }}
    </div>

</div>
</body>
</html>
