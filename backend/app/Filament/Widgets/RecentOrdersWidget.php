<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;

class RecentOrdersWidget extends BaseTableWidget
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    protected static string $area = 'orders';
    protected static ?string $heading = ' Recent Orders';
    protected static ?int    $sort    = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::latest()->limit(10))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Order $r) => $r->created_at->diffForHumans()),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (Order $r) => $r->shipping_phone)
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->weight(FontWeight::SemiBold)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'          => '⏳ Pending',
                        'confirmed'        => ' Confirmed',
                        'processing'       => ' Processing',
                        'shipped'          => ' Shipped',
                        'out_for_delivery' => ' Out for Delivery',
                        'delivered'        => ' Delivered',
                        'cancelled'        => ' Cancelled',
                        'returned'         => '↩ Returned',
                        'refunded'         => ' Refunded',
                        default            => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'                        => 'warning',
                        'confirmed', 'processing'        => 'info',
                        'shipped', 'out_for_delivery'    => 'primary',
                        'delivered'                      => 'success',
                        'cancelled', 'returned',
                        'refunded'                       => 'danger',
                        default                          => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'               => 'success',
                        'pending'            => 'warning',
                        'failed', 'refunded' => 'danger',
                        default              => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('shipping_district')
                    ->label('District')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (Order $r) => $r->created_at->format('d M Y, h:i A')),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Order $r) => "/admin/orders/{$r->id}"),
            ])
            ->paginated(false)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('No orders yet')
            ->poll('30s');
    }
}
