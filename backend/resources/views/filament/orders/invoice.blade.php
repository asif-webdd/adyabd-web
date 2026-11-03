<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->order_number }}</title>
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

        .toolbar {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            max-width: 680px;
            margin: 0 auto 14px
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
            cursor: pointer;
            text-decoration: none
        }

        .btn-print {
            background: #1e293b;
            color: #fff
        }

        .btn-dl {
            background: #0f766e;
            color: #fff
        }

        .page {
            max-width: 680px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .10);
            overflow: hidden
        }

        /* ── Header ── */
        .inv-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 18px 24px 14px;
            border-bottom: 2px solid #1e293b
        }

        .shop-logo { max-height: 46px; max-width: 220px; width: auto; height: auto; object-fit: contain; display: block; }
        .shop-name {
            font-size: 16px;
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
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #1e293b;
            text-transform: uppercase
        }

        .inv-num {
            font-size: 12px;
            font-weight: 700;
            color: #0f766e;
            margin-top: 3px
        }

        .inv-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px
        }

        /* ── Badges ── */
        .badges {
            display: flex;
            gap: 8px;
            padding: 8px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            align-items: center
        }

        .bdg {
            padding: 2px 9px;
            border-radius: 12px;
            font-size: 10px;
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

        /* ── Two-col info ── */
        .info-row {
            display: grid;
            grid-template-columns:1fr 1fr;
            border-bottom: 1px solid #e2e8f0
        }

        .info-col {
            padding: 12px 24px
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
            margin-bottom: 8px
        }

        .info-line {
            display: flex;
            gap: 6px;
            margin-bottom: 4px;
            font-size: 10.5px;
            line-height: 1.4
        }

        .info-line .k {
            color: #64748b;
            min-width: 80px;
            flex-shrink: 0
        }

        .info-line .v {
            color: #1e293b;
            font-weight: 600
        }

        /* ── Items table ── */
        .items-wrap {
            padding: 12px 24px;
            border-bottom: 1px solid #e2e8f0
        }

        .items-wrap h4 {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #94a3b8;
            margin-bottom: 8px
        }

        table.items {
            width: 100%;
            border-collapse: collapse
        }

        table.items th {
            font-size: 9px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left
        }

        table.items th:not(:first-child) {
            text-align: right
        }

        table.items td {
            padding: 7px 8px;
            font-size: 10.5px;
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
            font-size: 9.5px;
            color: #94a3b8;
            margin-top: 1px
        }

        /* ── Totals ── */
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
            padding: 10px 24px;
            border-bottom: 1px solid #e2e8f0
        }

        .totals {
            width: 220px
        }

        .t-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 10.5px;
            color: #ddd
        }

        .t-row .k {
            color: #94a3b8
        }

        .t-row.grand {
            border-top: 1.5px solid #1e293b;
            margin-top: 5px;
            padding-top: 7px;
            font-size: 12px;
            font-weight: 800;
            color: #1e293b
        }

        .t-row.disc {
            color: #0f766e
        }

        /* ── Footer ── */
        .inv-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 24px;
            background: #f8fafc
        }

        .thank {
            font-size: 10px;
            color: #64748b;
            font-style: italic
        }

        .gen {
            font-size: 9px;
            color: #94a3b8;
            text-align: right
        }

        /* ── Print ── */
        @media print {
            body {
                background: #fff;
                padding: 0
            }

            .toolbar {
                display: none !important
            }

            .page {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                margin: 0
            }

            @page {
                size: A4;
                margin: 8mm
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button class="btn btn-print" onclick="window.print()"> Print</button>
    <button class="btn btn-dl" onclick="dlPDF()"> Download PDF</button>
</div>

<div class="page">
    @include('filament.orders._invoice_card', ['order' => $order])
</div>

<script>
    if (new URLSearchParams(location.search).get('print') === '1')
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));

    function dlPDF() {
        var t = document.querySelector('.toolbar');
        t.style.display = 'none';
        var old = document.title;
        document.title = 'Invoice-{{ $order->order_number }}';
        window.print();
        t.style.display = '';
        document.title = old;
    }
</script>
</body>
</html>
