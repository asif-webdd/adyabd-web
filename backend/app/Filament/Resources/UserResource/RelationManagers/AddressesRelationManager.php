<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Actions\Action};
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid, IconEntry};

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    protected static ?string $title       = ' Addresses';
    protected static bool $isLazy         = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_default', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'home'   => ' Home',
                        'office' => ' Office',
                        'other'  => ' Other',
                        default  => ucfirst($state ?? '—'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'home'   => 'info',
                        'office' => 'warning',
                        default  => 'gray',
                    }),

                TextColumn::make('address_line')
                    ->label('Address')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address_line),

                TextColumn::make('district.name')
                    ->label('District')
                    ->default('—'),

                TextColumn::make('division.name')
                    ->label('Division')
                    ->default('—'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('postal_code')
                    ->label('Post Code')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view_address')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->modalHeading('Address Details')
                    ->infolist(fn (Infolist $infolist, $record) =>
                        $infolist->record($record)->schema([
                            Section::make()->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('name')->label('Recipient Name'),
                                    TextEntry::make('phone')->label('Phone')->copyable(),
                                    TextEntry::make('type')->label('Type')->badge(),
                                    \Filament\Infolists\Components\IconEntry::make('is_default')
                                        ->label('Default Address')->boolean(),
                                    TextEntry::make('address_line')->label('Street Address')->columnSpanFull(),
                                    TextEntry::make('upazila.name')->label('Upazila')->default('—'),
                                    TextEntry::make('district.name')->label('District')->default('—'),
                                    TextEntry::make('division.name')->label('Division')->default('—'),
                                    TextEntry::make('postal_code')->label('Postal Code')->default('—'),
                                ]),
                            ]),
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([])
            ->paginated(false);
    }
}
