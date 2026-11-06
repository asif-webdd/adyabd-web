<?php

namespace App\Filament\Resources;

use App\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\{EditReview, ListReviews};
use Filament\Forms\{Components\Textarea,
    Components\View,
    Form,
    Components\Toggle,
    Components\TextInput,
    Components\Section,
    Components\Grid,
    Components\Placeholder
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\{Actions\BulkAction,
    Actions\DeleteBulkAction,
    Columns\ViewColumn,
    Table,
    Columns\TextColumn,
    Columns\IconColumn,
    Columns\ImageColumn,
    Filters\SelectFilter,
    Filters\TernaryFilter,
    Actions\EditAction,
    Actions\DeleteAction,
    Actions\Action
};
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\{ImageEntry, Section as InfoSection, TextEntry, IconEntry, Grid as InfoGrid};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Throwable;

class ReviewResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model           = Review::class;
    protected static ?string $navigationIcon  = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Reviews';
    protected static ?int    $navigationSort  = 6;


    public static function getNavigationBadge(): ?string
    {
        try {
            return (string)Review::where('is_approved', false)->count() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Review Details')->schema([
                Grid::make(2)->schema([
                    TextInput::make('customer_name')->label('Customer')->disabled()->dehydrated(false)->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->name ?? '—')),
                    TextInput::make('product_name')->label('Product')->disabled()->dehydrated(false)->afterStateHydrated(fn($component, $record) => $component->state($record?->product?->name ?? '—')),
                    TextInput::make('rating')->label('Rating')->disabled()->formatStateUsing(fn($state) => str_repeat('', (int)$state) . str_repeat('', 5 - (int)$state) . '  (' . $state . '/5)'),
                    TextInput::make('created_at')->label('Submitted')->disabled(),
                ]),
                Textarea::make('review')->label('Review Text')->disabled()->rows(3),
            ])->collapsible(),


            Section::make('Moderation')->schema([
                Grid::make(2)->schema([
                    Toggle::make('is_approved')->label('Approved')->helperText('Approved reviews are visible to customers'),
                    Toggle::make('is_featured')->label('Featured')->helperText('Show this review prominently'),
                ]),
            ]),
            Section::make('Attachments')->schema([
                Placeholder::make('images_preview')->label('')->dehydrated(false)->content(fn($record) => new HtmlString(
                    collect(json_decode($record?->getRawOriginal('images') ?? '[]', true) ?? [])
                        ->map(fn($path) => str_starts_with($path, 'http') ? $path : storage_url($path))
                        ->map(fn($url) => '<a href="' . $url . '" target="_blank"><img src="' . $url . '" style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin:4px;display:inline-block;" onerror="this.style.display=\'none\'" /></a>')
                        ->implode('')
                )),
            ])->hidden(fn($record) => !$record || empty($record->getRawOriginal('images'))),
            Section::make('Admin Reply')->schema([
                Textarea::make('admin_reply')->label('Reply to this review')->rows(4)->placeholder('Write a public reply visible to all customers...')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('reviews', ['ID' => 'id', 'Date' => 'created_at', 'Product' => 'product.name', 'Customer' => 'user.name', 'Phone' => 'user.phone', 'Rating' => 'rating', 'Title' => 'title', 'Review' => 'review', 'Approved' => 'is_approved', 'Featured' => 'is_featured', 'Admin reply' => 'admin_reply'], ['product', 'user'])])
            ->defaultSort('created_at', 'desc')->columns([
            // Product thumbnail + name
            ImageColumn::make('product.primaryImage.image_path')->label('')->circular()->size(40)->defaultImageUrl(fn() => asset('images/product-default.png')),
            TextColumn::make('product.name')->label('Product')->searchable()->limit(30)->description(fn($record) => $record->product?->seller?->shop_name ?? ''),
            // Customer
            TextColumn::make('user.name')->label('Customer')->searchable()->description(fn($record) => $record->user?->phone ?? $record->user?->email ?? ''),
            // Star rating
            TextColumn::make('rating')->label('Rating')->formatStateUsing(fn($state) => str_repeat('', (int)$state) . str_repeat('', 5 - (int)$state))->color(fn($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),

            // Review excerpt
            TextColumn::make('review')->label('Review')->limit(60)->wrap()->description(fn($record) => $record->title ?? ''),
            ViewColumn::make('images')->label('Photos')->view('filament.tables.columns.review-images')->getStateUsing(fn($record) => collect(json_decode($record?->getRawOriginal('images') ?? '[]', true) ?? [])->map(fn($path) => str_starts_with($path, 'http') ? $path : storage_url($path))->toArray()),
            IconColumn::make('is_approved')->label('Approved')->boolean(),
            IconColumn::make('is_verified')->label('Verified')->boolean()->getStateUsing(fn(Review $review)=> static::verifyProductHasPurchased($review))->trueIcon('heroicon-o-shield-check')->falseIcon('heroicon-o-shield-exclamation')->trueColor('success')->falseColor('warning')->tooltip(fn(Review $record): string => match (true) {
                $record->order_id === null                                     => ' No linked order — review submitted without purchase',
                $record->order?->user_id !== $record->user_id                  => ' Order belongs to a different user',
                !in_array($record->order?->status, ['delivered', 'completed']) => ' Order not yet delivered (status: ' . ($record->order?->status ?? '—') . ')',
                default                                                        => ' Verified — purchased and delivered',
            }),
            IconColumn::make('is_featured')->label('Featured')->boolean(),
            TextColumn::make('created_at')->label('Date')->date('d M Y')->sortable(),
        ])->filters([
            TernaryFilter::make('is_approved')->label('Approved'),
            TernaryFilter::make('is_featured')->label('Featured'),
            TernaryFilter::make('is_verified')->label('Verified Purchase'),
            SelectFilter::make('rating')->label('Rating')->options([
                5 => ' (5)',
                4 => ' (4)',
                3 => ' (3)',
                2 => ' (2)',
                1 => ' (1)',
            ]),
        ])->actions([
            // Quick approve without opening edit
            Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')->action(function ($record) {
                $record->update(['is_approved' => true]);
                Notification::make()->success()->title('Review approved')->send();
            })->visible(fn($record) => !$record->is_approved)->requiresConfirmation(),

            Action::make('reject')->label('Hide / Reject')->icon('heroicon-o-x-circle')->color('danger')
                ->requiresConfirmation()->modalDescription('The review will not be shown on the website (it stays here and can be approved again).')
                ->action(function ($record) {
                    $record->update(['is_approved' => false, 'is_featured' => false]);
                    Notification::make()->success()->title('Review hidden')->send();
                })->visible(fn($record) => (bool) $record->is_approved),

            // View full details slide-over
            Action::make('view')->label('View')->icon('heroicon-o-eye')->color('gray')->slideOver()->modalWidth('2xl')->modalHeading('Review Details')->infolist(fn(Infolist $infolist, $record) => $infolist->record($record)->schema([
                InfoSection::make()->schema([
                    // Reviewer
                    InfoGrid::make(2)->schema([
                        TextEntry::make('user.name')->label('Customer')->icon('heroicon-m-user'),
                        TextEntry::make('user.phone')->label('Phone')->icon('heroicon-m-phone')->default('—'),
                        TextEntry::make('product.name')->label('Product')->icon('heroicon-m-cube'),
                        TextEntry::make('product.seller.shop_name')->label('Seller')->icon('heroicon-m-building-storefront')->default('—'),
                    ]),

                    // Stars
                    TextEntry::make('rating')->label('Rating')->formatStateUsing(fn($state) => str_repeat('', (int)$state) . str_repeat('', 5 - (int)$state) . '  (' . $state . '/5)')->color(fn($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                    TextEntry::make('title')->label('Title')->default('—'),
                    TextEntry::make('review')->label('Review')->default('—'),
                ]),

                // Images
                /*InfoSection::make('Photos')->schema([
                    TextEntry::make('images')->label('')->formatStateUsing(fn($state) => is_array($state) && count($state) ? implode("\n", $state) : 'No photos attached'),
                ])->hidden(fn($record) => empty($record->getRawOriginal('images'))),*/
                InfoSection::make('Photos')->schema([
                    ImageEntry::make('images')->label('')->size(120)->square()->stacked(false),
                ])->hidden(fn($record) => empty($record->getRawOriginal('images'))),

                // Admin reply
                InfoSection::make('Admin Reply')->schema([
                    TextEntry::make('admin_reply')->label('')->default('No reply yet.')->color('info'),
                    TextEntry::make('admin_replied_at')->label('Replied at')->dateTime('d M Y, h:i A')->hidden(fn($record) => !$record->admin_replied_at),
                ]),

                // Flags
                InfoSection::make('Status')->schema([
                    InfoGrid::make(3)->schema([
                        IconEntry::make('is_approved')->label('Approved')->boolean(),
                        IconEntry::make('is_verified')->label('Verified')->boolean()->getStateUsing(fn(Review $record)=> static::verifyProductHasPurchased($record))->trueIcon('heroicon-o-shield-check')->falseIcon('heroicon-o-shield-exclamation')->trueColor('success')->falseColor('warning'),
                        IconEntry::make('is_featured')->label('Featured')->boolean(),
                    ]),
                    InfoGrid::make(2)->schema([
                        TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y, h:i A'),
                        TextEntry::make('updated_at')->label('Last Updated')->since(),
                    ]),
                ]),
            ]))->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            EditAction::make()->icon('heroicon-o-pencil-square'),
            DeleteAction::make(),
        ])->bulkActions([
            BulkAction::make('approve_selected')->label('Approve Selected')->icon('heroicon-o-check-circle')->color('success')->action(function ($records) {
                $records->each->update(['is_approved' => true]);
                Notification::make()->success()->title(count($records) . ' reviews approved')->send();
            })->requiresConfirmation(),
            DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit'  => EditReview::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    private static function verifyProductHasPurchased(Review $record)
    {
        $record->load(['orderItem' => fn($query) => $query->with(['order']),]);
        // Condition A: must have a linked order
        if (!$record->order_item_id) return false;
        // Condition B: order must belong to the reviewer
        if ($record->orderItem?->user_id !== $record->user_id) return false;

        // Condition C: order must be in a delivered state
        if (!in_array($record->orderItem->order?->status, ['delivered', 'completed'])) return false;

        // Condition D: order must contain this product
        return $record->orderItem->where('product_id', $record->product_id)->exists();
    }

}
