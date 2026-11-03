<?php

namespace App\Filament\Resources;

use App\Models\{BlogPost, BlogCategory};
use Filament\Forms\Form;
use Filament\Forms\Components\{Section, TextInput, Select, Toggle, Textarea, FileUpload, RichEditor, DateTimePicker, TagsInput};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn, ImageColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction, Action};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class BlogPostResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'content';
    protected static ?string $model = BlogPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Blog Posts';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) BlogPost::where('status', 'draft')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Post Information')->schema([
                TextInput::make('title')
                    ->label('Title (English)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($set, $state) => $set('slug', \Str::slug($state))),
                TextInput::make('title_bn')
                    ->label('Title (Bangla)')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('blog_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('name_bn')->label('Name (Bangla)'),
                        TextInput::make('slug')->required(),
                        TextInput::make('color')->default('#006A4E'),
                    ]),
                Select::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => ' Draft',
                        'published' => ' Published',
                        'archived'  => ' Archived',
                    ])
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at')->label('Publish Date & Time'),
                Toggle::make('is_featured')->label('Featured Post'),
                Toggle::make('allow_comments')->label('Allow Comments')->default(true),
            ])->columns(2),

            Section::make('Image & Tags')->schema([
                FileUpload::make('featured_image')
                    ->label('Featured Image')
                    ->image()
                    ->directory('blog')
                    ->imageResizeTargetWidth('1200')  // ← string, not int
                    ->columnSpanFull(),
                TagsInput::make('tags')
                    ->label('Tags')
                    ->placeholder('Type a tag and press Enter')
                    ->columnSpanFull(),
            ]),

            Section::make('Content (English)')->schema([
                Textarea::make('excerpt')->label('Excerpt')->rows(3)->maxLength(500)->columnSpanFull(),
                RichEditor::make('content')
                    ->label('Full Content')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'link', 'orderedList', 'bulletList', 'blockquote', 'codeBlock', 'h2', 'h3', 'redo', 'undo']),
            ]),

            Section::make('Content (Bangla)')->schema([
                Textarea::make('excerpt_bn')->label('Excerpt (Bangla)')->rows(3)->columnSpanFull(),
                RichEditor::make('content_bn')->label('Full Content (Bangla)')->columnSpanFull(),
            ])->collapsed(),

            Section::make('SEO')->schema([
                TextInput::make('meta_title')->label('Meta Title')->maxLength(70),
                Textarea::make('meta_description')->label('Meta Description')->rows(2)->maxLength(160),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('blog-posts', ['ID' => 'id', 'Title' => 'title', 'Slug' => 'slug', 'Status' => 'status', 'Featured' => 'is_featured', 'Views' => 'view_count', 'Published' => 'published_at', 'Created' => 'created_at'], [])])
            ->columns([
                ImageColumn::make('featured_image')->label('Image')->width(80)->height(50),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn(BlogPost $record) => $record->category?->name),
                TextColumn::make('author.name')->label('Author')->placeholder('—'),
                TextColumn::make('view_count')->label('Views')->sortable()->badge()->color('info'),
                TextColumn::make('comments_count')->label('Comments')->counts('comments')->badge()->color('primary'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match($state) {
                        'published' => ' Published',
                        'draft'     => ' Draft',
                        'archived'  => ' Archived',
                        default     => $state,
                    })
                    ->color(fn(string $state) => match($state) {
                        'published' => 'success',
                        'draft'     => 'warning',
                        'archived'  => 'secondary',
                        default     => 'secondary',
                    }),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('published_at')->label('Published Date')->date('d M Y')->sortable()->placeholder('Unpublished'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']),
                SelectFilter::make('blog_category_id')->label('Category')->relationship('category', 'name'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('publish')
                    ->label(' Publish')
                    ->color('success')
                    ->action(fn(BlogPost $record) => $record->update(['status' => 'published', 'published_at' => now()])
                        && Notification::make()->success()->title('Post published successfully!')->send())
                    ->visible(fn(BlogPost $record) => $record->status !== 'published'),
                DeleteAction::make(),
            ])
            ->bulkActions([\Filament\Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\BlogPostResource\Pages\ListBlogPosts::route('/'),
            'create' => \App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost::route('/create'),
            'view'   => \App\Filament\Resources\BlogPostResource\Pages\ViewBlogPost::route('/{record}'),
            'edit'   => \App\Filament\Resources\BlogPostResource\Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
