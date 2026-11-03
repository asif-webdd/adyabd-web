<?php

namespace App\Filament\Widgets;

use App\Models\{Order, User, Product};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    use \App\Filament\Concerns\UsesDashboardRange;
    protected static string $area = 'orders';
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function getStats(): array
    {
        [$from, $to] = [$this->rangeStart(), $this->rangeEnd()];
        $rangeLabel  = $this->rangeLabel();

        $todayOrders = Order::whereBetween('created_at', [$from, $to])->count();

        $todayRevenue = Order::whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->sum('total_amount');


        // Platform commission actually earned: delivered orders only, minus refunded ones
        $commission = Order::whereBetween('created_at', [$from, $to])
            ->where('status', 'delivered')
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'refunded'))
            ->sum('commission_amount');


        $pending = Order::where('status', 'pending')->count();

        $newUsers = User::whereBetween('created_at', [$from, $to])->count();

        $blocked = User::where('is_blocked', true)->count();

        $lowStock = Product::where('is_active', true)
            ->where('quantity', '<=', 5)
            ->where('quantity', '>', 0)
            ->count();

        $last7Orders = Order::selectRaw('COUNT(*) as c')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('c')
            ->toArray();

        $last7Revenue = Order::where('status', 'delivered')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('SUM(total_amount) as t')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('t')
            ->toArray();

        return [
            Stat::make('Orders', number_format($todayOrders))
                ->description($rangeLabel)
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->chart($last7Orders),

            Stat::make('Revenue', 'BDT ' . number_format($todayRevenue, 0))
                ->description('Delivered orders · ' . $rangeLabel)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($last7Revenue),


            Stat::make('Commission Earned', 'BDT ' . number_format($commission, 0))
                ->description('Delivered orders · ' . $rangeLabel)
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),
        

            Stat::make('Pending Orders', number_format($pending))
                ->description('Needs processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 20 ? 'danger' : ($pending > 10 ? 'warning' : 'success')),

            Stat::make('New Customers', number_format($newUsers))
                ->description($rangeLabel . ' · Total: ' . number_format(User::count()))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Low Stock Products', number_format($lowStock))
                ->description('Running out of stock')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($lowStock > 10 ? 'danger' : 'warning'),

            Stat::make('Blocked Customers', number_format($blocked))
                ->description('IP Blocks: ' . \App\Models\IpBlock::where('is_active', true)->count())
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($blocked > 0 ? 'danger' : 'success'),
        ];
    }
}
