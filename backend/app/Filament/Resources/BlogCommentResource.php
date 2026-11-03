<?php

namespace App\Filament\Resources;

use App\Models\BlogComment;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Toggle, Textarea};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction, Action};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class BlogCommentResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'content';
    protected static ?string $model = BlogComment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Blog Comments';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) BlogComment::where('is_approved', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('author_name')->label('Author Name'),
            Toggle::make('is_approved')->label('Approved'),
            Textarea::make('comment')->label('Comment')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('blog-comments', ['ID' => 'id', 'Date' => 'created_at', 'Author' => 'author_name', 'Email' => 'author_email', 'Comment' => 'comment', 'Approved' => 'is_approved'], [])])
            ->columns([
                TextColumn::make('post.title')->label('Post')->limit(30)->searchable(),
                TextColumn::make('author_name')->label('Commenter')
                    ->description(fn(BlogComment $record) => $record->user?->phone),
                TextColumn::make('comment')->label('Comment')->limit(60),
                IconColumn::make('is_approved')->label('Approved')->boolean(),
                TextColumn::make('created_at')->label('Date')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->label(' Approve')
                    ->color('success')
                    ->action(fn(BlogComment $record) => $record->update(['is_approved' => true])
                        && Notification::make()->success()->title('Comment approved!')->send())
                    ->visible(fn(BlogComment $record) => !$record->is_approved),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\BlogCommentResource\Pages\ListBlogComments::route('/'),
            'edit'  => \App\Filament\Resources\BlogCommentResource\Pages\EditBlogComment::route('/{record}/edit'),
        ];
    }
}
