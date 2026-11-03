<?php

namespace App\Filament\Resources\SellerResource\RelationManagers;

use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn};
use Illuminate\Database\Eloquent\Builder;

class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Customers';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn(Builder $query) => $query
                    ->with('user')
                    ->whereNotNull('user_id')
                    ->select('user_id',
                        \DB::raw('COUNT(*) as total_orders'),
                        \DB::raw('SUM(total_amount) as total_spent'),
                        \DB::raw('MAX(created_at) as last_order_at'),
                        \DB::raw('MIN(created_at) as first_order_at'),
                    )
                    ->groupBy('user_id')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn($record) => $record->user?->phone ?? $record->user?->email ?? ''),

                TextColumn::make('total_orders')
                    ->label('Total Orders')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('first_order_at')
                    ->label('First Order')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('last_order_at')
                    ->label('Last Order')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('total_spent', 'desc')
            ->headerActions([])
            ->paginated([10, 25, 50]);
    }
}
