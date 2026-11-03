<?php

namespace App\Filament\Widgets;

use App\Models\CourierMerchant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;

class CourierSummaryWidget extends BaseTableWidget implements HasTable
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    protected static string $area = 'orders';
    use InteractsWithTable;

    protected static ?string $heading = ' Courier Summary';

    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CourierMerchant::withCount([
                    'orders as today_count'   => fn($q) => $q->whereDate('created_at', today()),
                    'orders as pending_count' => fn($q) => $q->where('courier_status', 'booked'),
                ])->where('is_active', true)->orderByDesc('total_deliveries')
            )
            ->columns([
                TextColumn::make('name')->label('Courier')->description(fn(CourierMerchant $record) => $record->is_default ? ' Default' : null),
                TextColumn::make('today_count')->label('Today')->badge()->color('info'),
                TextColumn::make('pending_count')->label('Pending Pickup')->badge()->color('warning'),
                TextColumn::make('total_deliveries')->label('Total Deliveries')->badge()->color('primary'),
                TextColumn::make('success_rate')->label('Success Rate')->suffix('%')->badge()
                    ->color(fn(string $state) => $state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger')),
                TextColumn::make('base_charge')->label('Charge')->money('BDT'),
            ])->paginated(false);
    }
}
