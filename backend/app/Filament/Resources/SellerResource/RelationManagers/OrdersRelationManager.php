<?php

namespace App\Filament\Resources\SellerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Filters\SelectFilter};
use Filament\Tables\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid, RepeatableEntry};

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Sales / Orders';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable()
                    ->description(fn($record) => $record->created_at->format('d M Y, h:i A')),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn($record) => $record->shipping_phone ?? ''),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('seller_payout')
                    ->label('Seller Payout')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('BDT')
                    ->color('warning'),

                TextColumn::make('payment_status')
                    ->label('Payment')
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
                        'delivered'              => 'success',
                        'pending'                => 'warning',
                        'confirmed', 'processing'=> 'info',
                        'shipped', 'out_for_delivery' => 'primary',
                        'cancelled', 'returned', 'refunded' => 'danger',
                        default                  => 'secondary',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
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
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'paid'     => 'Paid',
                        'pending'  => 'Pending',
                        'failed'   => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalHeading(fn($record) => 'Order ' . $record->order_number)
                    ->modalWidth('4xl')
                    ->infolist(fn(Infolist $infolist, $record) =>
                    $infolist->record($record)->schema([
                        Section::make('Order Info')->schema([
                            Grid::make(3)->schema([
                                TextEntry::make('order_number')->label('Order #')->copyable(),
                                TextEntry::make('created_at')->label('Date')->dateTime('d M Y, h:i A'),
                                TextEntry::make('status')->label('Status')->badge()
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
                                        default            => ucfirst($state),
                                    })
                                    ->color(fn($state) => match ($state) {
                                        'delivered'  => 'success',
                                        'pending'    => 'warning',
                                        'confirmed', 'processing' => 'info',
                                        'shipped', 'out_for_delivery' => 'primary',
                                        default      => 'danger',
                                    }),
                                TextEntry::make('payment_method')->label('Payment Method')->badge()->color('gray'),
                                TextEntry::make('payment_status')->label('Payment Status')->badge()
                                    ->color(fn($state) => match ($state) {
                                        'paid'     => 'success',
                                        'pending'  => 'warning',
                                        'failed'   => 'danger',
                                        default    => 'gray',
                                    }),
                                TextEntry::make('payment_transaction_id')->label('Transaction ID')->copyable()->default('—'),
                            ]),
                        ]),

                        Section::make('Customer & Delivery')->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('user.name')->label('Customer Name'),
                                TextEntry::make('shipping_phone')->label('Phone'),
                                TextEntry::make('shipping_address')->label('Address'),
                                TextEntry::make('shipping_district')->label('District'),
                                TextEntry::make('shipping_division')->label('Division'),
                                TextEntry::make('delivery_type')->label('Delivery Type')->badge()->color('info'),
                            ]),
                        ]),

                        Section::make('Order Items')->schema([
                            RepeatableEntry::make('items')->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('product_name')->label('Product'),
                                    TextEntry::make('quantity')->label('Qty')->badge()->color('info'),
                                    TextEntry::make('unit_price')->label('Unit Price')->money('BDT'),
                                    TextEntry::make('total_price')->label('Total')->money('BDT'),
                                ]),
                            ])->contained(false),
                        ]),

                        Section::make('Financials')->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('subtotal')->label('Subtotal')->money('BDT'),
                                TextEntry::make('shipping_charge')->label('Shipping')->money('BDT'),
                                TextEntry::make('discount_amount')->label('Discount')->money('BDT'),
                                TextEntry::make('total_amount')->label('Grand Total')->money('BDT'),
                                TextEntry::make('commission_amount')->label('Commission')->money('BDT')->color('warning'),
                                TextEntry::make('seller_payout')->label('Seller Payout')->money('BDT')->color('success'),
                            ]),
                        ]),

                        Section::make('Courier')->schema([
                            Grid::make(3)->schema([
                                TextEntry::make('courierMerchant.name')->label('Courier')->default('—'),
                                TextEntry::make('courier_tracking_id')->label('Tracking ID')->copyable()->default('—'),
                                TextEntry::make('courier_status')->label('Courier Status')->badge()->color('info')->default('—'),
                            ]),
                        ]),

                        Section::make('Notes')->schema([
                            TextEntry::make('customer_note')->label('Customer Note')->default('—'),
                            TextEntry::make('admin_note')->label('Admin Note')->default('—'),
                            TextEntry::make('cancellation_reason')->label('Cancellation Reason')->default('—'),
                        ])->collapsed(),
                    ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([])
            ->paginated([10, 25, 50]);
    }
}
