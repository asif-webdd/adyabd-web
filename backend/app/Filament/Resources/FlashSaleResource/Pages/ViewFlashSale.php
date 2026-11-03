<?php

namespace App\Filament\Resources\FlashSaleResource\Pages;

use App\Filament\Resources\FlashSaleResource;
use App\Models\OrderItem;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Collection;

class ViewFlashSale extends ViewRecord
{
    protected static string $resource = FlashSaleResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Status Banner ─────────────────────────────────────────
            Section::make()->schema([
                Grid::make(5)->schema([
                    TextEntry::make('title')
                        ->label('Flash Sale')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-bolt'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->getStateUsing(fn($record) => match (true) {
                            !$record->is_active           => 'inactive',
                            now()->lt($record->starts_at) => 'upcoming',
                            now()->gt($record->ends_at)   => 'ended',
                            default                       => 'live',
                        })
                        ->formatStateUsing(fn($state) => match ($state) {
                            'live'     => ' Live Now',
                            'upcoming' => '⏳ Upcoming',
                            'ended'    => ' Ended',
                            default    => ' Inactive',
                        })
                        ->color(fn($state) => match ($state) {
                            'live'     => 'danger',
                            'upcoming' => 'warning',
                            'ended'    => 'success',
                            default    => 'gray',
                        }),

                    TextEntry::make('discount_percentage')
                        ->label('Global Discount')
                        ->formatStateUsing(fn($state) => $state ? "{$state}% OFF" : '—')
                        ->badge()
                        ->color('danger'),

                    TextEntry::make('time_left')
                        ->label('Time Remaining')
                        ->getStateUsing(fn($record) => $record->is_active && now()->lt($record->ends_at)
                            ? now()->diffForHumans($record->ends_at, true) . ' left'
                            : '—'
                        )
                        ->color('warning')
                        ->icon('heroicon-m-clock'),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean(),
                ]),
            ])->columnSpanFull(),

            // ── Schedule + Stats ──────────────────────────────────────
            Grid::make(3)->schema([
                Section::make('⏱ Schedule')->schema([
                    TextEntry::make('starts_at')
                        ->label('Start Date & Time')
                        ->dateTime('d M Y, h:i A')
                        ->icon('heroicon-m-play'),

                    TextEntry::make('ends_at')
                        ->label('End Date & Time')
                        ->dateTime('d M Y, h:i A')
                        ->icon('heroicon-m-stop'),

                    TextEntry::make('duration')
                        ->label('Duration')
                        ->getStateUsing(fn($record) => $record->starts_at && $record->ends_at
                            ? $record->starts_at->diffForHumans($record->ends_at, true)
                            : '—'
                        )
                        ->icon('heroicon-m-clock'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y, h:i A'),
                ])->columnSpan(1),

                Section::make(' Performance Stats')->schema([
                    TextEntry::make('products_count')
                        ->label('Total Products')
                        ->getStateUsing(fn($record) => $record->products()->count())
                        ->badge()
                        ->color('info'),

                    TextEntry::make('total_sold')
                        ->label('Total Units Sold')
                        ->getStateUsing(fn($record) => $record->products()->sum('sold_count'))
                        ->badge()
                        ->color('success'),

                    TextEntry::make('total_quantity_limit')
                        ->label('Total Stock Limit')
                        ->getStateUsing(fn($record) => $record->products()->whereNotNull('quantity_limit')->sum('quantity_limit') ?: 'Unlimited'
                        )
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('remaining_stock')
                        ->label('Remaining Stock')
                        ->getStateUsing(fn($record) => $record->products()
                            ->whereNotNull('quantity_limit')
                            ->get()
                            ->sum(fn($p) => max(0, $p->quantity_limit - $p->sold_count)) ?: '—'
                        )
                        ->badge()
                        ->color('primary'),
                ])->columnSpan(1),

                Section::make(' Sale Info')->schema([
                    TextEntry::make('title_bn')
                        ->label('Bangla Title')
                        ->default('—'),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('d M Y, h:i A'),

                    TextEntry::make('progress')
                        ->label('Sale Progress')
                        ->getStateUsing(function ($record) {
                            if (now()->lt($record->starts_at)) return 'Not started yet';
                            if (now()->gt($record->ends_at)) return 'Completed';
                            $total = $record->starts_at->diffInMinutes($record->ends_at);
                            $elapsed = $record->starts_at->diffInMinutes(now());
                            $pct = $total > 0 ? round(($elapsed / $total) * 100) : 0;
                            return "{$pct}% elapsed";
                        })
                        ->badge()
                        ->color('info'),
                ])->columnSpan(1),
            ]),

            // ── Products Table ────────────────────────────────────────
            Section::make(' Products in this Sale')->schema([
                RepeatableEntry::make('products')->schema([
                    Grid::make(6)->schema([
                        ImageEntry::make('product.primaryImage.image_path')
                            ->label('')
                            ->height(56)
                            ->width(56)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                            ->defaultImageUrl(asset('images/shop-default.png')),

                        TextEntry::make('product.name')
                            ->label('Product')
                            ->weight(FontWeight::SemiBold)
                            ->columnSpan(2),

                        TextEntry::make('discount_percentage')
                            ->label('Discount')
                            ->formatStateUsing(fn($state) => $state ? "{$state}% OFF" : 'Global')
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('special_price')
                            ->label('Special Price')
                            ->formatStateUsing(fn($state) => $state ? 'BDT ' . number_format($state, 2) : '—'),

                        TextEntry::make('sold_vs_limit')
                            ->label('Sold / Limit')
                            ->getStateUsing(fn($record) => $record->sold_count . ' / ' . ($record->quantity_limit ?? '∞')
                            )
                            ->badge()
                            ->color(fn($record) => $record->quantity_limit && $record->sold_count >= $record->quantity_limit
                                ? 'danger' : 'success'
                            ),
                    ]),
                ])->contained(false),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle')
                ->label(fn() => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn() => $this->record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                ->color(fn() => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    Notification::make()->success()
                        ->title($this->record->is_active ? 'Activated!' : 'Deactivated!')
                        ->send();
                    $this->refreshFormData([]);
                }),

            EditAction::make()->icon('heroicon-o-pencil-square'),
            DeleteAction::make(),
            Action::make('sales_report')
                ->label('Sales Report')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('Flash Sale — Sales Report')
                ->modalWidth('7xl')
                ->modalContent(function () {
                    $record = $this->record;
                    $productIds = $record->products->pluck('product_id');

                    $orderItems = \App\Models\OrderItem::with(['order.user'])
                        ->whereIn('product_id', $productIds)
                        ->whereHas('order', fn($q) => $q
                            ->whereBetween('created_at', [$record->starts_at, $record->ends_at])
                            ->whereNotIn('status', ['cancelled', 'returned', 'refunded'])
                        )
                        ->get();

                    $totalOrders   = $orderItems->pluck('order_id')->unique()->count();
                    $totalUnits    = $orderItems->sum('quantity');
                    $totalRevenue  = $orderItems->sum('total_price');
                    $byProduct     = $orderItems->groupBy('product_id');
                    $flashProducts = $record->products->keyBy('product_id');

                    $rows = $byProduct->map(function ($items, $productId) use ($flashProducts) {
                        $fp = $flashProducts[$productId] ?? null;
                        return [
                            'name'       => $items->first()->product_name,
                            'units'      => $items->sum('quantity'),
                            'orders'     => $items->pluck('order_id')->unique()->count(),
                            'revenue'    => $items->sum('total_price'),
                            'limit'      => $fp?->quantity_limit ?? '∞',
                            'sold_count' => $fp?->sold_count ?? 0,
                            'discount'   => $fp?->discount_percentage ?? '—',
                        ];
                    })->sortByDesc('revenue');

                    $orders = \App\Models\Order::with(['items', 'user'])
                        ->whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))
                        ->whereBetween('created_at', [$record->starts_at, $record->ends_at])
                        ->whereNotIn('status', ['cancelled', 'returned', 'refunded'])
                        ->orderByDesc('created_at')
                        ->get();

                    return new \Illuminate\Support\HtmlString('
        <div class="space-y-6 p-2">

            <!-- Summary Cards -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-950 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">' . $totalOrders . '</div>
                    <div class="text-sm text-gray-500 mt-1">Total Orders</div>
                </div>
                <div class="bg-green-50 dark:bg-green-950 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">' . $totalUnits . '</div>
                    <div class="text-sm text-gray-500 mt-1">Units Sold</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-950 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">BDT ' . number_format($totalRevenue, 0) . '</div>
                    <div class="text-sm text-gray-500 mt-1">Total Revenue</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-950 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600">' . $byProduct->count() . '</div>
                    <div class="text-sm text-gray-500 mt-1">Products Sold</div>
                </div>
            </div>

            <!-- Per Product Breakdown -->
            <div>
                <h3 class="text-base font-semibold mb-3 text-gray-700 dark:text-gray-300"> Per Product Breakdown</h3>
                <table class="w-full text-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2">Product</th>
                            <th class="text-center px-4 py-2">Discount</th>
                            <th class="text-center px-4 py-2">Orders</th>
                            <th class="text-center px-4 py-2">Units Sold</th>
                            <th class="text-center px-4 py-2">Stock Limit</th>
                            <th class="text-right px-4 py-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $rows->map(fn($r) => '
                        <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2 font-medium">' . e($r['name']) . '</td>
                            <td class="px-4 py-2 text-center"><span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full">' . $r['discount'] . '%</span></td>
                            <td class="px-4 py-2 text-center">' . $r['orders'] . '</td>
                            <td class="px-4 py-2 text-center font-semibold">' . $r['units'] . ' / ' . $r['limit'] . '</td>
                            <td class="px-4 py-2 text-center">' . $r['limit'] . '</td>
                            <td class="px-4 py-2 text-right font-bold text-green-600">BDT ' . number_format($r['revenue'], 0) . '</td>
                        </tr>')->implode('') . '
                    </tbody>
                </table>
            </div>

            <!-- Order List -->
            <div>
                <h3 class="text-base font-semibold mb-3 text-gray-700 dark:text-gray-300"> Orders During This Flash Sale</h3>
                <table class="w-full text-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="text-left px-4 py-2">Order #</th>
                            <th class="text-left px-4 py-2">Customer</th>
                            <th class="text-left px-4 py-2">Items</th>
                            <th class="text-center px-4 py-2">Status</th>
                            <th class="text-center px-4 py-2">Payment</th>
                            <th class="text-right px-4 py-2">Total</th>
                            <th class="text-right px-4 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $orders->map(fn($o) => '
                        <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2 font-mono text-xs font-semibold text-blue-600">' . e($o->order_number) . '</td>
                            <td class="px-4 py-2">
                                <div class="font-medium">' . e($o->user?->name ?? '—') . '</div>
                                <div class="text-xs text-gray-400">' . e($o->shipping_phone ?? '') . '</div>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">' . $o->items->map(fn($i) => e($i->product_name) . ' x' . $i->quantity)->implode('<br>') . '</td>
                            <td class="px-4 py-2 text-center"><span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">' . ucfirst(str_replace('_', ' ', $o->status)) . '</span></td>
                            <td class="px-4 py-2 text-center"><span class="text-xs px-2 py-1 rounded-full ' . ($o->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') . '">' . ucfirst($o->payment_status) . '</span></td>
                            <td class="px-4 py-2 text-right font-bold text-green-600">BDT ' . number_format($o->total_amount, 0) . '</td>
                            <td class="px-4 py-2 text-right text-xs text-gray-400">' . $o->created_at->format('d M Y, h:i A') . '</td>
                        </tr>')->implode('') . '
                    </tbody>
                </table>
            </div>

        </div>');
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    private function getFlashSaleOrders(): Collection
    {
        $record = $this->record;
        $productIds = $record->products->pluck('product_id');

        return OrderItem::with(['order.user', 'order'])
            ->whereIn('product_id', $productIds)
            ->whereHas('order', fn($q) => $q
                ->whereBetween('created_at', [$record->starts_at, $record->ends_at])
                ->whereNotIn('status', ['cancelled', 'returned', 'refunded'])
            )
            ->get()
            ->groupBy('product_id');
    }
}
