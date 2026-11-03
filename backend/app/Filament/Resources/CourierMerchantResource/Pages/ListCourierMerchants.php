<?php

namespace App\Filament\Resources\CourierMerchantResource\Pages;

use App\Filament\Resources\CourierMerchantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourierMerchants extends ListRecords
{
    protected static string $resource = CourierMerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
        ];
    }
}
