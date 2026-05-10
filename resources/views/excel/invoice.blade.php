<table>
    {{-- ── Row 1: Header ── --}}
    <tr>
        <td style="background-color:#1a1a1a; vertical-align:middle; padding:4px;" colspan="1">
        </td>
        <td style="background-color:#1a1a1a; color:#ffffff; font-size:20pt; font-weight:bold; vertical-align:middle;" colspan="3">
            JAPAN DIRECT KOI CENTER
        </td>
        <td style="background-color:#1a1a1a; color:#c0392b; font-size:22pt; font-weight:bold; text-align:right; vertical-align:middle;" colspan="4">
            INVOICE
        </td>
    </tr>
    {{-- ── Row 2: Address & Invoice Number ── --}}
    <tr>
        <td style="background-color:#1a1a1a; color:#aaaaaa; font-size:10pt; text-align:center;" colspan="4">
            Citeko, Cisarua, Puncak · +6287868882200
        </td>
        <td style="background-color:#1a1a1a; color:#ffffff; font-size:14pt; font-weight:bold; text-align:right;" colspan="4">
            {{ $invoiceNumber }}
        </td>
    </tr>
    {{-- ── Row 3: Spacer ── --}}
    <tr><td colspan="8" style="background-color:#c0392b; height:4px;"></td></tr>
    {{-- ── Row 4: Invoice To & Date ── --}}
    <tr>
        <td style="font-weight:bold; font-size:11pt; padding:6px;" colspan="4">
            Invoice to: {{ $customerName }}
        </td>
        <td style="font-size:11pt; text-align:right; text-decoration:underline; color:#c0392b; font-weight:bold;" colspan="4">
            {{ \Carbon\Carbon::now()->format('d F Y') }}
        </td>
    </tr>
    {{-- ── Row 5: Spacer ── --}}
    <tr><td colspan="8"></td></tr>
    {{-- ── Row 6: Table Header ── --}}
    <tr>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:30px;">No.</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:280px;" colspan="2">Item Description</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:40px;">Qty</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:140px;">Shipping</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:100px;">Price JPY</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:120px;">Price IDR</td>
        <td style="background-color:#333333; color:#ffffff; font-weight:bold; text-align:center; border:1px solid #555; font-size:10pt; width:120px;">Total</td>
    </tr>
    {{-- ── Rows: Koi Items ── --}}
    @foreach($kois as $index => $koi)
    @php
        $bg      = $index % 2 === 0 ? '#ffffff' : '#f9f9f9';
        $qty     = max(1, intval($koi->sale_qty ?? 1));
        $unitJpy = floatval($koi->unit_price ?? 0);
        $kurs    = floatval($koi->kurs ?? 0);
        $unitIdr = $kurs > 0 && $unitJpy > 0 ? round($unitJpy * $kurs) : round(floatval($koi->sell_idr ?? 0) / $qty);
        $totalIdr = $unitIdr * $qty;
        $fee     = floatval($koi->fee ?? 0);
        $feePct  = $totalIdr > 0 && $fee > 0 ? round($fee / $totalIdr * 100) : 0;

        $shippingPerEkor = null;
        $shippingNote    = null;
        if ($koi->shipping_cost && $koi->shipping_qty && $kurs > 0) {
            $shippingPerEkor = round(floatval($koi->shipping_cost) * $kurs / intval($koi->shipping_qty));
            $shippingNote    = $koi->shipping_note;
        }
    @endphp
    <tr>
        {{-- No --}}
        <td style="background-color:{{ $bg }}; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt; color:#666;">{{ $index + 1 }}</td>
        {{-- Item Description (2 cols merged) --}}
        <td style="background-color:{{ $bg }}; vertical-align:top; border:1px solid #ddd; padding:6px;" colspan="2">
            <span style="font-weight:bold; font-size:10pt;">{{ $koi->name }}</span><br/>
            <span style="font-size:9pt; color:#888888;">{{ $koi->code }}</span><br/>
            @if($koi->azukari_period)
                <span style="font-size:9pt; color:#aaaaaa;">{{ $koi->azukari_period }}</span><br/>
            @elseif($koi->sale_tanggal)
                <span style="font-size:9pt; color:#aaaaaa;">{{ \Carbon\Carbon::parse($koi->sale_tanggal)->format('d M Y') }}</span><br/>
            @endif
            @if($feePct > 0)
                <span style="font-size:8pt; color:#c0392b;">Fee {{ $feePct }}%</span>
            @endif
        </td>
        {{-- Qty --}}
        <td style="background-color:{{ $bg }}; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt;">{{ $qty }}</td>
        {{-- Shipping --}}
        <td style="background-color:{{ $bg }}; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            @if($shippingPerEkor)
                {{ number_format($shippingPerEkor, 0, ',', '.') }}<br/>
                @if($shippingNote)<span style="font-size:8pt; color:#888888;">{{ $shippingNote }}</span>@endif
            @else
                <span style="color:#cccccc;">-</span>
            @endif
        </td>
        {{-- Price JPY --}}
        <td style="background-color:{{ $bg }}; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            @if($unitJpy > 0)
                ¥{{ number_format($unitJpy) }}
                @if($feePct > 0)<br/><span style="font-size:8pt;color:#888;">Fee {{ $feePct }}%</span>@endif
            @else –@endif
        </td>
        {{-- Price IDR --}}
        <td style="background-color:{{ $bg }}; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            @if($unitIdr > 0)
                IDR {{ number_format($unitIdr, 0, ',', '.') }}
                @if($fee > 0)<br/><span style="font-size:8pt;color:#888;">{{ number_format($fee / $qty, 0, ',', '.') }}</span>@endif
            @else –@endif
        </td>
        {{-- Total --}}
        <td style="background-color:{{ $bg }}; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; font-weight:bold; padding:6px;">
            @if($totalIdr > 0)
                IDR {{ number_format($totalIdr, 0, ',', '.') }}
                @if($fee > 0)<br/><span style="font-size:8pt;color:#888;">{{ number_format($fee, 0, ',', '.') }}</span>@endif
            @else –@endif
        </td>
    </tr>
    @php
        $hasAzukari = $koi->azukari_period || $koi->azukari_qty || $koi->azukari_price_jpy;
    @endphp
    @if($hasAzukari)
        @php
            $azuQty = intval($koi->azukari_qty ?? 0);
            $azuJpy = floatval($koi->azukari_price_jpy ?? 0);
            $azuKurs = floatval($koi->kurs ?? 0);
            $azuIdr = $azuKurs > 0 && $azuJpy > 0 ? round($azuJpy * $azuKurs) : 0;
            $azuTotal = $azuQty > 0 && $azuIdr > 0 ? $azuIdr * $azuQty : 0;
        @endphp
    <tr>
        {{-- No --}}
        <td style="background-color:#d4edda; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt; color:#666;"></td>
        {{-- Item Description (Azukari) --}}
        <td style="background-color:#d4edda; vertical-align:top; border:1px solid #ddd; padding:6px;" colspan="2">
            <span style="font-weight:bold; font-size:10pt; color:#155724;">Azukari</span>
            @if($koi->azukari_period)
                <br/><span style="font-size:9pt; color:#155724;">{{ $koi->azukari_period }}</span>
            @endif
        </td>
        {{-- Qty --}}
        <td style="background-color:#d4edda; text-align:center; vertical-align:middle; border:1px solid #ddd; font-size:10pt; color:#155724;">{{ $azuQty > 0 ? $azuQty : '-' }}</td>
        {{-- Shipping --}}
        <td style="background-color:#d4edda; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px; color:#ccc;">-</td>
        {{-- Price JPY --}}
        <td style="background-color:#d4edda; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            @if($azuJpy > 0)
                ¥{{ number_format($azuJpy) }}
            @else –@endif
        </td>
        {{-- Price IDR --}}
        <td style="background-color:#d4edda; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; padding:6px;">
            @if($azuIdr > 0)
                IDR {{ number_format($azuIdr, 0, ',', '.') }}
            @else –@endif
        </td>
        {{-- Total --}}
        <td style="background-color:#d4edda; text-align:right; vertical-align:top; border:1px solid #ddd; font-size:10pt; font-weight:bold; padding:6px; color:#155724;">
            @if($azuTotal > 0)
                IDR {{ number_format($azuTotal, 0, ',', '.') }}
            @else –@endif
        </td>
    </tr>
    @endif
    @endforeach
    {{-- ── Spacer ── --}}
    <tr><td colspan="8"></td></tr>
    {{-- ── Grand Total ── --}}
    @php $koiSubtotal = $grandTotal - ($totalShipping ?? 0); @endphp
    <tr>
        <td colspan="5" style="font-size:10pt; font-weight:bold; color:#333; padding:4px;">
            Thank you for your purchased
        </td>
        <td style="background-color:#eeeeee; font-size:10pt; font-weight:bold; text-align:right; padding:4px; border:1px solid #ddd;">Subtotal</td>
        <td style="background-color:#eeeeee; font-size:10pt; text-align:right; padding:4px; border:1px solid #ddd;" colspan="2">IDR {{ number_format($koiSubtotal, 0, ',', '.') }}</td>
    </tr>
    @if(!empty($totalShipping) && $totalShipping > 0)
    @php
        $shippingGroups = $kois->filter(fn($k) => $k->shipping_cost && $k->shipping_qty && $k->kurs)
            ->groupBy(fn($k) => $k->shipping_cost . '|' . $k->shipping_qty . '|' . $k->kurs);
        $shippingDetail = '';
        foreach ($shippingGroups as $key => $group) {
            [$sc, $sq, $kr] = explode('|', $key);
            $groupQty = $group->sum(fn($k) => intval($k->sale_qty ?? 1));
            $note = $group->first()->shipping_note;
            $line = '¥' . number_format($sc) . ' ÷ ' . $sq . ' ikan × ' . $groupQty . ' terjual';
            if ($note) $line .= ' · ' . $note;
            $shippingDetail .= ($shippingDetail ? ' | ' : '') . $line;
        }
    @endphp
    <tr>
        <td colspan="5"></td>
        <td style="background-color:#eeeeee; font-size:10pt; font-weight:bold; text-align:right; padding:4px; border:1px solid #ddd; vertical-align:top;">
            Biaya Shipping
            <div style="font-size:8pt; font-weight:normal; color:#999999; margin-top:2px;">{{ $shippingDetail }}</div>
        </td>
        <td style="background-color:#eeeeee; font-size:10pt; text-align:right; padding:4px; border:1px solid #ddd;" colspan="2">IDR {{ number_format($totalShipping, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if($totalFee > 0)
    <tr>
        <td colspan="5"></td>
        <td style="background-color:#eeeeee; font-size:10pt; font-weight:bold; text-align:right; padding:4px; border:1px solid #ddd;">Total Fee</td>
        <td style="background-color:#eeeeee; font-size:10pt; text-align:right; padding:4px; border:1px solid #ddd;" colspan="2">IDR {{ number_format($totalFee, 0, ',', '.') }}</td>
    </tr>
    @endif
    <tr>
        <td colspan="5"></td>
        <td style="background-color:#c0392b; color:#ffffff; font-size:12pt; font-weight:bold; text-align:right; padding:6px; border:1px solid #a93226;">Total</td>
        <td style="background-color:#c0392b; color:#ffffff; font-size:12pt; font-weight:bold; text-align:right; padding:6px; border:1px solid #a93226;" colspan="2">IDR {{ number_format($grandTotal, 0, ',', '.') }}</td>
    </tr>
    {{-- ── Payment Info ── --}}
    <tr><td colspan="8"></td></tr>
    <tr>
        <td style="font-size:9pt; color:#888; font-weight:bold;" colspan="8">Payment info:</td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">Name</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="7">: {{ $paymentName }}</td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">Account</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="7">: {{ $paymentBank }}</td>
    </tr>
    <tr>
        <td style="font-size:9pt; color:#888;">No. Rek</td>
        <td style="font-size:9pt; font-weight:bold;" colspan="7">: {{ $paymentAccount }}</td>
    </tr>
</table>
