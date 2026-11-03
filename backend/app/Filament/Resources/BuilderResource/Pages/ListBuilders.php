<?php

namespace App\Filament\Resources\BuilderResource\Pages;

use App\Filament\Resources\Builder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBuilders extends ListRecords
{
    protected static string $resource = Builder::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
