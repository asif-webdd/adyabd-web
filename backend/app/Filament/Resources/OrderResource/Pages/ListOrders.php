<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getFooter(): ?View
    {
        $cutoff = now()->subHours(24)->toISOString();

        return view('filament.resources.order.highlight-new-orders', [
            'cutoff' => $cutoff,
        ]);
    }
}
