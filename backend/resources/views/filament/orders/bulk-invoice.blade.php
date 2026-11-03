<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Invoices — {{ count($orders) }} Orders</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #222;
            background: #e5e7eb;
            padding: 16px
        }

        /* ── Screen toolbar ── */
        .toolbar {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
            max-width: 720px;
            margin: 0 auto 14px
        }

        .toolbar-info {
            font-size: 13px;
            color: #334155;
            font-weight: 600
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer
        }

        .btn-print {
            background: #1e293b;
            color: #fff
        }

        /* ── A4 page container — 2 invoices per page ── */
        .a4-page {
            max-width: 720px;
            margin: 0 auto 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .10);
            overflow: hidden;
        }

        /* The divider line between the two invoices on one page */
        .inv-divider {
            border: none;
            border-top: 1.5px dashed #cbd5e1;
            margin: 0;
        }

        /* ── Reuse invoice card styles ── */
        .inv-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px 22px 12px;
            border-bottom: 2px solid #1e293b
        }

        .shop-logo { max-height: 46px; max-width: 220px; width: auto; height: auto; object-fit: contain; display: block; }
        .shop-name {
            font-size: 15px;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -.3px
        }

        .shop-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px
        }

        .inv-title {
            text-align: right
        }

        .inv-title h2 {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #1e293b;
            text-transform: uppercase
        }

        .inv-num {
            font-size: 11px;
            font-weight: 700;
            color: #0f766e;
            margin-top: 3px
        }

        .inv-date {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px
        }

        .badges {
            display: flex;
            gap: 6px;
            padding: 7px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            align-items: center
        }

        .bdg {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px
        }

        .bdg-green {
            background: #dcfce7;
            color: #166534
        }

        .bdg-yellow {
            background: #fef9c3;
            color: #854d0e
        }

        .bdg-blue {
            background: #dbeafe;
            color: #1e40af
        }

        .bdg-red {
            background: #fee2e2;
            color: #991b1b
        }

        .bdg-slate {
            background: #f1f5f9;
            color: #334155
        }

        .info-row {
            display: grid;
            grid-template-columns:1fr 1fr;
            border-bottom: 1px solid #e2e8f0
        }

        .info-col {
            padding: 10px 22px
        }

        .info-col:first-child {
            border-right: 1px solid #e2e8f0
        }

        .info-col h4 {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #94a3b8;
            margin-bottom: 7px
        }

        .info-line {
            display: flex;
            gap: 6px;
            margin-bottom: 3px;
            font-size: 10px;
            line-height: 1.4
        }

        .info-line .k {
            color: #64748b;
            min-width: 72px;
            flex-shrink: 0
        }

        .info-line .v {
            color: #1e293b;
            font-weight: 600
        }

        .items-wrap {
            padding: 10px 22px;
            border-bottom: 1px solid #e2e8f0
        }

        .items-wrap h4 {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #94a3b8;
            margin-bottom: 7px
        }

        table.items {
            width: 100%;
            border-collapse: collapse
        }

        table.items th {
            font-size: 8.5px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 5px 7px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left
        }

        table.items th:not(:first-child) {
            text-align: right
        }

        table.items td {
            padding: 5px 7px;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top
        }

        table.items td:not(:first-child) {
            text-align: right
        }

        table.items tbody tr:last-child td {
            border-bottom: none
        }

        .pname {
            text-align: left!important;
            font-weight: 700;
            color: #1e293b
        }

        .pvar {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 1px
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            padding: 8px 22px;
            border-bottom: 1px solid #e2e8f0
        }

        .totals {
            width: 200px
        }

        .t-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 10px;
            color: #ddd
        }

        .t-row .k {
            color: #94a3b8
        }

        .t-row.grand {
            border-top: 1.5px solid #1e293b;
            margin-top: 4px;
            padding-top: 5px;
            font-size: 11.5px;
            font-weight: 800;
            color: #1e293b
        }

        .t-row.disc {
            color: #0f766e
        }

        .inv-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 22px;
            background: #f8fafc
        }

        .thank {
            font-size: 9.5px;
            color: #64748b;
            font-style: italic
        }

        .gen {
            font-size: 9px;
            color: #94a3b8;
            text-align: right
        }

        /* ── Print styles ── */
        @media print {
            body {
                background: #fff;
                padding: 0
            }

            .toolbar {
                display: none !important
            }

            /* Each .a4-page fits on one A4 sheet exactly */
            .a4-page {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                margin: 0;
                /* Force a page break after every pair */
                page-break-after: always;
                break-after: page;
            }

            .a4-page:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }

            /* Each half fills exactly 50% of the page */
            .inv-half {
                height: 50%;
                overflow: hidden;
            }

            @page {
                size: A4 portrait;
                margin: 6mm 8mm
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <span class="toolbar-info"> {{ count($orders) }} Invoice(s) — {{ ceil(count($orders)/2) }} page(s)</span>
    <button class="btn btn-print" onclick="window.print()"> Print All Invoices</button>
</div>

@php $pairs = array_chunk($orders->all(), 2); @endphp

@foreach($pairs as $pair)
    <div class="a4-page">

        {{-- First invoice on the page --}}
        <div class="inv-half">
            @include('filament.orders._invoice_card', ['order' => $pair[0]])
        </div>

        @if(isset($pair[1]))
            <hr class="inv-divider">
            {{-- Second invoice on the page --}}
            <div class="inv-half">
                @include('filament.orders._invoice_card', ['order' => $pair[1]])
            </div>
        @else
            {{-- Only one invoice — fill bottom half with a blank receipt stub --}}
            <div class="inv-half"
                 style="display:flex;align-items:center;justify-content:center;color:#e2e8f0;font-size:12px;letter-spacing:1px;text-transform:uppercase;border-top:1.5px dashed #cbd5e1;">
                — blank —
            </div>
        @endif

    </div>
@endforeach

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
</body>
</html>
