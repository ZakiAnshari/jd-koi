<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $pesanan->kode_pesanan }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 25mm 15mm 25mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11.5px;
            color: #1a1a1a;
            background: #ffffff;
            line-height: 1.65;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        /* ── CLEARFIX ────────────────────────────────── */
        .cf::after {
            content: '';
            display: table;
            clear: both;
        }

        /* ── SPACING ─────────────────────────────────── */
        .sp-sm  { margin-bottom: 14px; }
        .sp-md  { margin-bottom: 22px; }
        .sp-lg  { margin-bottom: 30px; }

        /* ── RULES ───────────────────────────────────── */
        .rule-bold {
            border-top: 1.5px solid #111111;
            margin: 18px 0;
            font-size: 0;
            height: 0;
        }
        .rule-light {
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
            font-size: 0;
            height: 0;
        }

        /* ════════════════════════════════════════════════
           HEADER
        ════════════════════════════════════════════════ */
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 48%;
            text-align: right;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #111111;
            letter-spacing: -0.3px;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 9px;
            color: #aaaaaa;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }

        .inv-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #9ca3af;
        }
        .inv-number {
            font-size: 12px;
            font-weight: 700;
            color: #111111;
            margin-top: 4px;
        }
        .inv-meta {
            font-size: 9.5px;
            color: #6b7280;
            line-height: 1.75;
            margin-top: 2px;
        }

        /* ════════════════════════════════════════════════
           PARTIES (DARI & KEPADA)
        ════════════════════════════════════════════════ */
        .party-left {
            float: left;
            width: 48%;
        }
        .party-right {
            float: right;
            width: 48%;
        }

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
        }
        .party-info {
            font-size: 9.5px;
            color: #6b7280;
            line-height: 1.8;
        }

        /* ════════════════════════════════════════════════
           ITEMS
        ════════════════════════════════════════════════ */
        .items-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        /* row header */
        .item-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 7px;
            margin-bottom: 0;
        }
        .item-header .col-prod  { float: left;  width: 48%; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .item-header .col-qty   { float: left;  width: 14%; text-align: center; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .item-header .col-price { float: right; width: 19%; text-align: right; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .item-header .col-sub   { float: right; width: 19%; text-align: right; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }

        /* row item */
        .item-row {
            border-bottom: 1px solid #f3f4f6;
            padding: 10px 0;
            page-break-inside: avoid;
        }
        .item-row .col-prod  { float: left;  width: 48%; }
        .item-row .col-qty   { float: left;  width: 14%; text-align: center; padding-top: 1px; }
        .item-row .col-price { float: right; width: 19%; text-align: right; padding-top: 1px; }
        .item-row .col-sub   { float: right; width: 19%; text-align: right; padding-top: 1px; }

        .prod-name { font-size: 11px; font-weight: 600; color: #111111; }
        .prod-meta { font-size: 9px; color: #9ca3af; margin-top: 2px; }
        .item-qty  { font-size: 11px; color: #1a1a1a; }
        .item-price { font-size: 11px; color: #1a1a1a; }
        .item-sub   { font-size: 11px; color: #1a1a1a; }

        /* ════════════════════════════════════════════════
           TOTALS
        ════════════════════════════════════════════════ */
        .totals-wrap {
            width: 40%;
            float: right;
            margin-top: 12px;
        }
        .total-row {
            padding: 4px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .total-row .tl { float: left;  font-size: 10.5px; color: #9ca3af; }
        .total-row .tr { float: right; font-size: 10.5px; color: #374151; text-align: right; }
        .total-row.grand {
            border-top: 1.5px solid #111111;
            border-bottom: none;
            padding-top: 9px;
        }
        .total-row.grand .tl { font-size: 12.5px; font-weight: 700; color: #111111; }
        .total-row.grand .tr { font-size: 12.5px; font-weight: 700; color: #111111; }

        /* ════════════════════════════════════════════════
           CATATAN
        ════════════════════════════════════════════════ */
        .note-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .note-text {
            font-size: 10.5px;
            color: #6b7280;
        }

        /* ════════════════════════════════════════════════
           FOOTER
        ════════════════════════════════════════════════ */
        .footer {
            text-align: center;
            font-size: 9.5px;
            color: #9ca3af;
            line-height: 1.75;
        }

        /* ════════════════════════════════════════════════
           CONTAINER
        ════════════════════════════════════════════════ */
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px 50px;
            background: #ffffff;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
<div class="container">

{{-- ═══ HEADER ══════════════════════════════════════════ --}}
<div class="cf sp-sm">
    <div class="header-left">
        <div class="brand">JD-KOI</div>
        <div class="brand-sub">Toko Ikan Koi Premium</div>
    </div>
    <div class="header-right">
        <div class="inv-label">Invoice</div>
        <div class="inv-number">{{ $pesanan->kode_pesanan }}</div>
        <div class="inv-meta">
            {{ $pesanan->created_at->format('d M Y') }}, {{ $pesanan->created_at->format('H:i') }} WIB<br>
            Status: {{ $pesanan->status === 'confirmed' ? 'Terkonfirmasi' : 'Selesai' }}
        </div>
    </div>
</div>

<div class="rule-bold"></div>

{{-- ═══ DARI & KEPADA ════════════════════════════════════ --}}
<div class="cf sp-lg">
    <div class="party-left">
        <div class="party-label">Dari</div>
        <div class="party-name">JD-KOI</div>
        <div class="party-info">
            Spesialis Ikan Koi Premium<br>
            +62 823-8744-4002<br>
            admin@jd-koi.com
        </div>
    </div>
    <div class="party-right">
        <div class="party-label">Kepada</div>
        <div class="party-name">{{ $customer->name }}</div>
        <div class="party-info">
            {{ $customer->email }}<br>
            @if($customer->no_telepon){{ $customer->no_telepon }}<br>@endif
            @if($customer->alamat){{ $customer->alamat }}@endif
        </div>
    </div>
</div>

{{-- ═══ DETAIL PRODUK ════════════════════════════════════ --}}
<div class="sp-sm">
    <div class="items-label">Detail Pesanan</div>

    {{-- header kolom --}}
    <div class="item-header cf">
        <div class="col-prod">Produk</div>
        <div class="col-qty">Jumlah</div>
        <div class="col-price">Harga</div>
        <div class="col-sub">Subtotal</div>
    </div>

    {{-- satu baris produk --}}
    <div class="item-row cf">
        <div class="col-prod">
            <div class="prod-name">{{ $pesanan->katalogKoi->koi->name }}</div>
            <div class="prod-meta">
                @if($pesanan->katalogKoi->koi->breeder->name_breeder ?? null)
                    {{ $pesanan->katalogKoi->koi->breeder->name_breeder }}
                @endif
                @if($pesanan->katalogKoi->koi->code ?? null)
                    &bull; {{ $pesanan->katalogKoi->koi->code }}
                @endif
            </div>
        </div>
        <div class="col-qty">
            <div class="item-qty">{{ $pesanan->qty }}&nbsp;ekor</div>
        </div>
        <div class="col-price">
            <div class="item-price">Rp&nbsp;{{ number_format($pesanan->harga_satuan, 0, ',', '.') }}</div>
        </div>
        <div class="col-sub">
            <div class="item-sub">Rp&nbsp;{{ number_format($pesanan->harga_satuan * $pesanan->qty, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- ═══ TOTAL ══════════════════════════════════════════════ --}}
<div class="cf">
    <div class="totals-wrap">
        <div class="total-row grand cf">
            <div class="tl">Total</div>
            <div class="tr">Rp&nbsp;{{ number_format($pesanan->total_harga, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- ═══ CATATAN ════════════════════════════════════════════ --}}
@if($pesanan->catatan)
<div class="cf">
    <div class="rule-light"></div>
    <div class="note-label">Catatan</div>
    <div class="note-text">{{ $pesanan->catatan }}</div>
</div>
@endif

{{-- ═══ FOOTER ═════════════════════════════════════════════ --}}
<div class="cf">
    <div class="rule-light"></div>
    <div class="footer">
        Terima kasih telah berbelanja di JD-KOI.<br>
        Dicetak {{ now()->format('d M Y, H:i') }} WIB
    </div>
</div>

</div>
</body>
</html>