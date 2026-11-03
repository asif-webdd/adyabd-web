<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SitePageResource\Pages\ManageSitePages;
use App\Models\SitePage;
use Filament\Forms\Components\{RichEditor, TextInput, Textarea, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;

/** About / Privacy / Terms / Return pages. Leave the content empty to keep the storefront's built-in text. */
class SitePageResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'content';
    protected static ?string $model = SitePage::class;
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Site Pages';
    protected static ?int    $navigationSort  = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->alphaDash()
                ->helperText('about, privacy, terms and return are used by the storefront pages. Other slugs are reachable via /api/v1/pages/{slug}.'),
            RichEditor::make('content')->columnSpanFull()
                ->helperText('Leave EMPTY to show the default text that ships with the website. Anything you write here replaces it.'),
            Textarea::make('meta_description')->rows(2)->maxLength(300)->columnSpanFull(),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug'),
                IconColumn::make('has_content')->label('Custom text')->boolean()->state(fn(SitePage $r) => trim((string) $r->content) !== ''),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->since(),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSitePages::route('/')];
    }
}
