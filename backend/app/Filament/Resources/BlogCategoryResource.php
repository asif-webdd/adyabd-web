<?php

namespace App\Filament\Resources;

use App\Models\BlogCategory;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Toggle};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Resources\Resource;

class BlogCategoryResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'content';
    protected static ?string $model = BlogCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Blog Categories';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($set, $state) => $set('slug', \Str::slug($state))),
            TextInput::make('name_bn')->label('Name (Bangla)'),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('color')->label('Color')->default('#006A4E'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('name_bn')->label('Name (Bangla)'),
                TextColumn::make('color')->badge()->formatStateUsing(fn(string $state) => $state),
                TextColumn::make('posts_count')->label('Posts')->counts('posts')->badge()->color('primary'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->reorderable('sort_order')
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories::route('/'),
            'create' => \App\Filament\Resources\BlogCategoryResource\Pages\CreateBlogCategory::route('/create'),
            'edit'   => \App\Filament\Resources\BlogCategoryResource\Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
