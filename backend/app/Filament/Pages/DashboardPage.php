<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\{DatePicker, Section};
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardPage extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Date range')->description('Applies to the order, revenue, customer and product widgets below.')->schema([
                DatePicker::make('startDate')->label('From')->default(now()->subDays(29)->toDateString())->maxDate(now()),
                DatePicker::make('endDate')->label('To')->default(now()->toDateString())->maxDate(now()),
            ])->columns(2)->compact(),
        ]);
    }

    protected static ?string $navigationIcon = "heroicon-o-home";
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Market Dashboard';
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            //\App\Filament\Widgets\OrderStatusDonutWidget::class,
            \App\Filament\Widgets\RecentOrdersWidget::class,
            \App\Filament\Widgets\TopProductsWidget::class,
            \App\Filament\Widgets\BlockedUsersWidget::class,
            \App\Filament\Widgets\CourierSummaryWidget::class,
            \App\Filament\Widgets\SmsLogWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

}
