<?php

namespace App\Filament\Resources\SellerResource\Pages;

use App\Filament\Resources\SellerResource;
use App\Filament\Resources\SellerResource\RelationManagers\{
    OrdersRelationManager,
    ProductsRelationManager,
    CustomersRelationManager,
    TransactionsRelationManager,
};
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, ImageEntry, IconEntry, Grid, Split, Group};
use Filament\Support\Enums\FontWeight;
use Filament\Actions\Action;

class ViewSeller extends ViewRecord
{
    protected static string $resource = SellerResource::class;

    protected function getHeaderActions(): array
    {
        $seller = $this->record;

        return [
            Action::make('view_orders')
                ->label('All Orders (' . $seller->orders()->count() . ')')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->slideOver()
                ->modalWidth('7xl')
                ->modalHeading('All Orders — ' . $seller->shop_name)
                ->modalContent(function () use ($seller) {
                    $orders = $seller->orders()
                        ->with(['user', 'items', 'courierMerchant'])
                        ->latest()
                        ->get();
                    return view('filament.seller.modals.orders-list', compact('orders'));
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('view_products')
                ->label('All Products (' . $seller->products()->count() . ')')
                ->icon('heroicon-o-cube')
                ->color('info')
                ->slideOver()
                ->modalWidth('7xl')
                ->modalHeading('All Products — ' . $seller->shop_name)
                ->modalContent(function () use ($seller) {
                    $products = $seller->products()
                        ->with(['category', 'brand', 'primaryImage'])
                        ->latest()
                        ->get();
                    return view('filament.seller.modals.products-list', compact('products'));
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('view_customers')
                ->label('Customers (' . $seller->orders()->distinct('user_id')->count('user_id') . ')')
                ->icon('heroicon-o-users')
                ->color('success')
                ->slideOver()
                ->modalWidth('5xl')
                ->modalHeading('Customers — ' . $seller->shop_name)
                ->modalContent(function () use ($seller) {
                    $customers = $seller->orders()
                        ->with('user')
                        ->whereNotNull('user_id')
                        ->selectRaw('user_id, COUNT(*) as total_orders, SUM(total_amount) as total_spent, MAX(created_at) as last_order_at')
                        ->groupBy('user_id')
                        ->orderByDesc('total_spent')
                        ->get();
                    return view('filament.seller.modals.customers-list', compact('customers'));
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('view_transactions')
                ->label('Transactions (' . $seller->transactions()->count() . ')')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->slideOver()
                ->modalWidth('6xl')
                ->modalHeading('Transaction History — ' . $seller->shop_name)
                ->modalContent(function () use ($seller) {
                    $transactions = $seller->transactions()->latest()->get();
                    return view('filament.seller.modals.transactions-list', compact('transactions'));
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('view_reviews')
                ->label('Reviews (' . $seller->reviews()->count() . ')')
                ->icon('heroicon-o-star')
                ->color('gray')
                ->slideOver()
                ->modalWidth('6xl')
                ->modalHeading('Customer Reviews — ' . $seller->shop_name)
                ->modalContent(function () use ($seller) {
                    $reviews = $seller->reviews()
                        ->with(['user', 'product'])
                        ->latest()
                        ->get();
                    return view('filament.seller.modals.reviews-list', compact('reviews'));
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $seller = $this->record;

        $totalOrders     = $seller->orders()->count();
        $deliveredOrders = $seller->orders()->where('status', 'delivered')->count();
        $pendingOrders   = $seller->orders()->where('status', 'pending')->count();
        $cancelledOrders = $seller->orders()->where('status', 'cancelled')->count();
        $totalRevenue    = $seller->orders()->sum('total_amount');
        $totalPayout     = $seller->orders()->sum('seller_payout');
        $totalCommission = $seller->orders()->sum('commission_amount');
        $totalProducts   = $seller->products()->count();
        $activeProducts  = $seller->products()->where('is_active', true)->count();
        $uniqueCustomers = $seller->orders()->distinct('user_id')->count('user_id');
        $avgOrderValue   = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $totalReviews    = $seller->reviews()->count();
        $avgRating       = $seller->reviews()->avg('reviews.rating') ?? 0;

        return $infolist->schema([

            Section::make('Shop Profile')->schema([
                Split::make([
                    Group::make([
                        ImageEntry::make('logo')
                            ->label('')
                            ->circular()
                            ->size(100)
                            ->defaultImageUrl(fn() => asset('images/shop-default.png')),
                    ])->grow(false),
                    Group::make([
                        TextEntry::make('shop_name')
                            ->label('Shop Name')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('shop_name_bn')
                            ->label('Shop Name (Bangla)')
                            ->hidden(fn($record) => empty($record->shop_name_bn)),
                        TextEntry::make('slug')->label('Slug')->prefix('/shop/')->color('gray'),
                        TextEntry::make('description')->label('Description')
                            ->hidden(fn($record) => empty($record->description)),
                    ])->grow(),
                ])->from('md'),
            ]),

            Section::make(' Performance Overview')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('_total_orders')->label('Total Orders')->state($totalOrders)->badge()->color('primary')->size(TextEntry\TextEntrySize::Large),
                    TextEntry::make('_delivered')->label('Delivered')->state($deliveredOrders)->badge()->color('success'),
                    TextEntry::make('_pending')->label('Pending')->state($pendingOrders)->badge()->color('warning'),
                    TextEntry::make('_cancelled')->label('Cancelled')->state($cancelledOrders)->badge()->color('danger'),
                ]),
                Grid::make(4)->schema([
                    TextEntry::make('_total_revenue')->label('Total Revenue')->state('BDT  ' . number_format($totalRevenue, 2))->color('success')->weight(FontWeight::Bold),
                    TextEntry::make('_total_payout')->label('Seller Payout')->state('BDT  ' . number_format($totalPayout, 2))->color('info'),
                    TextEntry::make('_commission')->label('Commission Earned')->state('BDT  ' . number_format($totalCommission, 2))->color('warning'),
                    TextEntry::make('_avg_order')->label('Avg Order Value')->state('BDT  ' . number_format($avgOrderValue, 2))->color('gray'),
                ]),
                Grid::make(4)->schema([
                    TextEntry::make('_total_products')->label('Total Products')->state($totalProducts)->badge()->color('primary'),
                    TextEntry::make('_active_products')->label('Active Products')->state($activeProducts)->badge()->color('success'),
                    TextEntry::make('_unique_customers')->label('Unique Customers')->state($uniqueCustomers)->badge()->color('info'),
                    TextEntry::make('total_sales')->label('Units Sold')->badge()->color('primary'),
                ]),
                Grid::make(4)->schema([
                    TextEntry::make('_total_reviews')->label('Total Reviews')->state($totalReviews)->badge()->color('gray'),
                    TextEntry::make('_avg_rating')->label('Avg Rating')->state(number_format($avgRating, 1) . ' ')->color('warning'),
                ]),
            ]),

            Section::make('Contact & Financial')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('phone')->label('Phone')->icon('heroicon-m-phone'),
                    TextEntry::make('email')->label('Email')->icon('heroicon-m-envelope'),
                    TextEntry::make('commission_rate')->label('Commission Rate')->suffix('%'),
                    TextEntry::make('balance')->label('Current Balance')->prefix('BDT ')->weight(FontWeight::Bold)->color('success'),
                    TextEntry::make('bkash_number')->label('bKash Number'),
                    TextEntry::make('bank_name')->label('Bank Name'),
                    TextEntry::make('bank_account')->label('Bank Account'),
                    TextEntry::make('trade_license')->label('Trade License'),
                    TextEntry::make('nid_number')->label('NID Number'),
                ]),
            ]),

            Section::make('Status & Verification')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('status')->label('Status')->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'active'              => 'success',
                            'pending'             => 'warning',
                            'suspended', 'banned' => 'danger',
                            default               => 'gray',
                        }),
                    IconEntry::make('is_verified')->label('Verified')->boolean(),
                    IconEntry::make('is_featured')->label('Featured')->boolean(),
                    TextEntry::make('rating')->label('Rating')->suffix(' ')->color('warning'),
                ]),
            ]),

            Section::make('Timeline')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('created_at')->label('Joined')->dateTime('d M Y, h:i A'),
                    TextEntry::make('updated_at')->label('Last Updated')->since(),
                    TextEntry::make('total_reviews')->label('Total Reviews')->badge()->color('info'),
                ]),
            ])->collapsed(),
        ]);
    }
}
