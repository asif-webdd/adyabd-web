<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\URL;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return  [
            Action::make("back")->label("Back")->icon('heroicon-m-arrow-left')->url(
                static::getResource()::getUrl('index')
            )->color('gray'),
        ];
    }
}
