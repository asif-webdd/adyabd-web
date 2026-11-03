<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    use \App\Filament\Concerns\UsesDashboardRange;
    protected static string $area = 'finance';
    protected static ?string $heading = ' Daily Revenue & Orders';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Order::whereBetween('created_at', [$this->rangeStart(), $this->rangeEnd()])
            ->selectRaw("
                DATE(created_at) as date,
                SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) as revenue,
                COUNT(*) as orders
            ")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue (BDT )',
                    'data'            => $data->pluck('revenue')->map(fn($v) => round($v))->toArray(),
                    'borderColor'     => '#006A4E',
                    'backgroundColor' => 'rgba(0,106,78,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Orders',
                    'data'            => $data->pluck('orders')->toArray(),
                    'borderColor'     => '#F42A41',
                    'backgroundColor' => 'rgba(244,42,65,0.08)',
                    'fill'            => false,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y1',
                ],
            ],

            'labels' => $data->pluck('date')
                ->map(fn($d) => date('d M', strtotime($d)))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'position'    => 'left',
                    'beginAtZero' => true,
                    'title'       => [
                        'display' => true,
                        'text'    => 'Revenue (BDT )',
                    ],
                ],

                'y1' => [
                    'position'    => 'right',
                    'beginAtZero' => true,
                    'title'       => [
                        'display' => true,
                        'text'    => 'Orders',
                    ],
                    'grid'        => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
