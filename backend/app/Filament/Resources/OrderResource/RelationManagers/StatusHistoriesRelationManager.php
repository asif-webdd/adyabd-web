<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn};

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';
    protected static ?string $title = 'Status History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status'),
                TextColumn::make('note')->limit(60),
                TextColumn::make('changed_by'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
