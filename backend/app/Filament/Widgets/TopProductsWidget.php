<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;

class TopProductsWidget extends BaseTableWidget implements HasTable
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    use \App\Filament\Concerns\UsesDashboardRange;
    protected static string $area = 'catalog';
    use InteractsWithTable;

    protected static ?string $heading = 'Top Selling Products (selected date range)';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::withCount([
                    'orderItems as monthly_sales' => fn ($q) => $q->whereHas(
                        'order',
                        fn ($oq) => $oq
                            ->where('status', 'delivered')
                            ->whereBetween('created_at', [$this->rangeStart(), $this->rangeEnd()])
                    ),
                ])
                    ->where('is_active', true)
                    ->orderByDesc('monthly_sales')
                    ->limit(8)
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->limit(35)
                    ->description(fn (Product $record) => $record->seller?->shop_name),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('BDT'),

                TextColumn::make('monthly_sales')
                    ->label('In range')
                    ->badge()
                    ->color('success'),

                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (string $state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                TextColumn::make('rating')->label('Rating')
                    ->formatStateUsing(fn (string $state) => $state > 0 ? " {$state}" : '—'),
            ])

            ->paginated(false);
    }
}
