<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\{Order, OrderItem, Product, Seller};
use Illuminate\Support\Facades\DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\{DatePicker, Select, Grid as FormGrid};
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ReportPage extends Page implements HasForms
{
    use \App\Filament\Concerns\ChecksAdminAreaPage;
    protected static string $area = 'reports';
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Reports & Analytics';
    protected static ?string $navigationLabel = 'Profit & Revenue Report';
    protected static ?string $title = 'Profit & Revenue Report';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.report';

    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $seller_id = null;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to   = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FormGrid::make(3)->schema([
                    DatePicker::make('date_from')->label('From Date')->default(now()->startOfMonth()),
                    DatePicker::make('date_to')->label('To Date')->default(now()),
                    Select::make('seller_id')
                        ->label('Filter by Seller')
                        ->options(Seller::pluck('shop_name', 'id')->prepend('All Sellers', ''))
                        ->placeholder('All Sellers')
                        ->searchable(),
                ]),
            ]);
    }

    public function getStats(): array
    {
        $from = $this->date_from ? Carbon::parse($this->date_from)->startOfDay() : now()->startOfMonth();
        $to   = $this->date_to   ? Carbon::parse($this->date_to)->endOfDay()   : now()->endOfDay();

        $orderQuery = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'returned']);

        if ($this->seller_id) {
            $orderQuery->where('seller_id', $this->seller_id);
        }

        $orders       = $orderQuery->get();
        $totalRevenue = (float) $orders->sum('total_amount');
        $totalOrders  = $orders->count();
        $deliveredOrders = $orders->where('status', 'delivered')->count();

        // Marketplace money model:
        //  • seller orders  → the platform earns the COMMISSION (earned once delivered & not refunded, pending before that)
        //  • own-store orders (no seller) → the platform earns revenue − product cost
        //  • shipping charges are platform income (never part of the seller payout)
        $sellerOrders = $orders->whereNotNull('seller_id');
        $ownOrders    = $orders->whereNull('seller_id');
        $isEarned     = fn ($o) => $o->status === 'delivered' && ($o->payment_status ?? '') !== 'refunded';

        $commissionEarned  = (float) $sellerOrders->filter($isEarned)->sum('commission_amount');
        $commissionPending = (float) $sellerOrders->reject($isEarned)->sum('commission_amount');
        $sellerPayoutTotal = (float) $sellerOrders->filter($isEarned)->sum('seller_payout');

        $ownItems   = OrderItem::whereIn('order_id', $ownOrders->pluck('id'))->with('product:id,cost_price')->get();
        $ownRevenue = (float) $ownItems->sum('total_price');
        $totalCost  = (float) $ownItems->sum(fn ($i) => ($i->product?->cost_price ?? 0) * $i->quantity);
        $ownProfit  = $ownRevenue - $totalCost;

        $totalShipping       = (float) $orders->sum('shipping_charge');
        $totalCouponDiscount = (float) $orders->sum('coupon_discount');

        // Net platform income = all commission (earned + pending) + own-store profit + shipping income
        $totalProfit = $commissionEarned + $commissionPending + $ownProfit + $totalShipping;
        $margin      = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        return compact(
            'totalRevenue', 'totalOrders', 'deliveredOrders',
            'totalCost', 'totalProfit', 'margin',
            'totalShipping', 'totalCouponDiscount',
            'commissionEarned', 'commissionPending', 'sellerPayoutTotal', 'ownProfit'
        );
    }

    public function getTopProducts(): \Illuminate\Support\Collection
    {
        $from = $this->date_from ? Carbon::parse($this->date_from)->startOfDay() : now()->startOfMonth();
        $to   = $this->date_to   ? Carbon::parse($this->date_to)->endOfDay()   : now()->endOfDay();

        $orderIds = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->when($this->seller_id, fn ($q) => $q->where('seller_id', $this->seller_id))
            ->pluck('id');

        // Per product: seller products earn their share of the order commission (pro-rata by line value),
        // own-store products earn revenue − cost_price.
        $items = OrderItem::whereIn('order_id', $orderIds)
            ->with('order:id,seller_id,subtotal,commission_amount', 'product:id,name,price,cost_price,seller_id', 'product.seller:id,shop_name')
            ->get();

        return $items->groupBy(fn ($i) => $i->product_id . '|' . $i->product_name)->map(function ($rows) {
            $first   = $rows->first();
            $revenue = (float) $rows->sum('total_price');
            $qty     = (int) $rows->sum('quantity');
            $profit  = 0.0; $cost = 0.0;
            foreach ($rows as $it) {
                $o = $it->order;
                if ($o && $o->seller_id) {
                    $profit += (float) $o->subtotal > 0 ? (float) $o->commission_amount * ((float) $it->total_price / (float) $o->subtotal) : 0;
                } else {
                    $c = ($it->product?->cost_price ?? 0) * $it->quantity;
                    $cost += $c; $profit += (float) $it->total_price - $c;
                }
            }
            return [
                'name'       => $first->product_name,
                'seller'     => $first->product?->seller?->shop_name ?? 'Admin Store',
                'qty'        => $qty,
                'revenue'    => $revenue,
                'cost'       => $cost,
                'profit'     => round($profit, 2),
                'margin'     => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
                'unit_price' => $first->product?->price ?? 0,
                'cost_price' => $first->product?->cost_price ?? 0,
            ];
        })->sortByDesc('revenue')->take(15)->values();
    }

    public function getTopSellers(): \Illuminate\Support\Collection
    {
        $from = $this->date_from ? Carbon::parse($this->date_from)->startOfDay() : now()->startOfMonth();
        $to   = $this->date_to   ? Carbon::parse($this->date_to)->endOfDay()   : now()->endOfDay();

        return Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->when($this->seller_id, fn ($q) => $q->where('seller_id', $this->seller_id))
            ->with('seller:id,shop_name,commission_rate')
            ->select('seller_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_revenue'),
                // Commission / payout are only "earned" once delivered and not refunded
                DB::raw("SUM(CASE WHEN status = 'delivered' AND COALESCE(payment_status, '') != 'refunded' THEN commission_amount ELSE 0 END) as total_commission"),
                DB::raw("SUM(CASE WHEN status = 'delivered' AND COALESCE(payment_status, '') != 'refunded' THEN seller_payout ELSE 0 END) as total_payout"),
                DB::raw("SUM(CASE WHEN status = 'delivered' AND COALESCE(payment_status, '') != 'refunded' THEN 0 ELSE commission_amount END) as pending_commission")
            )
            ->groupBy('seller_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($o) {
                return [
                    'name'       => $o->seller_id ? ($o->seller?->shop_name ?? 'Deleted seller') : 'Admin Store (own products)',
                    'orders'     => $o->order_count,
                    'revenue'    => $o->total_revenue,
                    'commission' => $o->total_commission,
                    'pending'    => $o->pending_commission,
                    'payout'     => $o->total_payout,
                    'profit'     => $o->total_commission, // platform keeps commission
                ];
            });
    }

    public function getMonthlyTrend(): array
    {
        // DATE_FORMAT() exists only in MySQL/MariaDB; SQLite uses strftime().
        $monthExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        return Order::selectRaw("{$monthExpr} as month, SUM(total_amount) as revenue, COUNT(*) as orders")
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->when($this->seller_id, fn ($q) => $q->where('seller_id', $this->seller_id))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function csvDownload(string $name, array $header, iterable $rows)
    {
        $file = $name . '-' . ($this->date_from ?: 'start') . '_to_' . ($this->date_to ?: 'today') . '.csv';
        $cell = function ($v) {
            $v = (string) ($v ?? '');
            return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !is_numeric($v)) ? "'" . $v : $v;
        };
        return response()->streamDownload(function () use ($header, $rows, $cell) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $r) fputcsv($out, array_map($cell, $r));
            fclose($out);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSummary()
    {
        $s = $this->getStats();
        return $this->csvDownload('profit-report-summary', ['Metric', 'Value'], [
            ['From', $this->date_from], ['To', $this->date_to],
            ['Seller', $this->seller_id ? (Seller::find($this->seller_id)?->shop_name ?? $this->seller_id) : 'All'],
            ['Total revenue', $s['totalRevenue']], ['Own-store cost', $s['totalCost']], ['Net platform income', $s['totalProfit']],
            ['Commission earned', $s['commissionEarned']], ['Commission pending', $s['commissionPending']], ['Own-store profit', $s['ownProfit']],
            ['Margin %', $s['margin']], ['Orders', $s['totalOrders']], ['Delivered', $s['deliveredOrders']],
            ['Shipping income', $s['totalShipping']], ['Coupon discounts', $s['totalCouponDiscount']],
        ]);
    }

    public function exportProducts()
    {
        return $this->csvDownload('profit-report-products', ['Product', 'Seller', 'Qty', 'Revenue', 'Own cost', 'Platform profit', 'Margin %'],
            $this->getTopProducts()->map(fn($p) => [$p['name'], $p['seller'], $p['qty'], $p['revenue'], $p['cost'], $p['profit'], $p['margin']]));
    }

    public function exportSellers()
    {
        return $this->csvDownload('profit-report-sellers', ['Seller', 'Orders', 'Revenue', 'Commission earned', 'Commission pending', 'Seller payout'],
            $this->getTopSellers()->map(fn($s) => [$s['name'], $s['orders'], $s['revenue'], $s['commission'], $s['pending'], $s['payout']]));
    }

    public function applyFilter(): void
    {
        // Livewire reactive — just trigger re-render
    }
}