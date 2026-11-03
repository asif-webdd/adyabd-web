<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryChargeResource\Pages\{CreateDeliveryCharge, EditDeliveryCharge, ListDeliveryCharges};
use App\Models\DeliveryCharge;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Grid};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\IconColumn, Columns\ToggleColumn, Actions\EditAction, Actions\DeleteAction, Filters\TernaryFilter, Filters\SelectFilter};
use Filament\Resources\Resource;

class DeliveryChargeResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'orders';
    protected static ?string $model = DeliveryCharge::class;

    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Delivery Charges';
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Area Delivery Charge')->schema([
                Grid::make(2)->schema([
                    TextInput::make('area_name')
                        ->label('Area Name')
                        ->placeholder('e.g. Dhaka, Khulna, Mirpur')
                        ->required()
                        ->maxLength(100),

                    Select::make('area_type')
                        ->label('Area Type')
                        ->options([
                            'division' => 'Division',
                            'district' => 'District',
                            'upazila'  => 'Upazila / City',
                        ])
                        ->default('district')
                        ->required()
                        ->helperText('District entries are matched against the customer\'s selected district.'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('charge')
                        ->label('Delivery Charge (BDT )')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix('BDT'),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                ]),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive areas fall back to the global standard delivery charge.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('area_name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'division' => 'info',
                        'district' => 'primary',
                        'upazila'  => 'warning',
                        default    => 'gray',
                    }),

                TextColumn::make('charge')
                    ->label('Charge')
                    ->formatStateUsing(fn($state) => 'BDT  ' . number_format($state, 0))
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                SelectFilter::make('area_type')
                    ->options([
                        'division' => 'Division',
                        'district' => 'District',
                        'upazila'  => 'Upazila',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDeliveryCharges::route('/'),
            'create' => CreateDeliveryCharge::route('/create'),
            'edit'   => EditDeliveryCharge::route('/{record}/edit'),
        ];
    }
}
