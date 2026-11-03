<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages\{CreateBrand, EditBrand, ListBrands};
use App\Models\Brand;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Toggle, Components\Textarea, Components\FileUpload};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\IconColumn, Columns\ImageColumn, Actions\EditAction, Actions\DeleteAction, Actions\Action};
use Filament\Resources\Resource;

class BrandResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model = Brand::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Brands';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')
                    ->label('Brand Name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($set, $state) => $set('slug', \Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->directory('brands'),

                TextInput::make('website')
                    ->label('Website')
                    ->url(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('brands', ['ID' => 'id', 'Name' => 'name', 'Slug' => 'slug', 'Website' => 'website', 'Active' => 'is_active', 'Sort' => 'sort_order'], [])])
            ->columns([
                ImageColumn::make('logo')->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('website')
                    ->url(fn(Brand $record) => $record->website)
                    ->openUrlInNewTab()
                    ->placeholder('—'),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state) => $state ? 'Active' : 'Inactive')
                    ->color(fn(string $state) => $state ? 'success' : 'danger'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),

                Action::make('toggle')
                    ->label(fn(Brand $record) => $record->is_active ? ' Deactivate' : ' Activate')
                    ->action(fn(Brand $record) => $record->update(['is_active' => !$record->is_active])),

                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit'   => EditBrand::route('/{record}/edit'),
        ];
    }
}
