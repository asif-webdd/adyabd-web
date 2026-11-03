<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn};

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Products';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('product_name'),
            TextColumn::make('quantity'),
            TextColumn::make('unit_price')->money('BDT'),
            TextColumn::make('total_price')->money('BDT'),
        ]);
    }
}
