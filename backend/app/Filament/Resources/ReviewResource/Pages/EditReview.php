<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid};

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Show read-only review details above the edit form
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Review Details')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('user.name')->label('Customer')->icon('heroicon-m-user'),
                    TextEntry::make('user.phone')->label('Phone')->default('—'),
                    TextEntry::make('product.name')->label('Product')->icon('heroicon-m-cube'),
                    TextEntry::make('product.seller.shop_name')->label('Seller')->default('—'),
                ]),

                TextEntry::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn($state) =>
                        str_repeat('', (int)$state) . str_repeat('', 5 - (int)$state) . '  (' . $state . '/5)'
                    )
                    ->color(fn($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),

                TextEntry::make('title')->label('Review Title')->default('—'),
                TextEntry::make('review')->label('Review Text')->default('—'),
                TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y, h:i A'),
            ])->collapsible(),
        ]);
    }
}
