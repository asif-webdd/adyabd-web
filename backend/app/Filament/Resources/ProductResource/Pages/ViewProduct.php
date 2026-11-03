<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->schema([
                ViewEntry::make('product_view')->label('')->columnSpanFull()->view('filament.resources.product.view-product')->viewData(['record' => $this->record]),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn(): bool => !$this->record->is_active)
                ->requiresConfirmation()->modalHeading('Approve product?')
                ->modalDescription('The product goes live on the store and the seller is notified.')
                ->action(function () { $this->record->approve(); $this->record->refresh(); Notification::make()->success()->title('Product approved')->send(); }),
            Action::make('activate')->label('Activate')->icon('heroicon-o-play-circle')->color('success')
                ->visible(fn(): bool => !$this->record->is_active)
                ->action(function () { $this->record->update(['is_active' => true]); Notification::make()->success()->title('Product activated')->send(); }),
            Action::make('deactivate')->label('Deactivate')->icon('heroicon-o-pause-circle')->color('warning')
                ->visible(fn(): bool => (bool) $this->record->is_active)->requiresConfirmation()
                ->action(function () { $this->record->update(['is_active' => false]); Notification::make()->success()->title('Product deactivated')->send(); }),
            DeleteAction::make(),
            EditAction::make(),
            Action::make("back")->label("Back")->icon('heroicon-m-arrow-left')->url(
                static::getResource()::getUrl('index')
            )->color('gray'),
        ];
    }
}
