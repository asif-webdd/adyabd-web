<?php

namespace App\Filament\Resources\AutoBlockRuleResource\Pages;

use App\Filament\Resources\AutoBlockRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutoBlockRules extends ListRecords
{
    protected static string $resource = AutoBlockRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
        ];
    }
}
