<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Illuminate\Database\Eloquent\Builder;

class OrdersRelationManager extends RelationManager
{
    protected static string  $relationship = 'orders';
    protected static ?string $title        = ' Orders';
    protected static bool    $isLazy       = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Order # copied')
                    ->description(fn($record) => $record->created_at->format('d M Y, h:i A'))
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn($record) => OrderItem::where('order_id', $record->id)->count()),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '—')),

                TextColumn::make('payment_status')
                    ->label('Paid?')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid'     => 'success',
                        'pending'  => 'warning',
                        'failed'   => 'danger',
                        'refunded' => 'gray',
                        default    => 'secondary',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
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
                    ->color(fn(string $state): string => match ($state) {
                        'delivered'                         => 'success',
                        'pending'                           => 'warning',
                        'confirmed', 'processing'           => 'info',
                        'shipped', 'out_for_delivery'       => 'primary',
                        'cancelled', 'returned', 'refunded' => 'danger',
                        default                             => 'secondary',
                    }),

                TextColumn::make('delivery_type')
                    ->label('Delivery')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('courier_tracking_id')
                    ->label('Tracking ID')
                    ->copyable()
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Use plain Filter with a form Select component instead of SelectFilter.
                // SelectFilter calls Table::getModel() during Livewire boot before the
                // relation manager has resolved the model, causing "::class on null".
                Filter::make('status')
                    ->form([
                        FormSelect::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending'          => 'Pending',
                                'confirmed'        => 'Confirmed',
                                'processing'       => 'Processing',
                                'shipped'          => 'Shipped',
                                'out_for_delivery' => 'Out for Delivery',
                                'delivered'        => 'Delivered',
                                'cancelled'        => 'Cancelled',
                                'returned'         => 'Returned',
                                'refunded'         => 'Refunded',
                            ])
                            ->placeholder('All statuses'),
                    ])
                    ->query(fn(Builder $q, array $data) => $q->when(
                        $data['status'] ?? null,
                        fn($q, $v) => $q->where('status', $v)
                    ))
                    ->indicateUsing(fn(array $data) => $data['status']
                        ? 'Status: ' . ucfirst(str_replace('_', ' ', $data['status']))
                        : null
                    ),

                Filter::make('payment_status')
                    ->form([
                        FormSelect::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'paid'     => 'Paid',
                                'pending'  => 'Pending',
                                'failed'   => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->placeholder('All'),
                    ])
                    ->query(fn(Builder $q, array $data) => $q->when(
                        $data['payment_status'] ?? null,
                        fn($q, $v) => $q->where('payment_status', $v)
                    ))
                    ->indicateUsing(fn(array $data) => $data['payment_status']
                        ? 'Payment: ' . ucfirst($data['payment_status'])
                        : null
                    ),

                Filter::make('cancelled')
                    ->label('Cancelled orders only')
                    ->query(fn(Builder $q) => $q->where('status', 'cancelled')),
            ])
            ->actions([
                Action::make('view_order')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->modalHeading(fn($record) => 'Order ' . $record->order_number)
                    ->infolist(function (Infolist $infolist) {
                        $order = Order::with(['items', 'courierMerchant'])
                            ->findOrFail($infolist->getRecord()->getKey());

                        return $infolist->record($order)->schema([

                            Section::make('Order Details')->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('order_number')
                                        ->label('Order #')
                                        ->copyable(),

                                    TextEntry::make('created_at')
                                        ->label('Placed At')
                                        ->dateTime('d M Y, h:i A'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'pending'          => '⏳ Pending',
                                            'confirmed'        => ' Confirmed',
                                            'processing'       => ' Processing',
                                            'shipped'          => ' Shipped',
                                            'out_for_delivery' => ' Out for Delivery',
                                            'delivered'        => ' Delivered',
                                            'cancelled'        => ' Cancelled',
                                            'returned'         => '↩ Returned',
                                            'refunded'         => ' Refunded',
                                            default            => ucfirst(str_replace('_', ' ', $state)),
                                        })
                                        ->color(fn($state) => match ($state) {
                                            'delivered'                         => 'success',
                                            'pending'                           => 'warning',
                                            'confirmed', 'processing'           => 'info',
                                            'shipped', 'out_for_delivery'       => 'primary',
                                            'cancelled', 'returned', 'refunded' => 'danger',
                                            default                             => 'gray',
                                        }),

                                    TextEntry::make('payment_method')
                                        ->label('Payment Method')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('payment_status')
                                        ->label('Payment Status')
                                        ->badge()
                                        ->color(fn($state) => match ($state) {
                                            'paid'    => 'success',
                                            'pending' => 'warning',
                                            'failed'  => 'danger',
                                            default   => 'gray',
                                        }),

                                    TextEntry::make('payment_transaction_id')
                                        ->label('Transaction ID')
                                        ->copyable()
                                        ->default('—'),
                                ]),
                            ]),

                            Section::make('Shipping Address')->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('shipping_name')->label('Name'),
                                    TextEntry::make('shipping_phone')->label('Phone')->copyable(),
                                    TextEntry::make('delivery_type')->label('Delivery Type')->badge()->color('info'),
                                    TextEntry::make('shipping_address')->label('Address')->columnSpanFull(),
                                    TextEntry::make('shipping_upazila')->label('Upazila'),
                                    TextEntry::make('shipping_district')->label('District'),
                                    TextEntry::make('shipping_division')->label('Division'),
                                ]),
                            ]),

                            Section::make('Order Items')->schema([
                                TextEntry::make('items_summary')
                                    ->label('')
                                    ->columnSpanFull()
                                    ->getStateUsing(function ($record) {
                                        $items = $record->items ?? collect();

                                        if ($items->isEmpty()) {
                                            return '—';
                                        }

                                        return $items->values()->map(function ($item, $i) {
                                            $num   = $i + 1;
                                            $name  = $item->product_name ?? 'Unknown Product';
                                            $qty   = $item->quantity ?? 1;
                                            $unit  = number_format($item->unit_price ?? 0, 2);
                                            $total = number_format($item->total_price ?? 0, 2);

                                            return "{$num}. {$name} — {$qty} × BDT {$unit} = BDT {$total}";
                                        })->implode("\n");
                                    }),
                            ]),

                            Section::make('Financials')->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('subtotal')->label('Subtotal')->money('BDT'),
                                    TextEntry::make('shipping_charge')->label('Shipping')->money('BDT'),
                                    TextEntry::make('discount_amount')->label('Discount')->money('BDT'),
                                    TextEntry::make('coupon_discount')->label('Coupon Discount')->money('BDT')->default('0.00'),
                                    TextEntry::make('total_amount')
                                        ->label('Grand Total')
                                        ->money('BDT')
                                        ->size(TextEntrySize::Large)
                                        ->weight(FontWeight::Bold)
                                        ->color('success'),
                                ]),
                            ]),

                            Section::make('Courier Information')->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('courierMerchant.name')->label('Courier Partner')->default('—'),
                                    TextEntry::make('courier_tracking_id')->label('Tracking ID')->copyable()->default('—'),
                                    TextEntry::make('courier_status')->label('Courier Status')->badge()->color('info')->default('—'),
                                    TextEntry::make('courier_booked_at')->label('Booked At')->dateTime('d M Y, h:i A')->placeholder('—'),
                                    TextEntry::make('estimated_delivery_at')->label('Est. Delivery')->date('d M Y')->placeholder('—'),
                                    TextEntry::make('delivered_at')->label('Delivered At')->dateTime('d M Y, h:i A')->placeholder('—'),
                                ]),
                            ])->collapsed(),

                            Section::make('Notes')->schema([
                                TextEntry::make('customer_note')->label('Customer Note')->default('—'),
                                TextEntry::make('admin_note')->label('Admin Note')->default('—'),
                                TextEntry::make('cancellation_reason')->label('Cancellation Reason')->default('—'),
                                TextEntry::make('cancelled_at')->label('Cancelled At')->dateTime('d M Y, h:i A')->placeholder('—'),
                            ])->collapsed(),

                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([])
            ->paginated([10, 25, 50]);
    }
}
