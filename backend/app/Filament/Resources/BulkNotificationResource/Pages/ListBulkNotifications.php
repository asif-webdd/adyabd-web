<?php

namespace App\Filament\Resources\BulkNotificationResource\Pages;

use App\Filament\Resources\BulkNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBulkNotifications extends ListRecords
{
    protected static string $resource = BulkNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
        ];
    }
}
