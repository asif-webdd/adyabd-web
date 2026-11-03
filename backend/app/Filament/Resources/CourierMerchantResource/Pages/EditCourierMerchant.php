<?php

namespace App\Filament\Resources\CourierMerchantResource\Pages;

use App\Filament\Resources\CourierMerchantResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCourierMerchant extends EditRecord
{
    protected static string $resource = CourierMerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make("order-list")->label("Back")->icon('heroicon-o-arrow-uturn-left')->url(static::getResource()::getUrl('index'))
        ];
    }
}
