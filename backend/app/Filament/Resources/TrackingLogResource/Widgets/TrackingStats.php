<?php

namespace App\Filament\Resources\TrackingLogResource\Widgets;

use App\Models\TrackingLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackingStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $since = now()->subDay();

        $base      = TrackingLog::where('created_at', '>=', $since);
        $total     = (clone $base)->count();
        $failed    = (clone $base)->where('has_error', true)->count();
        $purchases = (clone $base)->where('event_name', 'Purchase');
        $purchaseCount  = (clone $purchases)->count();
        $serverPurchase = (clone $purchases)->where('source', 'server')->count();
        $revenue        = (float) (clone $purchases)->sum('value');
        $rate           = $total > 0 ? round(100 * ($total - $failed) / $total, 1) : 100;

        return [
            Stat::make('Events (24h)', number_format($total))
                ->description('Sent to Meta / GA4 by the Worker'),
            Stat::make('Purchases (24h)', number_format($purchaseCount))
                ->description($serverPurchase . ' from server')
                ->color('success'),
            Stat::make('Purchase value (24h)', '৳' . number_format($revenue, 0)),
            Stat::make('Success rate (24h)', $rate . '%')
                ->description($failed . ' failed')
                ->color($failed > 0 ? 'danger' : 'success'),
        ];
    }
}
