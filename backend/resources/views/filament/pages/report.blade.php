<x-filament-panels::page>

    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .rpt-filter-wrap, .fi-header { display: none !important; }
            .fi-main-ctn, .fi-main { margin: 0 !important; max-width: 100% !important; }
            body { background: #fff !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .rpt-hero {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .rpt-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(16,185,129,0.18) 0%, transparent 70%);
            border-radius: 50%;
        }
        .rpt-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 20%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .rpt-title {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            position: relative; z-index: 1;
        }
        .rpt-subtitle {
            color: rgba(255,255,255,0.55);
            font-size: 13px;
            margin-top: 4px;
            position: relative; z-index: 1;
        }
        .rpt-filter-wrap {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 20px 24px;
            margin-top: 20px;
            position: relative; z-index: 1;
        }
        .rpt-filter-btn {
            display: inline-flex; align-items: center; gap: 7px;
            background: #10b981;
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 14px;
            transition: background 0.2s, transform 0.1s;
            letter-spacing: 0.02em;
        }
        .rpt-filter-btn:hover { background: #059669; transform: translateY(-1px); }

        /* KPI Grid */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        @media(max-width:1100px){ .kpi-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:640px){ .kpi-grid { grid-template-columns: 1fr 1fr; } }

        .kpi-card {
            border-radius: 16px;
            padding: 22px 20px 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: default;
        }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.25); }
        .kpi-card .kpi-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(4px);
        }
        .kpi-card .kpi-value {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .kpi-card .kpi-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.65);
            margin-top: 6px;
        }
        .kpi-card .kpi-decor {
            position: absolute;
            bottom: -18px; right: -18px;
            font-size: 72px;
            opacity: 0.08;
            line-height: 1;
            pointer-events: none;
        }
        .kpi-card .kpi-trend {
            position: absolute;
            top: 14px; right: 14px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(255,255,255,0.15);
            padding: 3px 8px;
            border-radius: 20px;
            color: #fff;
        }

        /* Section card */
        .rpt-card {
            background: var(--fi-bg, #fff);
            border-radius: 16px;
            border: 1px solid rgba(150,150,150,0.12);
            box-shadow: 0 2px 16px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .dark .rpt-card { background: #1e293b; border-color: rgba(255,255,255,0.06); }
        .rpt-card-header {
            padding: 18px 24px 16px;
            border-bottom: 1px solid rgba(150,150,150,0.1);
            display: flex; align-items: center; gap: 10px;
        }
        .dark .rpt-card-header { border-color: rgba(255,255,255,0.07); }
        .rpt-card-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .rpt-card-title { font-size: 15px; font-weight: 700; }
        .dark .rpt-card-title { color: #f1f5f9; }
        .rpt-card-body { padding: 0; }

        /* Trend chart row */
        .trend-row {
            display: grid;
            grid-template-columns: 90px 60px 1fr 120px;
            align-items: center;
            gap: 16px;
            padding: 13px 24px;
            border-bottom: 1px solid rgba(150,150,150,0.07);
            transition: background 0.15s;
        }
        .dark .trend-row { border-color: rgba(255,255,255,0.05); }
        .trend-row:hover { background: rgba(16,185,129,0.04); }
        .trend-row:last-child { border-bottom: none; }
        .trend-bar-wrap { height: 8px; background: rgba(150,150,150,0.12); border-radius: 99px; overflow: hidden; }
        .trend-bar { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #10b981, #3b82f6); transition: width 0.6s cubic-bezier(.4,0,.2,1); }
        .trend-month { font-weight: 700; font-size: 13px; }
        .dark .trend-month { color: #cbd5e1; }
        .trend-orders { font-size: 12px; color: #64748b; }
        .trend-rev { font-size: 13px; font-weight: 700; color: #10b981; text-align: right; white-space: nowrap; }

        /* Data tables */
        .rpt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rpt-table thead tr { border-bottom: 2px solid rgba(150,150,150,0.1); }
        .dark .rpt-table thead tr { border-color: rgba(255,255,255,0.08); }
        .rpt-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            white-space: nowrap;
        }
        .rpt-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgba(150,150,150,0.07);
            vertical-align: middle;
        }
        .dark .rpt-table td { border-color: rgba(255,255,255,0.05); }
        .rpt-table tbody tr:last-child td { border-bottom: none; }
        .rpt-table tbody tr:hover td { background: rgba(99,102,241,0.035); }
        .rpt-table tfoot tr td {
            padding: 14px 16px;
            font-weight: 700;
            background: rgba(99,102,241,0.04);
            border-top: 2px solid rgba(150,150,150,0.1);
            border-bottom: none;
        }
        .dark .rpt-table tfoot tr td { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }

        /* Rank badge */
        .rank-num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 24px; height: 24px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            background: rgba(150,150,150,0.1);
            color: #64748b;
        }
        /* margin pill */
        .mpill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .mpill-hi { background: #dcfce7; color: #15803d; }
        .mpill-mid { background: #fef9c3; color: #a16207; }
        .mpill-lo { background: #fee2e2; color: #b91c1c; }
        .dark .mpill-hi { background: rgba(21,128,61,0.2); color: #4ade80; }
        .dark .mpill-mid { background: rgba(161,98,7,0.2); color: #fcd34d; }
        .dark .mpill-lo { background: rgba(185,28,28,0.2); color: #f87171; }

        /* Status chip */
        .status-chip {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Summary banner */
        .rpt-summary {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            border-radius: 16px;
            padding: 28px 32px;
            display: grid;
            grid-template-columns: repeat(4,1fr);
            gap: 24px;
            margin-bottom: 8px;
            position: relative;
            overflow: hidden;
        }
        .rpt-summary::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .rpt-summary-item { text-align: center; position: relative; z-index: 1; }
        .rpt-summary-val { font-size: 24px; font-weight: 900; letter-spacing: -1px; }
        .rpt-summary-lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.4); margin-top: 5px; }
        .rpt-summary-meta { text-align: center; color: rgba(255,255,255,0.3); font-size: 12px; margin-top: 16px; position: relative; z-index: 1; grid-column: 1/-1; }
        @media(max-width:768px){ .rpt-summary { grid-template-columns: 1fr 1fr; } }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        @media(max-width:1024px){ .two-col { grid-template-columns: 1fr; } }

        .seller-avatar {
            width: 32px; height: 32px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: inline-flex; align-items: center; justify-content: center;
            color: #fff; font-size: 13px; font-weight: 800;
            margin-right: 8px;
            flex-shrink: 0;
            vertical-align: middle;
        }
        .product-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #10b981;
            margin-right: 6px;
            flex-shrink: 0;
            vertical-align: middle;
        }
    </style>

    {{-- Hero Header with Filter --}}
    <div class="rpt-hero">
        <div style="display:flex;align-items:center;gap:14px;position:relative;z-index:1;">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#10b981,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;"></div>
            <div>
                <div class="rpt-title">Profit & Revenue Report</div>
                <div class="rpt-subtitle">Real-time financial analytics · Period {{ $date_from }} → {{ $date_to }} · Updated {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <div class="rpt-filter-wrap">
            <div style="color:rgba(255,255,255,0.7);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">
                 Filter Period & Seller
            </div>
            {{ $this->form }}
            <button wire:click="$refresh" class="rpt-filter-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Apply & Refresh
            </button>
            <span class="rpt-export-row" style="display:inline-flex;gap:8px;flex-wrap:wrap;margin-left:10px;">
                <button type="button" wire:click="exportSummary" class="rpt-filter-btn" style="background:#2563eb">Export summary CSV</button>
                <button type="button" wire:click="exportProducts" class="rpt-filter-btn" style="background:#2563eb">Export products CSV</button>
                <button type="button" wire:click="exportSellers" class="rpt-filter-btn" style="background:#2563eb">Export sellers CSV</button>
                <button type="button" onclick="window.print()" class="rpt-filter-btn" style="background:#475569">Print / Save as PDF</button>
            </span>
        </div>
    </div>

    @php
        $stats = $this->getStats();
        $topProducts = $this->getTopProducts();
        $topSellers = $this->getTopSellers();
        $monthlyTrend = $this->getMonthlyTrend();
    @endphp

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        @php
            $kpis = [
                ['label'=>'Total Revenue',        'value'=>'BDT '.number_format($stats['totalRevenue'],2),     'icon'=>'', 'bg'=>'linear-gradient(135deg,#059669,#10b981)', 'trend'=>'Gross sales (GMV)'],
                ['label'=>'Net Platform Income',  'value'=>'BDT '.number_format($stats['totalProfit'],2),      'icon'=>'', 'bg'=>'linear-gradient(135deg,#2563eb,#3b82f6)', 'trend'=>'Commission + own profit + shipping'],
                ['label'=>'Income Margin',        'value'=>$stats['margin'].'%',                               'icon'=>'', 'bg'=>'linear-gradient(135deg,#7c3aed,#8b5cf6)', 'trend'=>'Income ÷ revenue'],
                ['label'=>'Total Orders',         'value'=>number_format($stats['totalOrders']),               'icon'=>'', 'bg'=>'linear-gradient(135deg,#d97706,#f59e0b)', 'trend'=>'Excl. cancelled / returned'],
                ['label'=>'Commission Earned',    'value'=>'BDT '.number_format($stats['commissionEarned'],2), 'icon'=>'', 'bg'=>'linear-gradient(135deg,#0d9488,#14b8a6)', 'trend'=>$stats['deliveredOrders'].' delivered'],
                ['label'=>'Commission Pending',   'value'=>'BDT '.number_format($stats['commissionPending'],2),'icon'=>'', 'bg'=>'linear-gradient(135deg,#be123c,#f43f5e)', 'trend'=>'Until delivered'],
                ['label'=>'Own-store Profit',     'value'=>'BDT '.number_format($stats['ownProfit'],2),        'icon'=>'', 'bg'=>'linear-gradient(135deg,#0369a1,#0ea5e9)', 'trend'=>'Cost BDT '.number_format($stats['totalCost'],0)],
                ['label'=>'Shipping Income',      'value'=>'BDT '.number_format($stats['totalShipping'],2),    'icon'=>'', 'bg'=>'linear-gradient(135deg,#6d28d9,#a855f7)', 'trend'=>'Delivery fees'],
                ['label'=>'Seller Payouts',       'value'=>'BDT '.number_format($stats['sellerPayoutTotal'],2),'icon'=>'', 'bg'=>'linear-gradient(135deg,#475569,#64748b)', 'trend'=>'Delivered orders'],
                ['label'=>'Coupon Discounts',     'value'=>'BDT '.number_format($stats['totalCouponDiscount'],2),'icon'=>'','bg'=>'linear-gradient(135deg,#b45309,#d97706)', 'trend'=>'Promo savings'],
            ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="kpi-card" style="background:{{ $kpi['bg'] }};">
                <div class="kpi-icon">{{ $kpi['icon'] }}</div>
                <div class="kpi-value">{{ $kpi['value'] }}</div>
                <div class="kpi-label">{{ $kpi['label'] }}</div>
                <div class="kpi-trend">{{ $kpi['trend'] }}</div>
                <div class="kpi-decor">{{ $kpi['icon'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Monthly Trend --}}
    @if(!empty($monthlyTrend))
        <div class="rpt-card">
            <div class="rpt-card-header">
                <div class="rpt-card-icon" style="background:linear-gradient(135deg,#0ea5e9,#6366f1);"></div>
                <div>
                    <div class="rpt-card-title">Monthly Revenue Trend</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Last 6 months performance</div>
                </div>
            </div>
            <div class="rpt-card-body" style="padding:8px 0;">
                {{-- Header row --}}
                <div class="trend-row" style="padding-top:8px;padding-bottom:8px;border-bottom:1px solid rgba(150,150,150,0.12);">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Month</div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Orders</div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Revenue Bar</div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;text-align:right;">Amount</div>
                </div>
                @php $maxRev = collect($monthlyTrend)->max('revenue') ?: 1; @endphp
                @foreach($monthlyTrend as $i => $row)
                    @php $pct = ($row['revenue'] / $maxRev) * 100; @endphp
                    <div class="trend-row">
                        <div class="trend-month">{{ \Carbon\Carbon::createFromFormat('Y-m',$row['month'])->format('M Y') }}</div>
                        <div class="trend-orders">{{ number_format($row['orders']) }} orders</div>
                        <div>
                            <div class="trend-bar-wrap">
                                <div class="trend-bar" style="width:{{ $pct }}%;"></div>
                            </div>
                        </div>
                        <div class="trend-rev">BDT {{ number_format($row['revenue'],0) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Products + Sellers --}}
    <div class="two-col">

        {{-- Top Products --}}
        <div class="rpt-card">
            <div class="rpt-card-header">
                <div class="rpt-card-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);"></div>
                <div>
                    <div class="rpt-card-title">Top Products by Revenue</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Ranked by total sales value</div>
                </div>
            </div>
            <div class="rpt-card-body">
                @if($topProducts->isEmpty())
                    <div style="text-align:center;padding:48px 24px;color:#94a3b8;">
                        <div style="font-size:40px;margin-bottom:12px;"></div>
                        <div style="font-weight:600;">No product data found</div>
                        <div style="font-size:12px;margin-top:4px;">Try adjusting the date range</div>
                    </div>
                @else
                    <table class="rpt-table">
                        <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Revenue</th>
                            <th>Platform profit</th>
                            <th>Margin</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topProducts as $i => $p)
                            <tr>
                                <td>
                                    @if($i===0)<span style="font-size:18px;"></span>
                                    @elseif($i===1)<span style="font-size:18px;"></span>
                                    @elseif($i===2)<span style="font-size:18px;"></span>
                                    @else<span class="rank-num">{{ $i+1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:600;font-size:13px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $p['name'] }}">
                                        <span class="product-dot"></span>{{ $p['name'] }}
                                    </div>
                                    <div style="font-size:11px;color:#64748b;margin-top:2px;padding-left:14px;">{{ $p['seller'] }}</div>
                                </td>
                                <td style="font-weight:700;">{{ $p['qty'] }}</td>
                                <td style="color:#10b981;font-weight:700;">BDT {{ number_format($p['revenue'],0) }}</td>
                                <td style="font-weight:700;color:{{ $p['profit']>=0 ? '#3b82f6' : '#ef4444' }};">
                                    {{ $p['profit']>=0 ? '+' : '' }}BDT {{ number_format($p['profit'],0) }}
                                </td>
                                <td>
                        <span class="mpill {{ $p['margin']>=30 ? 'mpill-hi' : ($p['margin']>=10 ? 'mpill-mid' : 'mpill-lo') }}">
                            {{ $p['margin'] }}%
                        </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="2" style="color:#64748b;font-size:12px;">{{ $topProducts->count() }} products shown</td>
                            <td style="font-weight:800;">{{ $topProducts->sum('qty') }}</td>
                            <td style="color:#10b981;font-weight:800;">BDT {{ number_format($topProducts->sum('revenue'),0) }}</td>
                            <td style="color:#3b82f6;font-weight:800;">BDT {{ number_format($topProducts->sum('profit'),0) }}</td>
                            <td>
                                @php $avgMargin = $topProducts->count() > 0 ? round($topProducts->avg('margin'),1) : 0; @endphp
                                <span class="mpill {{ $avgMargin>=30 ? 'mpill-hi' : ($avgMargin>=10 ? 'mpill-mid' : 'mpill-lo') }}">
                                Avg {{ $avgMargin }}%
                            </span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>

        {{-- Top Sellers --}}
        <div class="rpt-card">
            <div class="rpt-card-header">
                <div class="rpt-card-icon" style="background:linear-gradient(135deg,#7c3aed,#a855f7);"></div>
                <div>
                    <div class="rpt-card-title">Top Sellers by Revenue</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Commission & payout breakdown</div>
                </div>
            </div>
            <div class="rpt-card-body">
                @if($topSellers->isEmpty())
                    <div style="text-align:center;padding:48px 24px;color:#94a3b8;">
                        <div style="font-size:40px;margin-bottom:12px;"></div>
                        <div style="font-weight:600;">No seller data found</div>
                        <div style="font-size:12px;margin-top:4px;">Try adjusting the date range</div>
                    </div>
                @else
                    <table class="rpt-table">
                        <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th>Seller</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Commission (earned)</th>
                            <th>Commission (pending)</th>
                            <th>Payout</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topSellers as $i => $s)
                            @php $initial = strtoupper(substr($s['name'],0,1)); @endphp
                            <tr>
                                <td>
                                    @if($i===0)<span style="font-size:18px;"></span>
                                    @elseif($i===1)<span style="font-size:18px;"></span>
                                    @elseif($i===2)<span style="font-size:18px;"></span>
                                    @else<span class="rank-num">{{ $i+1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="seller-avatar">{{ $initial }}</span>
                                    <span style="font-weight:600;font-size:13px;">{{ $s['name'] }}</span>
                                </td>
                                <td>
                        <span style="display:inline-flex;align-items:center;justify-content:center;background:rgba(99,102,241,0.1);color:#6366f1;border-radius:8px;padding:2px 10px;font-weight:700;font-size:12px;">
                            {{ number_format($s['orders']) }}
                        </span>
                                </td>
                                <td style="color:#10b981;font-weight:700;">BDT {{ number_format($s['revenue'],0) }}</td>
                                <td style="color:#3b82f6;font-weight:700;">BDT {{ number_format($s['commission'],0) }}</td>
                                <td style="color:#f59e0b;font-weight:700;">BDT {{ number_format($s['pending'],0) }}</td>
                                <td style="color:#a855f7;font-weight:700;">BDT {{ number_format($s['payout'],0) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="2" style="color:#64748b;font-size:12px;">{{ $topSellers->count() }} sellers</td>
                            <td style="font-weight:800;">{{ $topSellers->sum('orders') }}</td>
                            <td style="color:#10b981;font-weight:800;">BDT {{ number_format($topSellers->sum('revenue'),0) }}</td>
                            <td style="color:#3b82f6;font-weight:800;">BDT {{ number_format($topSellers->sum('commission'),0) }}</td>
                            <td style="color:#f59e0b;font-weight:800;">BDT {{ number_format($topSellers->sum('pending'),0) }}</td>
                            <td style="color:#a855f7;font-weight:800;">BDT {{ number_format($topSellers->sum('payout'),0) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>

    </div>

    {{-- Summary Banner --}}
    <div class="rpt-summary">
        <div class="rpt-summary-item">
            <div class="rpt-summary-val" style="color:#4ade80;">BDT {{ number_format($stats['totalRevenue'],0) }}</div>
            <div class="rpt-summary-lbl">Total Revenue</div>
        </div>
        <div class="rpt-summary-item">
            <div class="rpt-summary-val" style="color:#60a5fa;">BDT {{ number_format($stats['totalProfit'],0) }}</div>
            <div class="rpt-summary-lbl">Net Platform Income</div>
        </div>
        <div class="rpt-summary-item">
            <div class="rpt-summary-val" style="color:#c084fc;">{{ $stats['margin'] }}%</div>
            <div class="rpt-summary-lbl">Income Margin</div>
        </div>
        <div class="rpt-summary-item">
            <div class="rpt-summary-val" style="color:#fbbf24;">{{ number_format($stats['totalOrders']) }}</div>
            <div class="rpt-summary-lbl">Total Orders</div>
        </div>
        <div class="rpt-summary-meta">
            Report period: <strong style="color:rgba(255,255,255,0.6);">{{ $date_from }}</strong>
            &nbsp;→&nbsp;
            <strong style="color:rgba(255,255,255,0.6);">{{ $date_to }}</strong>
            &nbsp;·&nbsp; Generated {{ now()->format('d M Y, H:i') }}
        </div>
    </div>

</x-filament-panels::page>