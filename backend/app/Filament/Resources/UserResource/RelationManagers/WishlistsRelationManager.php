<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Actions\Action};
use Filament\Notifications\Notification;

class WishlistsRelationManager extends RelationManager
{
    protected static string $relationship = 'wishlists';
    protected static ?string $title       = ' Wishlists';
    protected static bool $isLazy         = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->product?->name)
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                TextColumn::make('product.price')
                    ->label('Price')
                    ->money('BDT'),

                TextColumn::make('product.compare_price')
                    ->label('MRP')
                    ->money('BDT')
                    ->toggleable(),

                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                TextColumn::make('product.seller.shop_name')
                    ->label('Seller / Shop')
                    ->default('—'),

                TextColumn::make('product.is_active')
                    ->label('In Stock?')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->delete();
                        Notification::make()->success()->title('Removed from wishlist')->send();
                    }),
            ])
            ->headerActions([])
            ->paginated([15, 25]);
    }
}
