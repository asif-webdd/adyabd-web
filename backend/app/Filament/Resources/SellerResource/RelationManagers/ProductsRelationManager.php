<?php

namespace App\Filament\Resources\SellerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Columns\ImageColumn, Filters\SelectFilter, Filters\TernaryFilter};
use Filament\Tables\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, ImageEntry, IconEntry, Grid};

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'Products';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('total_sales', 'desc')
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->disk('public')
                    ->defaultImageUrl(fn() => asset('images/product-default.png')),

                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->limit(40)
                    ->description(fn($record) => $record->sku ?? ''),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn($record) => $record->quantity <= ($record->low_stock_threshold ?? 5) ? 'danger' : 'success'),

                TextColumn::make('total_sales')
                    ->label('Units Sold')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->suffix('')
                    ->sortable()
                    ->color('warning'),

                IconColumn::make('is_active')->label('Active')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_featured')->label('Featured'),
                SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->actions([
                Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn($record) => !$record->is_active)
                    ->requiresConfirmation()->modalHeading('Approve product?')
                    ->action(function ($record) {
                        $record->approve();
                        \Filament\Notifications\Notification::make()->success()->title('Product approved')->send();
                    }),
                Action::make('deactivate')->label('Deactivate')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn($record) => (bool) $record->is_active)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => false]);
                        \Filament\Notifications\Notification::make()->success()->title('Product deactivated')->send();
                    }),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalHeading(fn($record) => $record->name)
                    ->modalWidth('4xl')
                    ->infolist(fn(Infolist $infolist, $record) =>
                    $infolist->record($record)->schema([
                        Section::make('Product Details')->schema([
                            Grid::make(2)->schema([
                                ImageEntry::make('primaryImage.image_path')
                                    ->label('Image')
                                    ->disk('public')
                                    ->size(120)
                                    ->defaultImageUrl(fn() => asset('images/product-default.png')),
                                \Filament\Infolists\Components\Group::make([
                                    TextEntry::make('name')->label('Name'),
                                    TextEntry::make('name_bn')->label('Name (Bangla)')->default('—'),
                                    TextEntry::make('sku')->label('SKU')->copyable()->default('—'),
                                    TextEntry::make('category.name')->label('Category')->badge()->color('gray'),
                                    TextEntry::make('brand.name')->label('Brand')->badge()->color('info')->default('—'),
                                ]),
                            ]),
                            TextEntry::make('short_description')->label('Short Description')->default('—'),
                        ]),

                        Section::make('Pricing & Stock')->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('price')->label('Price')->money('BDT'),
                                TextEntry::make('compare_price')->label('Compare Price')->money('BDT')->default('—'),
                                TextEntry::make('cost_price')->label('Cost Price')->money('BDT')->default('—'),
                                TextEntry::make('quantity')->label('Stock')->badge()
                                    ->color(fn($record) => $record->quantity <= ($record->low_stock_threshold ?? 5) ? 'danger' : 'success'),
                                TextEntry::make('low_stock_threshold')->label('Low Stock Alert')->default('5'),
                                TextEntry::make('unit')->label('Unit')->default('—'),
                                TextEntry::make('weight')->label('Weight')->suffix(' kg')->default('—'),
                            ]),
                        ]),

                        Section::make('Performance')->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('total_sales')->label('Units Sold')->badge()->color('primary'),
                                TextEntry::make('total_views')->label('Views')->badge()->color('gray'),
                                TextEntry::make('total_reviews')->label('Reviews')->badge()->color('info'),
                                TextEntry::make('rating')->label('Rating')->suffix(' ')->color('warning'),
                            ]),
                        ]),

                        Section::make('Flags')->schema([
                            Grid::make(4)->schema([
                                IconEntry::make('is_active')->label('Active')->boolean(),
                                IconEntry::make('is_featured')->label('Featured')->boolean(),
                                IconEntry::make('is_new')->label('New')->boolean(),
                                IconEntry::make('is_trending')->label('Trending')->boolean(),
                                IconEntry::make('free_shipping')->label('Free Shipping')->boolean(),
                                IconEntry::make('has_variants')->label('Has Variants')->boolean(),
                            ]),
                        ]),

                        Section::make('Timestamps')->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('created_at')->label('Created')->dateTime('d M Y, h:i A'),
                                TextEntry::make('updated_at')->label('Updated')->since(),
                            ]),
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
