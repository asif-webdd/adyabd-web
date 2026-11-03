<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use Illuminate\Support\Collection;
use Livewire\Component;
use App\Models\{Order, CourierMerchant};
use App\Services\{BlockingService, SmsService};
use Filament\Forms\{Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea,
    Components\Grid,
    Components\Group,
    Components\Placeholder
};
use Filament\Tables\{Actions\DeleteBulkAction,
    Table,
    Columns\TextColumn,
    Actions\EditAction,
    Actions\ViewAction,
    Actions\Action,
    Actions\BulkAction,
    Filters\SelectFilter,
    Filters\Filter
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'orders';
    protected static ?string $model           = Order::class;
    protected static ?string $navigationIcon  = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string)Order::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('orders', ['Order #' => 'order_number', 'Date' => 'created_at', 'Customer' => 'shipping_name', 'Phone' => 'shipping_phone', 'Status' => 'status', 'Payment method' => 'payment_method', 'Payment status' => 'payment_status', 'Subtotal' => 'subtotal', 'Discount' => 'discount_amount', 'Coupon discount' => 'coupon_discount', 'Shipping' => 'shipping_charge', 'Total' => 'total_amount', 'Commission' => 'commission_amount', 'Seller payout' => 'seller_payout', 'Seller' => 'seller.shop_name', 'Division' => 'shipping_division', 'District' => 'shipping_district', 'Address' => 'shipping_address', 'Courier' => 'courier_status', 'Tracking' => 'courier_tracking_id', 'Delivered at' => 'delivered_at'], ['seller'])])
            ->modifyQueryUsing(fn(Builder $query) => $query->withCount('items')->with(['user', 'courierMerchant']))->columns([
            // ── Order number + age ────────────────────────────────────────────────
            TextColumn::make('order_number')->label('Order')->searchable()->sortable()->copyable()->description(fn(Order $r) => $r->created_at->diffForHumans()),
            // ── Customer + phone ──────────────────────────────────────────────────
            TextColumn::make('user.name')->label('Customer')->searchable()->description(fn(Order $r) => $r->shipping_phone),
            // ── Shipping district + division ──────────────────────────────────────
            TextColumn::make('shipping_district')->label('District')->badge()->color('gray')->searchable()->placeholder('—')->description(fn(Order $r): string => $r->shipping_division ?? ''),
            // ── Item count ────────────────────────────────────────────────────────
            TextColumn::make('items_count')->label('Items')->badge()->color('primary')->sortable()->tooltip('Total number of products in this order'),
            // ── Subtotal + total ──────────────────────────────────────────────────
            TextColumn::make('total_amount')->label('Total')->money('BDT')->sortable()->description(fn(Order $r): string => 'Subtotal: BDT  ' . number_format($r->subtotal, 0)),
            // ── Shipping charge ───────────────────────────────────────────────────
            TextColumn::make('shipping_charge')->label('Shipping')->money('BDT')->sortable()->badge()->color('info')->placeholder('—'),
            // ── Total savings (discount + coupon breakdown) ───────────────────────
            TextColumn::make('discount_amount')->label('Savings')->sortable()->badge()->color(fn($state, Order $r): string => (($state ?? 0) + ($r->coupon_discount ?? 0)) > 0 ? 'success' : 'gray')->formatStateUsing(fn($state, Order $r): string => (($state ?? 0) + ($r->coupon_discount ?? 0)) > 0 ? '− BDT  ' . number_format(($state ?? 0) + ($r->coupon_discount ?? 0), 0) : '—')->description(function (Order $r): string {
                $parts = [];
                if (($r->discount_amount ?? 0) > 0) {
                    $parts[] = 'Disc: BDT  ' . number_format($r->discount_amount, 0);
                }
                if (($r->coupon_discount ?? 0) > 0) {
                    $label = $r->coupon_code ? 'Coupon: ' . $r->coupon_code : 'Coupon';
                    $parts[] = $label . ': BDT  ' . number_format($r->coupon_discount, 0);
                }
                return implode(' · ', $parts);
            })->tooltip(fn(Order $r): string => implode(' | ', array_filter([
                'Discount : BDT  ' . number_format($r->discount_amount ?? 0, 0),
                'Coupon   : BDT  ' . number_format($r->coupon_discount ?? 0, 0),
                $r->coupon_code ? 'Code     : ' . $r->coupon_code : null,
                'Total saved: BDT  ' . number_format(($r->discount_amount ?? 0) + ($r->coupon_discount ?? 0), 0),
            ])))->placeholder('—'),
            // ── Commission amount ─────────────────────────────────────────────────
            TextColumn::make('commission_amount')->label('Commission')->money('BDT')->sortable()->badge()->color('danger')->tooltip('Platform commission deducted from seller payout')->placeholder('—'),
            // ── Order status ──────────────────────────────────────────────────────
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn(string $state) => [
                'pending'          => 'Pending',
                'confirmed'        => 'Confirmed',
                'processing'       => 'Processing',
                'shipped'          => 'Shipped',
                'out_for_delivery' => 'Out for Delivery',
                'delivered'        => 'Delivered',
                'cancelled'        => 'Cancelled',
                'returned'         => 'Returned',
                'refunded'         => 'Refunded',
            ][$state] ?? $state)->color(fn(string $state) => match ($state) {
                'pending'                     => 'warning',
                'confirmed', 'processing'     => 'info',
                'shipped', 'out_for_delivery' => 'primary',
                'delivered'                   => 'success',
                'cancelled', 'returned',
                'refunded'                    => 'danger',
                default                       => 'gray',
            }),

            // ── Payment status + method merged ────────────────────────────────────
            TextColumn::make('payment_status')->label('Payment')->badge()->color(fn(string $state) => match ($state) {
                'paid'               => 'success',
                'pending'            => 'warning',
                'failed', 'refunded' => 'danger',
                default              => 'gray',
            })->description(fn(Order $r): string => match ($r->payment_method ?? '') {
                'zinipay' => 'ZiniPay',
                'bkash'   => 'bKash',
                'nagad'   => 'Nagad',
                'rocket'  => 'Rocket',
                'card'    => 'Card',
                'cod'     => 'COD',
                'wallet'  => 'Wallet',
                default   => $r->payment_method ?? '—',
            }),

            // ── Courier + tracking merged into one column ─────────────────────────
            TextColumn::make('courierMerchant.name')->label('Courier')->placeholder('—')->description(fn(Order $r): string => $r->courier_tracking_id ? $r->courier_tracking_id : 'Not booked')->copyable()->copyableState(fn(Order $r): string => $r->courier_tracking_id ?? ''),
            // ── Courier delivery status ────────────────────────────────────────────
            TextColumn::make('courier_status')->label('Delivery Status')->badge()->formatStateUsing(fn(?string $state) => match ($state) {
                'pending'           => 'Pending',
                'booked'            => 'Booked',
                'in_review'         => 'In Review',
                'hold'              => 'On Hold',
                'delivered'         => 'Delivered',
                'partial_delivered' => 'Partial Delivered',
                'cancelled'         => 'Cancelled',
                null, ''            => '—',
                default             => ucfirst(str_replace('_', ' ', $state)),
            })->color(fn(?string $state) => match ($state) {
                'delivered'                    => 'success',
                'partial_delivered'            => 'warning',
                'pending', 'in_review', 'hold' => 'info',
                'cancelled'                    => 'danger',
                'booked'                       => 'primary',
                default                        => 'gray',
            })->placeholder('—'),

            // ── Delivery type ─────────────────────────────────────────────────────
            TextColumn::make('delivery_type')->label('Delivery type')->badge()->color(fn(?string $state) => match ($state) {
                'express'  => 'warning',
                'standard' => 'info',
                'economy'  => 'gray',
                default    => 'gray',
            })->formatStateUsing(fn(?string $state) => match ($state) {
                'express'  => 'Express',
                'standard' => 'Standard',
                'economy'  => 'Economy',
                default    => $state ?? '—',
            })->placeholder('—'),

            // ── Seller payout ─────────────────────────────────────────────────────
            TextColumn::make('seller_payout')->label('Payout')->money('BDT')->sortable()->badge()->color('warning')->tooltip('Seller payout after commission deduction'),
            // ── Order date ────────────────────────────────────────────────────────
            TextColumn::make('created_at')->label('Date')->dateTime('d M Y h:i A')->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([
            SelectFilter::make('status')->options([
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
            SelectFilter::make('payment_status')->options([
                'pending'  => 'Pending',
                'paid'     => 'Paid',
                'failed'   => 'Failed',
                'refunded' => 'Refunded',
            ]),
            SelectFilter::make('payment_method')->options([
                'cod'     => 'COD',
                'zinipay' => 'ZiniPay',
                'wallet'  => 'Wallet',
                'bkash'   => 'bKash',
                'nagad'   => 'Nagad',
                'rocket'  => 'Rocket',
                'card'    => 'Card',
            ]),
            SelectFilter::make('delivery_type')->options([
                'express'  => 'Express',
                'standard' => 'Standard',
                'economy'  => 'Economy',
            ]),
            SelectFilter::make('courier_status')->label('Delivery Status')->options([
                'pending'           => 'Pending',
                'booked'            => 'Booked',
                'in_review'         => 'In Review',
                'hold'              => 'On Hold',
                'delivered'         => 'Delivered',
                'partial_delivered' => 'Partial Delivered',
                'cancelled'         => 'Cancelled',
            ]),
            SelectFilter::make('shipping_district')->label('District')->searchable()->options(fn() => Order::distinct()->pluck('shipping_district', 'shipping_district')->filter()->sort()->toArray()),
            SelectFilter::make('shipping_division')->label('Division')->searchable()->options(fn() => Order::distinct()->pluck('shipping_division', 'shipping_division')->filter()->sort()->toArray()),
            Filter::make('today')->label('Today')->query(fn(Builder $q) => $q->whereDate('created_at', today())),
            Filter::make('has_coupon')->label('Has Coupon')->query(fn(Builder $q) => $q->whereNotNull('coupon_code')),
            Filter::make('has_discount')->label('Has Discount')->query(fn(Builder $q) => $q->where('discount_amount', '>', 0)->orWhere('coupon_discount', '>', 0)),
            Filter::make('free_shipping')->label('Free Shipping')->query(fn(Builder $q) => $q->where('shipping_charge', 0)),
            Filter::make('no_courier')->label('No Courier Booked')->query(fn(Builder $q) => $q->whereNull('courier_tracking_id')->whereIn('status', ['confirmed', 'processing'])),
        ])->actions([
            ViewAction::make()->label('View'),
            EditAction::make()->label('Edit'),
            Action::make('download_invoice')->label('Invoice')->icon('heroicon-o-document-arrow-down')->color('gray')->url(fn(Order $record) => route('orders.invoice', $record->id))->openUrlInNewTab(),
            Action::make('send_courier')->label('Send Courier')->color('success')->icon('heroicon-o-truck')->requiresConfirmation()->modalHeading('Book Courier')->form([
                Select::make('courier_merchant_id')->label('Select Courier')->options(CourierMerchant::where('is_active', true)->pluck('name', 'id'))->default(fn() => CourierMerchant::where('is_default', true)->value('id'))->required(),
            ])->action(function (Order $record, array $data) {
                $result = $record->bookCourier($data['courier_merchant_id']);
                if ($result['success']) {
                    Notification::make()->success()->title("Courier booked! Tracking: {$result['tracking_id']}")->send();
                    app(SmsService::class)->sendCourierBooked($record->fresh());
                } else {
                    ViewOrder::checkTheMessageIsArray($result);
                }
            })->visible(fn(Order $r) => $r->canBookCourier()),
            Action::make('block_user')->label('Block')->color('danger')->icon('heroicon-o-no-symbol')->requiresConfirmation()->modalHeading('Block Customer')->form([
                Textarea::make('reason')->label('Reason')->required()->rows(3),
                Select::make('block_duration')->label('Duration')->options([
                    '7 days'    => '7 Days',
                    '30 days'   => '30 Days',
                    '90 days'   => '90 Days',
                    'permanent' => 'Permanent',
                ])->default('permanent'),
                Toggle::make('block_ip')->label('Block IP Address')->default(true),
            ])->action(function (Order $record, array $data) {
                app(BlockingService::class)->blockUser($record->user, $data['reason'], auth()->id(), $data['block_ip'] ?? true, $data['block_duration']);
                Notification::make()->success()->title('Customer has been blocked!')->send();
            })->visible(fn(Order $r) => $r->user && !$r->user->is_blocked),
            Action::make('mark_no_answer')->label('No Answer')->icon('heroicon-o-phone-x-mark')->color('warning')->requiresConfirmation()->form([Textarea::make('note')->label('Note')->rows(2)])->action(function (Order $record, array $data) {
                app(BlockingService::class)->markNoAnswer($record, $data['note'] ?? '');
                Notification::make()->success()->title('Recorded successfully!')->send();
            })->visible(fn(Order $r) => in_array($r->status, ['shipped', 'out_for_delivery'])),
            Action::make('send_sms')->label('SMS')->icon('heroicon-o-chat-bubble-left-ellipsis')->color('info')->form([Textarea::make('message')->label('Message')->required()->rows(4)])->action(fn(Order $record, array $data) => app(SmsService::class)->send($record->shipping_phone, $data['message'], $record) && Notification::make()->success()->title('SMS sent successfully!')->send()),
            Action::make('refund')->label('Refund')->icon('heroicon-o-arrow-uturn-left')->color('danger')->requiresConfirmation()->action(function (Order $record) {
                $record->processRefund();
                $record->updateStatus('refunded', 'Admin refund', 'admin', auth()->id());
                Notification::make()->success()->title('Refund completed!')->send();
            })->visible(fn(Order $r) => $r->payment_status === 'paid' && in_array($r->status, ['cancelled', 'returned'])),
        ])->bulkActions([
            BulkAction::make('bulk_book_courier')->label('Send All Couriers')->requiresConfirmation()->form([
                Select::make('courier_merchant_id')->label('Courier')->options(CourierMerchant::where('is_active', true)->pluck('name', 'id'))->required(),
            ])->action(function ($records, array $data) {
                [$ok, $fail] = [0, 0];
                foreach ($records as $order) {
                    if ($order->canBookCourier()) {
                        $result = $order->bookCourier($data['courier_merchant_id']);
                        $result['success'] ? $ok++ : $fail++;
                    }
                }
                Notification::make()->success()->title("Courier: {$ok} succeeded, {$fail} failed")->send();
            }),

            BulkAction::make('bulk_confirm')->label('Confirm Selected')->icon('heroicon-o-check-circle')->requiresConfirmation()->action(fn($records) => $records->each->updateStatus('confirmed', '', 'admin', auth()->id())),
            BulkAction::make('bulk_sms')->label('Send SMS')->icon('heroicon-o-chat-bubble-left-ellipsis')->form([Textarea::make('message')->label('Message')->required()->rows(4)])->action(fn($records, array $data) => $records->each(fn($o) => app(SmsService::class)->send($o->shipping_phone, $data['message'], $o))),
            BulkAction::make('bulk_print_invoice')->label('Print Invoices')->icon('heroicon-o-printer')->color('gray')->action(function (Collection $records, Component $livewire) {
                $ids = $records->pluck('id')->values()->all();
                $url = route('orders.bulk-invoice') . '?ids=' . implode(',', $ids) . '&print=1';
                $livewire->js("window.open('" . addslashes($url) . "', '_blank')");
            })->deselectRecordsAfterCompletion(),
            BulkAction::make('bulk_download_invoice')->label('Download Invoices')->icon('heroicon-o-document-arrow-down')->color('info')->action(function (Collection $records, Component $livewire) {
                $ids = $records->pluck('id')->values()->all();
                $url = route('orders.bulk-invoice') . '?ids=' . implode(',', $ids);
                $livewire->js("window.open('" . addslashes($url) . "', '_blank')");
            })->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make(),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                // ── Left column (main content, 2/3 width) ──────────────
                Group::make()->columnSpan(2)->schema([
                    Section::make('Order Management')->icon('heroicon-o-adjustments-horizontal')->description('Update order status, payment and courier assignment.')->schema([
                        Select::make('status')->label('Order status')->options([
                            'pending'          => 'Pending',
                            'confirmed'        => 'Confirmed',
                            'processing'       => 'Processing',
                            'shipped'          => 'Shipped',
                            'out_for_delivery' => 'Out for Delivery',
                            'delivered'        => 'Delivered',
                            'cancelled'        => 'Cancelled',
                            'returned'         => 'Returned',
                            'refunded'         => 'Refunded',
                        ])->required()->native(false),
                        Select::make('payment_status')->label('Payment status')->options([
                            'pending'  => 'Pending',
                            'paid'     => 'Paid',
                            'failed'   => 'Failed',
                            'refunded' => 'Refunded',
                        ])->native(false),
                        Select::make('courier_merchant_id')->label('Courier service')->relationship('courierMerchant', 'name')->searchable()->preload()->native(false),
                        TextInput::make('courier_tracking_id')->label('Tracking ID')->placeholder('e.g. PTH-20240581-XK'),
                        Textarea::make('admin_note')->label('Admin note (internal only)')->placeholder('Notes visible only to admins...')->rows(3)->columnSpanFull(),
                    ])->columns(2),

                    Section::make('Order Items')->icon('heroicon-o-shopping-cart')->description('Items included in this order.')->schema([
                        Placeholder::make('items_table')->label('')->content(fn($record) => view('filament.resources.order.partials.items-table', ['record' => $record])),
                    ]),

                    Section::make('Delivery Address')->icon('heroicon-o-map-pin')->description('Shipping details provided by the customer. Read-only.')->schema([
                        TextInput::make('shipping_name')->label('Recipient name')->disabled(),
                        TextInput::make('shipping_phone')->label('Phone number')->disabled(),
                        Textarea::make('shipping_address')->label('Full address')->disabled()->rows(2)->columnSpanFull(),
                        TextInput::make('shipping_district')->label('District')->disabled(),
                    ])->columns(2),
                ]),

                // ── Right sidebar (1/3 width) ───────────────────────────
                Group::make()->columnSpan(1)->schema([
                    Section::make('Customer')->icon('heroicon-o-user')->schema([
                        Placeholder::make('customer_info')->label('')->content(fn($record) => view('filament.resources.order.partials.customer-info', ['record' => $record])),
                    ]),
                    Section::make('Courier Info')->icon('heroicon-o-truck')->schema([
                        Placeholder::make('courier_info')->label('')->content(fn($record) => view('filament.resources.order.partials.courier-info', ['record' => $record])),
                    ]),
                    Section::make('Status History')->icon('heroicon-o-clock')->schema([
                        Placeholder::make('status_timeline')->label('')->content(fn($record) => view('filament.resources.order.partials.status-timeline', ['record' => $record])),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view'   => ViewOrder::route('/{record}'),
            'edit'   => EditOrder::route('/{record}/edit'),
        ];
    }
}
