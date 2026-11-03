<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewCoupon extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CouponResource::class;

    protected static string $view = 'filament.resources.coupon-resource.pages.view-coupon';

    // ── Info list ──────────────────────────────────────────────────────

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Hero Stats Row ────────────────────────────────────────
            Section::make()->schema([
                Grid::make(5)->schema([

                    TextEntry::make('code')
                        ->label('Coupon Code')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->copyable()
                        ->copyMessage('Code copied!')
                        ->icon('heroicon-m-ticket')
                        ->fontFamily(\Filament\Support\Enums\FontFamily::Mono),

                    TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'percentage' => '% Percentage',
                            'fixed'      => 'BDT  Fixed Amount',
                            'free_ship'  => ' Free Shipping',
                            default      => $state,
                        })
                        ->color(fn (string $state) => match ($state) {
                            'percentage' => 'info',
                            'fixed'      => 'warning',
                            'free_ship'  => 'success',
                            default      => 'gray',
                        }),

                    TextEntry::make('value')
                        ->label('Discount Value')
                        ->formatStateUsing(fn ($state, $record) => match ($record->type) {
                            'percentage' => $state . '%',
                            'fixed'      => 'BDT ' . number_format($state, 2),
                            default      => '—',
                        })
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->color('success'),

                    TextEntry::make('is_active')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                        ->color(fn (bool $state) => $state ? 'success' : 'danger'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y')
                        ->icon('heroicon-m-calendar'),
                ]),
            ])->columnSpanFull(),

            // ── Usage Stats ───────────────────────────────────────────
            Section::make(' Usage Statistics')->schema([
                Grid::make(4)->schema([

                    TextEntry::make('used_count')
                        ->label('Total Uses')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->icon('heroicon-m-users'),

                    TextEntry::make('usage_limit')
                        ->label('Usage Limit')
                        ->formatStateUsing(fn ($state) => $state ?? 'Unlimited')
                        ->icon('heroicon-m-adjustments-horizontal'),

                    TextEntry::make('remaining_uses')
                        ->label('Remaining Uses')
                        ->state(function ($record): string {
                            if (is_null($record->usage_limit)) return 'Unlimited';
                            return (string) max(0, $record->usage_limit - $record->used_count);
                        })
                        ->color(fn ($record) => match (true) {
                            is_null($record->usage_limit) => 'success',
                            ($record->usage_limit - $record->used_count) <= 0 => 'danger',
                            ($record->usage_limit - $record->used_count) <= 5 => 'warning',
                            default => 'success',
                        })
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-chart-bar'),

                    TextEntry::make('total_discount_given')
                        ->label('Total Discount Given')
                        ->state(fn ($record) => 'BDT ' . number_format(
                                Order::where('coupon_id', $record->id)->sum('coupon_discount'),
                                2
                            ))
                        ->color('danger')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-banknotes'),
                ]),

                Grid::make(4)->schema([
                    TextEntry::make('total_revenue')
                        ->label('Revenue Generated')
                        ->state(fn ($record) => 'BDT ' . number_format(
                                Order::where('coupon_id', $record->id)
                                    ->whereIn('status', ['delivered', 'completed'])
                                    ->sum('total_amount'),
                                2
                            ))
                        ->color('success')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-currency-dollar'),

                    TextEntry::make('unique_users')
                        ->label('Unique Customers')
                        ->state(fn ($record) => Order::where('coupon_id', $record->id)
                            ->distinct('user_id')
                            ->count('user_id')
                        )
                        ->color('info')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-user-group'),

                    TextEntry::make('avg_order_value')
                        ->label('Avg Order Value')
                        ->state(fn ($record) => 'BDT ' . number_format(
                                Order::where('coupon_id', $record->id)->avg('total_amount') ?? 0,
                                2
                            ))
                        ->icon('heroicon-m-calculator'),

                    TextEntry::make('usage_limit_per_user')
                        ->label('Per-User Limit')
                        ->formatStateUsing(fn ($state) => $state ?? '1')
                        ->icon('heroicon-m-user'),
                ]),
            ])->columnSpanFull(),

            // ── Coupon Details ────────────────────────────────────────
            Grid::make(3)->schema([

                Section::make(' Coupon Details')->schema([
                    TextEntry::make('description')
                        ->label('Description')
                        ->placeholder('No description')
                        ->columnSpanFull(),

                    TextEntry::make('min_order_amount')
                        ->label('Min. Order Amount')
                        ->formatStateUsing(fn ($state) => 'BDT ' . number_format($state ?? 0, 2)),

                    TextEntry::make('max_discount_amount')
                        ->label('Max. Discount Cap')
                        ->formatStateUsing(fn ($state) => $state ? 'BDT ' . number_format($state, 2) : 'No cap')
                        ->placeholder('No cap'),

                    IconEntry::make('is_for_new_users')
                        ->label('New Users Only')
                        ->boolean(),
                ])->columnSpan(1),

                Section::make(' Validity Period')->schema([
                    TextEntry::make('starts_at')
                        ->label('Valid From')
                        ->dateTime('d M Y, h:i A')
                        ->icon('heroicon-m-play'),

                    TextEntry::make('expires_at')
                        ->label('Expires At')
                        ->dateTime('d M Y, h:i A')
                        ->placeholder('Never expires')
                        ->icon('heroicon-m-stop')
                        ->color(fn ($record) => $record->expires_at?->isPast() ? 'danger' : null),

                    TextEntry::make('validity_status')
                        ->label('Validity')
                        ->state(function ($record): string {
                            if (!$record->is_active) return 'Inactive';
                            if ($record->expires_at?->isPast()) return 'Expired';
                            if ($record->usage_limit && $record->used_count >= $record->usage_limit) return 'Limit Reached';
                            return 'Valid';
                        })
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'Valid'    => 'success',
                            'Inactive' => 'gray',
                            default    => 'danger',
                        }),
                ])->columnSpan(1),

                Section::make(' Quick Stats')->schema([
                    TextEntry::make('orders_last_7_days')
                        ->label('Uses (Last 7 Days)')
                        ->state(fn ($record) => Order::where('coupon_id', $record->id)
                            ->where('created_at', '>=', now()->subDays(7))
                            ->count()
                        )
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('orders_last_30_days')
                        ->label('Uses (Last 30 Days)')
                        ->state(fn ($record) => Order::where('coupon_id', $record->id)
                            ->where('created_at', '>=', now()->subDays(30))
                            ->count()
                        )
                        ->badge()
                        ->color('info'),

                    TextEntry::make('delivered_orders')
                        ->label('Delivered Orders')
                        ->state(fn ($record) => Order::where('coupon_id', $record->id)
                            ->where('status', 'delivered')
                            ->count()
                        )
                        ->badge()
                        ->color('success'),

                    TextEntry::make('cancelled_orders')
                        ->label('Cancelled Orders')
                        ->state(fn ($record) => Order::where('coupon_id', $record->id)
                            ->where('status', 'cancelled')
                            ->count()
                        )
                        ->badge()
                        ->color('danger'),
                ])->columnSpan(1),
            ]),

        ]);
    }

    // ── Customer Usage Table ──────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->heading(' Customers Who Used This Coupon')
            ->description('All customers that redeemed this coupon, with their order history.')
            ->query(
                \App\Models\User::query()
                    ->whereHas('orders', fn ($q) => $q->where('coupon_id', $this->record->id))
                    ->withCount([
                        'orders as coupon_orders_count' => fn ($q) => $q->where('coupon_id', $this->record->id),
                    ])
                    ->withSum(
                        ['orders as coupon_total_spent' => fn ($q) => $q->where('coupon_id', $this->record->id)],
                        'total_amount'
                    )
                    ->withSum(
                        ['orders as coupon_total_discount' => fn ($q) => $q->where('coupon_id', $this->record->id)],
                        'coupon_discount'
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email ?? $record->phone ?? '—')
                    ->icon('heroicon-m-user-circle')
                    ->weight(FontWeight::Medium),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                TextColumn::make('coupon_orders_count')
                    ->label('Coupon Uses')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('coupon_total_spent')
                    ->label('Total Spent')
                    ->formatStateUsing(fn ($state) => 'BDT ' . number_format($state ?? 0, 2))
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                TextColumn::make('coupon_total_discount')
                    ->label('Total Saved')
                    ->formatStateUsing(fn ($state) => 'BDT ' . number_format($state ?? 0, 2))
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('total_orders')
                    ->label('All-Time Orders')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'active'))
                    ->color(fn ($state) => match ($state) {
                        'active'   => 'success',
                        'blocked'  => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('last_order_date')
                    ->label('Last Coupon Order')
                    ->state(fn ($record) => Order::where('coupon_id', $this->record->id)
                        ->where('user_id', $record->id)
                        ->latest()
                        ->value('created_at')
                    )
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('coupon_orders_count', 'desc')
            ->expandable(fn ($record) => $this->getOrdersForUser($record))
            ->actions([
                Tables\Actions\Action::make('view_orders')
                    ->label('Order History')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('primary')
                    ->modalHeading(fn ($record) => " Coupon Orders — {$record->name}")
                    ->modalContent(fn ($record) => $this->getOrderHistoryContent($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getOrderHistoryContent(\App\Models\User $user): \Illuminate\Contracts\View\View
    {
        $orders = Order::with(['items'])
            ->where('coupon_id', $this->record->id)
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('filament.resources.coupon-resource.partials.order-history', [
            'orders' => $orders,
            'user'   => $user,
        ]);
    }

    // ── Header Actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil-square'),

            Action::make('toggle_active')
                ->label(fn () => $this->record->is_active ? ' Deactivate' : ' Activate')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    Notification::make()
                        ->success()
                        ->title('Coupon ' . ($this->record->is_active ? 'activated' : 'deactivated') . '.')
                        ->send();
                    $this->refreshFormData(['is_active']);
                }),

            Action::make('copy_code')
                ->label(' Copy Code')
                ->color('gray')
                ->action(fn () => null)
                ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{$this->record->code}')"]),
        ];
    }
}
