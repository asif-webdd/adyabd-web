<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages\{CreateCategory, EditCategory, ListCategories};
use App\Models\Category;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\FileUpload};
use Illuminate\Support\Str;
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Columns\ImageColumn, Actions\EditAction, Actions\DeleteAction, Actions\Action, Filters\TernaryFilter};
use Filament\Resources\Resource;

class CategoryResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('name')
                    ->label('Name (English)')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($set, $state) => $set('slug', Str::slug($state))),
                TextInput::make('name_bn')->label('Name (Bangla)'),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('icon')
                    ->label('Icon')
                    ->placeholder('smartphone, checkroom...'),

                TextInput::make('color')
                    ->label('Color')
                    ->placeholder('#006A4E'),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->directory('categories'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Toggle::make('show_in_homepage')
                    ->label('Show in Homepage'),

                Toggle::make('show_in_menu')
                    ->label('Show in Menu')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('categories', ['ID' => 'id', 'Name' => 'name', 'Name (BN)' => 'name_bn', 'Slug' => 'slug', 'Parent ID' => 'parent_id', 'Active' => 'is_active', 'Home' => 'show_in_homepage', 'Menu' => 'show_in_menu', 'Sort' => 'sort_order'], [])])
            ->columns([
                ImageColumn::make('image')->size(40),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Category $record) => $record->parent?->name ?? 'Root Category'),

                TextColumn::make('name_bn')
                    ->label('Bangla Name')
                    ->searchable(),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('show_in_homepage')
                    ->label('Homepage')
                    ->boolean(),

                IconColumn::make('show_in_menu')
                    ->label('Menu')
                    ->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('show_in_homepage')->label('Homepage'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggle')->label(fn(Category $record) => $record->is_active ? ' Deactivate' : ' Activate')->action(fn(Category $record) => $record->update(['is_active' => !$record->is_active])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }
}
