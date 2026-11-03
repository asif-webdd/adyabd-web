<?php

namespace App\Filament\Resources\BuilderResource\Pages;

use App\Filament\Resources\Builder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBuilder extends EditRecord
{
    protected static string $resource = Builder::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
