<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\{ActivityLog, CourierMerchant, Order};
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\{Grid, ImageEntry, RepeatableEntry, Section, TextEntry, ViewEntry};
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Filament\Forms\Components\{Select, Textarea};
use Filament\Support\Enums\FontWeight;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public static function checkTheMessageIsArray($result): void
    {
        foreach ($result['raw'] as $key => $value) {
            if ($key === 'errors' && is_array($value)) {
                $collection = collect($value)->values();
                foreach ($collection->values()->flatten()->toArray() as $message) {
                    Notification::make()->danger()->title($message)->send();
                }
            }
        }
        Notification::make()->danger()->title('Courier failed: ' . ($result['message'] ?? 'Error'))->send();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Row 1: Status Banner ──────────────────────────────────
            Section::make()->schema([
                Grid::make(5)->schema([
                    TextEntry::make('order_number')
                        ->label('Order Number')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->copyable()
                        ->icon('heroicon-m-hashtag'),

                    TextEntry::make('status')
                        ->label('Order Status')
                        ->badge()
                        ->formatStateUsing(fn(mixed $state) => match ($state) {
                            'pending'          => '⏳ Pending',
                            'confirmed'        => ' Confirmed',
                            'processing'       => ' Processing',
                            'shipped'          => ' Shipped',
                            'out_for_delivery' => ' Out for Delivery',
                            'delivered'        => ' Delivered',
                            'cancelled'        => ' Cancelled',
                            'returned'         => '↩ Returned',
                            'refunded'         => ' Refunded',
                            default            => $state,
                        })
                        ->color(fn(mixed $state) => match ($state) {
                            'pending'                     => 'warning',
                            'confirmed', 'processing'     => 'info',
                            'shipped', 'out_for_delivery' => 'primary',
                            'delivered'                   => 'success',
                            default                       => 'danger',
                        }),

                    TextEntry::make('payment_status')
                        ->label('Payment Status')
                        ->badge()
                        ->color(fn(mixed $state) => match ($state) {
                            'paid'    => 'success',
                            'pending' => 'warning',
                            default   => 'danger',
                        }),

                    TextEntry::make('payment_method')
                        ->label('Payment Method')
                        ->formatStateUsing(fn(mixed $state) => strtoupper($state))
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('created_at')
                        ->label('Order Date')
                        ->dateTime('d M Y, h:i A')
                        ->icon('heroicon-m-calendar'),
                ]),
            ])->columnSpanFull(),

            // ── Row 2: Customer + Delivery + Courier ──────────────────
            Grid::make(3)->schema([

                Section::make(' Customer Information')->schema([
                    TextEntry::make('user.name')->label('Name')->icon('heroicon-m-user'),
                    TextEntry::make('user.email')->label('Email')->icon('heroicon-m-envelope')->default('—'),
                    TextEntry::make('user.phone')->label('Phone')->icon('heroicon-m-phone')->copyable()->default('—'),
                    TextEntry::make('user.total_orders')->label('Total Orders')->badge()->color('info')->default(0),
                    TextEntry::make('user.created_at')->label('Member Since')->date('d M Y')->icon('heroicon-m-calendar-days'),
                ])->columnSpan(1),

                Section::make(' Delivery Address')->schema([
                    TextEntry::make('shipping_name')->label('Recipient')->icon('heroicon-m-user-circle'),
                    TextEntry::make('shipping_phone')->label('Phone')->icon('heroicon-m-phone')->copyable(),
                    TextEntry::make('shipping_address')->label('Address')->icon('heroicon-m-map-pin'),
                    Grid::make(2)->schema([
                        TextEntry::make('shipping_district')->label('District'),
                        TextEntry::make('shipping_upazila')->label('Upazila')->default('—'),
                        TextEntry::make('shipping_division')->label('Division')->default('—'),
                        TextEntry::make('shipping_postal_code')->label('Postal Code')->default('—'),
                    ]),
                    TextEntry::make('delivery_type')
                        ->label('Delivery Type')
                        ->badge()->color('info')
                        ->formatStateUsing(fn(mixed $state) => ucfirst($state ?? 'Standard')),
                ])->columnSpan(1),

                Section::make(' Courier & Tracking')->schema([
                    TextEntry::make('courierMerchant.name')->label('Courier')->icon('heroicon-m-truck')->default('Not assigned'),
                    TextEntry::make('courier_tracking_id')->label('Tracking ID')->icon('heroicon-m-qr-code')->copyable()->default('—'),
                    TextEntry::make('courier_tracking_url')
                        ->label('Tracking URL')
                        ->url(fn(Order $r) => $r->courier_tracking_url)
                        ->openUrlInNewTab()
                        ->default('—'),
                    TextEntry::make('courier_booked_at')->label('Send At')->dateTime('d M Y, h:i A')->placeholder('—'),
                    TextEntry::make('courier_status')->label('Courier Status')->badge()->color('primary')->default('—'),
                    TextEntry::make('estimated_delivery_at')->label('Est. Delivery')->date('d M Y')->placeholder('—'),
                ])->columnSpan(1),
            ]),

            // ── Row 3: Order Items ────────────────────────────────────
            Section::make(' Order Items')->schema([
                RepeatableEntry::make('items')->schema([
                    Grid::make(7)->schema([
                        ImageEntry::make('product_image')->label('')->height(56)->width(56)->extraImgAttributes(['class' => 'rounded-lg object-cover'])->defaultImageUrl(asset('images/shop-default.png')),
                        TextEntry::make('product_name')->label('Product')->weight(FontWeight::SemiBold)->columnSpan(2),
                        

                        TextEntry::make('variant_display')->label('Variant')->getStateUsing(fn ($record) => variant_label($record->variant_options) ?: '—')
                        ->color('gray')->columnSpan(2),

                        TextEntry::make('unit_price')->label('Unit Price')->money('BDT'),
                        TextEntry::make('quantity')->label('Qty')->badge()->color('gray'),
                        TextEntry::make('total_price')->label('Total')->money('BDT')->weight(FontWeight::Bold)->color('success'),
                    ]),
                ])->contained(false),
            ]),

            // ── Row 4: Payment + Seller + Notes ──────────────────────
            Grid::make(3)->schema([
                Section::make(' Payment Summary')->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('subtotal')->label('Subtotal')->money('BDT'),
                        TextEntry::make('shipping_charge')->label('Shipping')->money('BDT'),
                        TextEntry::make('discount_amount')->label('Discount')->money('BDT')->default(0),
                        TextEntry::make('coupon_discount')->label('Coupon Discount')->money('BDT')->default(0),
                        TextEntry::make('coupon_code')->label('Coupon Code')->badge()->color('success')->default('—'),
                        TextEntry::make('total_amount')->label('Grand Total')->money('BDT')->size(TextEntry\TextEntrySize::Large)->weight(FontWeight::Bold)->color('success'),
                    ]),
                ])->columnSpan(1),

                Section::make(' Seller & Commission')->schema([
                    TextEntry::make('seller.shop_name')->label('Shop')->icon('heroicon-m-building-storefront')->default('—'),
                    TextEntry::make('seller.user.name')->label('Seller Name')->default('—'),
                    TextEntry::make('commission_amount')->label('Commission')->money('BDT')->default(0),
                    TextEntry::make('seller_payout')->label('Seller Payout')->money('BDT')->color('success')->default(0),
                ])->columnSpan(1),

                Section::make(' Notes')->schema([
                    TextEntry::make('customer_note')->label('Customer Note')->default('No note.')->columnSpanFull(),
                    TextEntry::make('admin_note')->label('Admin Note')->default('No admin note.')->columnSpanFull(),
                    TextEntry::make('payment_transaction_id')->label('Transaction ID')->copyable()->default('—'),
                    TextEntry::make('delivered_at')->label('Delivered At')->dateTime('d M Y, h:i A')->placeholder('—'),
                ])->columnSpan(1),
            ]),

            // ── Row 5: Status History ─────────────────────────────────
            Section::make(' Status History')->schema([
                RepeatableEntry::make('statusHistories')->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('status')
                            ->label('Status')->badge()
                            ->formatStateUsing(fn(mixed $state) => ucfirst(str_replace('_', ' ', $state)))
                            ->color(fn(mixed $state) => match ($state) {
                                'pending'                     => 'warning',
                                'confirmed', 'processing'     => 'info',
                                'shipped', 'out_for_delivery' => 'primary',
                                'delivered'                   => 'success',
                                default                       => 'danger',
                            }),
                        TextEntry::make('note')->label('Note')->default('—'),
                        TextEntry::make('changed_by')->label('Changed By')->badge()->color('gray'),
                        TextEntry::make('created_at')->label('Time')->dateTime('d M Y, h:i A'),
                    ]),
                ])->contained(false),
            ]),

            // ── Row 6: Activity Log for this Order ───────────────────
            Section::make(' Activity Log')
                ->description('Every action performed on this order — who did it, when, from which IP.')
                ->collapsible()
                ->schema([
                    ViewEntry::make('activity_log')
                        ->label('')
                        ->view('filament.infolists.order-activity-log')
                        ->viewData(['record' => $this->record])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->icon('heroicon-o-pencil-square'),
            Action::make('print_invoice')->label('Print Invoice')->icon('heroicon-o-printer')->color('gray')->url(fn() => route('orders.invoice', ['order' => $this->record->id, 'print' => '1']))->openUrlInNewTab(),
            Action::make('download_invoice')->label('Download Invoice')->icon('heroicon-o-arrow-down-tray')->color('success')->url(fn() => route('orders.invoice', $this->record->id))->openUrlInNewTab(),
            Action::make('Send_courier')->label('Send Courier')->icon('heroicon-o-truck')->color('success')->requiresConfirmation()->modalHeading('Send Courier')->form([
                Select::make('courier_merchant_id')->label('Select Courier')->options(CourierMerchant::where('is_active', true)->pluck('name', 'id'))->default(fn() => CourierMerchant::where('is_default', true)->value('id'))->required(),
            ])->action(function (array $data) {
                $result = $this->record->bookCourier($data['courier_merchant_id']);
                $result['success'] ? Notification::make()->success()->title("Courier booked! Tracking: {$result['tracking_id']}")->send() : static::checkTheMessageIsArray($result);
                if ($result['success']) app(SmsService::class)->sendCourierBooked($this->record->fresh());
            })->visible(fn() => $this->record->canBookCourier()),

            Action::make('update_status')->label('Update Status')->icon('heroicon-o-arrow-path')->color('warning')->form([
                Select::make('status')->label('New Status')
                    ->options([
                        'pending'          => '⏳ Pending',
                        'confirmed'        => ' Confirmed',
                        'processing'       => ' Processing',
                        'shipped'          => ' Shipped',
                        'out_for_delivery' => ' Out for Delivery',
                        'delivered'        => ' Delivered',
                        'cancelled'        => ' Cancelled',
                        'returned'         => '↩ Returned',
                        'refunded'         => ' Refunded',
                    ])
                    ->default($this->record->status)->required(),
                Textarea::make('note')->label('Note')->rows(2),
            ])->action(function (array $data) {
                $this->record->updateStatus($data['status'], $data['note'] ?? '', 'admin', auth()->id());
                Notification::make()->success()->title('Status updated!')->send();
                $this->refreshFormData(['status']);
            }),

            Action::make('send_sms')->label('Send SMS')->icon('heroicon-o-chat-bubble-left')->color('info')->form([Textarea::make('message')->label('Message')->required()->rows(4)])->action(fn(array $data) => app(SmsService::class)->send($this->record->shipping_phone, $data['message'], $this->record) &&
                Notification::make()->success()->title('SMS sent!')->send()
            ),
        ];
    }
}
