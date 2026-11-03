<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusDonutWidget extends ChartWidget
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    protected static string $area = 'orders';
    protected static ?string $heading = ' Order Status Distribution';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $statuses = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $map = [
            'pending'          => ['⏳ Pending',           '#F59E0B'],
            'confirmed'        => [' Confirmed',         '#3B82F6'],
            'processing'       => [' Processing',        '#8B5CF6'],
            'shipped'          => [' Shipped',           '#06B6D4'],
            'out_for_delivery' => [' Out for Delivery',  '#0EA5E9'],
            'delivered'        => [' Delivered',         '#10B981'],
            'cancelled'        => [' Cancelled',         '#EF4444'],
            'returned'         => ['↩ Returned',          '#F97316'],
            'refunded'         => [' Refunded',          '#6B7280'],
        ];

        [$labels, $data, $colors] = [[], [], []];

        foreach ($map as $status => [$label, $color]) {
            if (!empty($statuses[$status])) {
                $labels[] = $label . ' (' . $statuses[$status] . ')';
                $data[] = $statuses[$status];
                $colors[] = $color;
            }
        }

        return [
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 2,
                'hoverOffset' => 8,
            ]],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],

            'cutout' => '65%',
        ];
    }
}
