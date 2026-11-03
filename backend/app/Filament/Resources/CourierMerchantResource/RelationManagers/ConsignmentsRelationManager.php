<?php

namespace App\Filament\Resources\CourierMerchantResource\RelationManagers;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsignmentsRelationManager extends RelationManager
{
    protected static string  $relationship = 'orders';
    protected static ?string $title        = 'Consignments';
    protected static ?string $icon         = 'heroicon-o-archive-box';
    protected static bool    $isLazy       = false;

    public function table(Table $table): Table
    {
        return $table->defaultSort('courier_booked_at', 'desc')->columns([
            TextColumn::make('order_number')->label('Order #')->searchable()->sortable()->copyable()->copyMessage('Order # copied')->description(fn($record) => $record->courier_booked_at?->format('d M Y, h:i A') ?? '—')->weight(FontWeight::SemiBold),
            TextColumn::make('courier_tracking_id')->label('Tracking / Consignment ID')->searchable()->copyable()->copyMessage('Tracking ID copied')->default('—'),
            TextColumn::make('shipping_name')->label('Recipient')->searchable()->description(fn($record) => $record->shipping_phone),
            TextColumn::make('shipping_district')->label('District')->badge()->color('gray')->toggleable(),
            TextColumn::make('total_amount')->label('Order Total')->money('BDT')->sortable()->weight(FontWeight::Bold),
            TextColumn::make('payment_method')->label('COD?')->badge()->formatStateUsing(fn($state) => $state === 'cod' ? 'COD' : 'Prepaid')->color(fn($state) => $state === 'cod' ? 'warning' : 'gray'),
            TextColumn::make('courier_status')->label('Delivery Status')->badge()->formatStateUsing(fn($state) => match ($state) {
                'pending'           => '⏳ Pending',
                'in_review'         => ' In Review',
                'hold'              => '⏸ On Hold',
                'delivered'         => ' Delivered',
                'partial_delivered' => ' Partial Delivered',
                'cancelled'         => ' Cancelled',
                'booked'            => ' Booked',
                null, ''            => '—',
                default             => ucfirst(str_replace('_', ' ', (string)$state)),
            })->color(fn($state) => match ($state) {
                'delivered'                    => 'success',
                'partial_delivered'            => 'warning',
                'pending', 'in_review', 'hold' => 'info',
                'cancelled'                    => 'danger',
                'booked'                       => 'primary',
                default                        => 'gray',
            }),

            TextColumn::make('status')->label('Order Status')->badge()->formatStateUsing(fn(string $state) => match ($state) {
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
            })->color(fn(string $state): string => match ($state) {
                'delivered'                         => 'success',
                'pending'                           => 'warning',
                'confirmed', 'processing'           => 'info',
                'shipped', 'out_for_delivery'       => 'primary',
                'cancelled', 'returned', 'refunded' => 'danger',
                default                             => 'secondary',
            })->toggleable(),
            TextColumn::make('courier_booked_at')->label('Booked At')->dateTime('d M Y, h:i A')->sortable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('delivered_at')->label('Delivered At')->dateTime('d M Y, h:i A')->sortable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Filter::make('courier_status')->form([
                FormSelect::make('courier_status')->label('Delivery Status')->options([
                    'pending'           => 'Pending',
                    'booked'            => 'Booked',
                    'in_review'         => 'In Review',
                    'hold'              => 'On Hold',
                    'delivered'         => 'Delivered',
                    'partial_delivered' => 'Partial Delivered',
                    'cancelled'         => 'Cancelled',
                ])->placeholder('All delivery statuses'),
            ])->query(fn(Builder $q, array $data) => $q->when(
                $data['courier_status'] ?? null,
                fn($q, $v) => $q->where('courier_status', $v)
            ))->indicateUsing(fn(array $data) => $data['courier_status'] ? 'Delivery Status: ' . ucfirst(str_replace('_', ' ', $data['courier_status'])) : null),

            Filter::make('status')->form([
                FormSelect::make('status')->label('Order Status')->options([
                    'pending'          => 'Pending',
                    'confirmed'        => 'Confirmed',
                    'processing'       => 'Processing',
                    'shipped'          => 'Shipped',
                    'out_for_delivery' => 'Out for Delivery',
                    'delivered'        => 'Delivered',
                    'cancelled'        => 'Cancelled',
                    'returned'         => 'Returned',
                    'refunded'         => 'Refunded',
                ])->placeholder('All order statuses'),
            ])->query(fn(Builder $q, array $data) => $q->when(
                $data['status'] ?? null,
                fn($q, $v) => $q->where('status', $v)
            ))->indicateUsing(fn(array $data) => $data['status'] ? 'Order Status: ' . ucfirst(str_replace('_', ' ', $data['status'])) : null),

            // COD vs Prepaid as a clean Yes/No/All toggle. The previous
            // version of this filter was a Select with only "cod" as a
            // choosable option, so there was no explicit way to filter
            // for Prepaid-only — TernaryFilter gives both directions
            // plus an automatic indicator chip for free.
            TernaryFilter::make('payment_method')->label('Payment Type')->placeholder('All payment types')->trueLabel('Cash on Delivery (COD)')->falseLabel('Prepaid')->queries(
                true: fn(Builder $q) => $q->where('payment_method', 'cod'),
                false: fn(Builder $q) => $q->where('payment_method', '!=', 'cod'),
                blank: fn(Builder $q) => $q,
            ),

            Filter::make('shipping_district')->form([
                FormSelect::make('shipping_district')->label('District')->options(fn() => Order::query()->whereNotNull('shipping_district')->distinct()->orderBy('shipping_district')->pluck('shipping_district', 'shipping_district')->toArray())->placeholder('All districts')->searchable(),
            ])->query(fn(Builder $q, array $data) => $q->when(
                $data['shipping_district'] ?? null,
                fn($q, $v) => $q->where('shipping_district', $v)
            ))->indicateUsing(fn(array $data) => $data['shipping_district'] ? 'District: ' . $data['shipping_district'] : null),

            // Booked-date filter with quick presets (Today / Last 7 Days /
            // Last 30 Days) plus a manual Custom Range option. The two
            // DatePicker fields only appear once "Custom Range" is picked,
            // so the common cases stay a single click.
            Filter::make('booked_at_range')->label('Booked Date')->form([
                FormSelect::make('range_preset')->label('Date Range')->options([
                    'today'      => 'Today',
                    'last_week'  => 'Last 7 Days',
                    'last_month' => 'Last 30 Days',
                    'custom'     => 'Custom Range',
                ])->placeholder('All time')->live(),
                DatePicker::make('booked_from')->label('Booked From')->visible(fn(Get $get) => $get('range_preset') === 'custom'),
                DatePicker::make('booked_to')->label('Booked To')->visible(fn(Get $get) => $get('range_preset') === 'custom'),
            ])->query(function (Builder $q, array $data) {
                // Use the app's configured timezone explicitly, and
                // anchor every preset to full calendar-day boundaries
                // (startOfDay/endOfDay) rather than the exact current
                // time. A rolling "now() to now()->subDays(7)" window
                // silently excludes anything booked earlier in the
                // day 7 days ago — e.g. filtering at 2pm would miss a
                // record booked at 9am exactly 7 days back, even
                // though it's clearly "within the last 7 days."
                $tz = config('app.timezone');
                $now = Carbon::now($tz);

                return match ($data['range_preset'] ?? null) {
                    'today'      => $q->whereDate('courier_booked_at', $now->toDateString()),
                    'last_week'  => $q->whereBetween('courier_booked_at', [
                        $now->copy()->subDays(6)->startOfDay(),
                        $now->copy()->endOfDay(),
                    ]),
                    'last_month' => $q->whereBetween('courier_booked_at', [
                        $now->copy()->subDays(29)->startOfDay(),
                        $now->copy()->endOfDay(),
                    ]),
                    'custom'     => $q->when($data['booked_from'] ?? null, fn($q, $v) => $q->whereDate('courier_booked_at', '>=', $v))->when($data['booked_to'] ?? null, fn($q, $v) => $q->whereDate('courier_booked_at', '<=', $v)),
                    default      => $q,
                };
            })->indicateUsing(function (array $data) {
                $preset = $data['range_preset'] ?? null;

                if ($preset === 'today') return 'Booked: Today';
                if ($preset === 'last_week') return 'Booked: Last 7 Days';
                if ($preset === 'last_month') return 'Booked: Last 30 Days';

                if ($preset === 'custom') {
                    $parts = array_filter([
                        ($data['booked_from'] ?? null) ? 'from ' . Carbon::parse($data['booked_from'])->format('d M Y') : null,
                        ($data['booked_to'] ?? null) ? 'to ' . Carbon::parse($data['booked_to'])->format('d M Y') : null,
                    ]);

                    return $parts ? 'Booked: ' . implode(' ', $parts) : null;
                }

                return null;
            }),

            Filter::make('booked_only')->label('Booked with this courier only')->default()->query(fn(Builder $q) => $q->whereNotNull('courier_tracking_id')),
        ])->actions([
            Action::make('view_consignment')->label('View')->icon('heroicon-o-eye')->color('gray')->slideOver()->modalWidth('4xl')->modalHeading(fn($record) => 'Consignment — ' . $record->order_number)->infolist(function (Infolist $infolist) {
                $order = Order::with(['items', 'courierMerchant'])->findOrFail($infolist->getRecord()->getKey());
                return $infolist->record($order)->schema([
                    Section::make('Order Summary')->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('order_number')->label('Order #')->copyable(),
                            TextEntry::make('shipping_name')->label('Recipient'),
                            TextEntry::make('shipping_phone')->label('Phone')->copyable(),
                            TextEntry::make('shipping_address')->label('Address')->columnSpanFull(),
                            TextEntry::make('shipping_district')->label('District'),
                            TextEntry::make('shipping_upazila')->label('Upazila'),
                            TextEntry::make('total_amount')->label('Total')->money('BDT')->size(TextEntrySize::Large)->weight(FontWeight::Bold)->color('success'),
                        ]),
                    ]),

                    Section::make('Courier / Consignment Info')->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('courier_tracking_id')->label('Tracking / Consignment ID')->copyable()->default('—'),
                            TextEntry::make('courier_tracking_url')->label('Tracking URL')->copyable()->default('—')->columnSpanFull(),
                            TextEntry::make('courier_status')->label('Delivery Status')->badge()->color('info')->default('—'),
                            TextEntry::make('payment_method')->label('Payment Type')->badge()->formatStateUsing(fn($state) => $state === 'cod' ? 'Cash on Delivery' : 'Prepaid')->color(fn($state) => $state === 'cod' ? 'warning' : 'gray'),
                            TextEntry::make('courier_booked_at')->label('Booked At')->dateTime('d M Y, h:i A')->placeholder('—'),
                            TextEntry::make('delivered_at')->label('Delivered At')->dateTime('d M Y, h:i A')->placeholder('—'),
                            TextEntry::make('cancelled_at')->label('Cancelled At')->dateTime('d M Y, h:i A')->placeholder('—'),
                        ]),
                    ]),

                    Section::make('Order Items')->schema([
                        TextEntry::make('items_summary')->label('')->columnSpanFull()->getStateUsing(function ($record) {
                            $items = $record->items ?? collect();

                            if ($items->isEmpty()) {
                                return '—';
                            }

                            return $items->values()->map(function ($item, $i) {
                                $num = $i + 1;
                                $name = $item->product_name ?? 'Unknown Product';
                                $qty = $item->quantity ?? 1;
                                $unit = number_format($item->unit_price ?? 0, 2);
                                $total = number_format($item->total_price ?? 0, 2);

                                return "{$num}. {$name} — {$qty} × BDT {$unit} = BDT {$total}";
                            })->implode("\n");
                        }),
                    ]),

                    Section::make('Latest Webhook Payload')->schema([
                        TextEntry::make('last_webhook_payload')->label('')->columnSpanFull()->getStateUsing(function ($record) {
                            $response = $record->courier_response;

                            if (is_string($response)) {
                                $response = json_decode($response, true);
                            }

                            if (!is_array($response) || !isset($response['last_webhook'])) {
                                return 'No webhook received yet';
                            }

                            return json_encode($response['last_webhook'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }),
                    ])->collapsible(),
                ]);
            })->modalSubmitAction(false)->modalCancelActionLabel('Close'),

            Action::make('open_tracking')->label('Track')->icon('heroicon-o-map-pin')->color('primary')->url(fn($record) => $record->courier_tracking_url, shouldOpenInNewTab: true)->visible(fn($record) => (bool)$record->courier_tracking_url),
        ])->headerActions([])->paginated([10, 25, 50, 100]);
    }
}
