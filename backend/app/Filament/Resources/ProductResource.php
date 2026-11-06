<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\{CreateProduct, EditProduct, ListProducts, ViewProduct};
use App\Models\{Product, ProductImage, Order, OrderItem};
use Filament\Forms\{
    Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea,
    Components\FileUpload,
    Components\RichEditor,
    Components\Repeater,
    Components\TagsInput,
    Components\Grid,
    Components\Placeholder,
};
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Tables\{
    Actions\DeleteBulkAction,
    Table,
    Columns\TextColumn,
    Columns\BadgeColumn,
    Columns\ImageColumn,
    Actions\EditAction,
    Actions\DeleteAction,
    Actions\ViewAction,
    Actions\Action,
    Actions\BulkAction,
    Filters\SelectFilter,
    Filters\TernaryFilter,
    Filters\Filter
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model           = Product::class;
    protected static ?string $navigationIcon  = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Products';
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string)Product::where('quantity', '<=', 5)->where('quantity', '>', 0)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Information')->schema([
                TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn(callable $set, $state) => $set('slug', Str::slug($state))),
                TextInput::make('name_bn')->label('Name (Bangla)'),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true),
                Select::make('category_id')->relationship('category', 'name')->searchable()->preload()->required(),
                //Select::make('seller_id')->relationship('seller', 'shop_name')->searchable()->preload()->required(),
                Select::make('seller_id')->relationship('seller', 'shop_name')->searchable()->preload()->placeholder('— Admin / platform product —')->helperText('Leave blank for your own (platform) products.'),
                Select::make('brand_id')->relationship('brand', 'name')->searchable()->preload()->nullable(),
                TagsInput::make('tags')->placeholder('Add tags...')->columnSpanFull(),
            ])->columns(2),
            Section::make('Pricing & Stock')->schema([
                TextInput::make('price')->numeric()->required()->prefix('BDT ')->label('Selling Price'),
                TextInput::make('compare_price')->numeric()->prefix('BDT ')->label('Compare Price (MRP)'),
                TextInput::make('cost_price')->numeric()->prefix('BDT ')->label('Buying / Cost (Admin & Seller Only)')->helperText('This price is only visible to admins and sellers. Used for profit calculations.')->visible(fn() => true),
                TextInput::make('quantity')->numeric()->required()->default(0),
                TextInput::make('low_stock_threshold')->numeric()->default(5),
                TextInput::make('weight')->numeric()->suffix('kg'),
                TextInput::make('unit')->placeholder('pcs, kg, litre...'),
                TextInput::make('video_url')->label('Video URL')->url()->columnSpanFull(),
            ])->columns(2),
            Section::make('Description')->schema([
                Textarea::make('short_description')->rows(3)->maxLength(500)->columnSpanFull(),
                RichEditor::make('description')->columnSpanFull(),
                RichEditor::make('description_bn')->label('Description (Bangla)')->columnSpanFull(),
            ]),
            Section::make('Product Variants')->schema([
                Toggle::make('has_variants')->label('This product has variants')->live()->default(false),
                Repeater::make('variants')->relationship('variants')->schema([
                    Repeater::make('attribute_values')->label('Attributes')->schema([
                        TextInput::make('attribute')->label('Attribute')->placeholder('e.g. Color, Size, Weight')->required(),
                        TextInput::make('value')->label('Value')->placeholder('e.g. Red, XL, 1kg')->required(),
                    ])->columns(2)->addActionLabel('Add Attribute')->defaultItems(1)->columnSpanFull(),
                    TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true),
                    TextInput::make('price')->numeric()->prefix('BDT ')->required(),
                    TextInput::make('compare_price')->numeric()->prefix('BDT '),
                    TextInput::make('quantity')->numeric()->default(0),
                    FileUpload::make('image')->label('Variant Image')->image()->directory('products/variants')->nullable(),
                    Toggle::make('is_active')->default(true),
                ])->columns(3)->addActionLabel('Add Variant')->reorderableWithButtons()->collapsible()->itemLabel(function (array $state): ?string {
                    if (empty($state['attribute_values'])) return 'New Variant';
                    return collect($state['attribute_values'])->filter(fn($v) => !empty($v['attribute']) && !empty($v['value']))->map(fn($v) => "{$v['attribute']}: {$v['value']}")->join(' | ');
                })->visible(fn(callable $get) => $get('has_variants')),
            ])->collapsible(),

            Section::make('Images')->schema([
                Repeater::make('images')->relationship('images')->schema([
                    FileUpload::make('image_path')->label('Image')->image()->disk('public')->directory('products')->imageEditor()->required()->columnSpanFull(),
                    Toggle::make('is_primary')->label('Primary Image')->default(false)->helperText('If none is marked primary, the first image is used.'),
                ])->columns(2)->addActionLabel('Add Image')->reorderableWithButtons()->collapsible()->maxItems(8),
            ]),

            Section::make('Settings')->schema([
                Toggle::make('is_active')->default(true),
                Toggle::make('is_featured'),
                Toggle::make('is_new')->default(true),
                Toggle::make('is_trending'),
                Toggle::make('free_shipping'),
                Toggle::make('has_variants'),
            ])->columns(6),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('products', ['ID' => 'id', 'Name' => 'name', 'SKU' => 'sku', 'Category' => 'category.name', 'Seller' => 'seller.shop_name', 'Price' => 'price', 'Compare price' => 'compare_price', 'Cost' => 'cost_price', 'Stock' => 'quantity', 'Active' => 'is_active', 'Featured' => 'is_featured', 'Views' => 'total_views', 'Created' => 'created_at'], ['category','seller'])])
            ->modifyQueryUsing(fn(Builder $query) => $query->withCount('variants')->with('seller'))->columns([
            // ── Image ─────────────────────────────────────────────────────────────
            ImageColumn::make('primaryImage.image_path')->label('Image')->disk('public')->defaultImageUrl(asset('images/product-placeholder.png'))->width(50)->height(50)->circular(),
            // ── Name + Category ───────────────────────────────────────────────────
            TextColumn::make('name')->searchable()->sortable()->limit(30)->description(fn(Product $record): string => $record->category?->name ?? ''),
            // ── SKU ───────────────────────────────────────────────────────────────
            TextColumn::make('sku')->label('SKU')->searchable()->copyable(),
            // ── Selling Price ─────────────────────────────────────────────────────
            TextColumn::make('price')->money('BDT')->sortable()->label('Selling Price'),
            // ── Cost Price ────────────────────────────────────────────────────────
            TextColumn::make('cost_price')->money('BDT')->sortable()->label('Cost Price')->badge()->color('warning')->tooltip('Buying price — visible to admins & sellers only'),
            // ── Profit (price − cost_price) ───────────────────────────────────────
            TextColumn::make('profit')->label('Profit')->money('BDT')->sortable()->badge()->getStateUsing(fn(Product $record): float => max(0, (float)$record->price - (float)$record->cost_price))->color(fn(Product $record): string => match (true) {
                ((float)$record->price - (float)$record->cost_price) <= 0 => 'danger',
                ((float)$record->price - (float)$record->cost_price) < 50 => 'warning',
                default                                                   => 'success',
            })->tooltip('Profit per unit (selling price − cost price)'),

            // ── Margin % ──────────────────────────────────────────────────────────
            TextColumn::make('margin_percentage')->label('Margin %')->sortable()->badge()->getStateUsing(function (Product $record): string {
                if (!(float)$record->price) return '0%';
                $margin = (((float)$record->price - (float)$record->cost_price) / (float)$record->price) * 100;
                return number_format(max(0, min(100, $margin)), 1) . '%';
            })->color(function (Product $record): string {
                if (!(float)$record->price) return 'gray';
                $margin = (((float)$record->price - (float)$record->cost_price) / (float)$record->price) * 100;
                return match (true) {
                    $margin < 0  => 'danger',
                    $margin < 20 => 'warning',
                    $margin < 40 => 'primary',
                    default      => 'success',
                };
            })->tooltip('Gross margin percentage'),

            // ── Discount % ────────────────────────────────────────────────────────
            TextColumn::make('discount_percentage')->label('Discount')->badge()->getStateUsing(function (Product $record): string {
                if (!(float)$record->compare_price || $record->compare_price <= $record->price) {
                    return 'No MRP';
                }
                $pct = (($record->compare_price - $record->price) / $record->compare_price) * 100;
                return number_format($pct, 0) . '% off';
            })->color(function (Product $record): string {
                if (!(float)$record->compare_price || $record->compare_price <= $record->price) {
                    return 'gray';
                }
                $pct = (($record->compare_price - $record->price) / $record->compare_price) * 100;
                return $pct >= 20 ? 'success' : 'warning';
            }),

            // ── Stock ─────────────────────────────────────────────────────────────
            TextColumn::make('quantity')->label('Stock')->badge()->color(fn(int $state): string => match (true) {
                $state === 0 => 'danger',
                $state <= 5  => 'warning',
                default      => 'success',
            }),

            // ── Total Sales ───────────────────────────────────────────────────────
            TextColumn::make('total_sales')->label('Total Sales')->badge()->color('primary')->sortable(),
            // ── Revenue (price × total_sales) ─────────────────────────────────────
            TextColumn::make('revenue')->label('Revenue')->money('BDT')->sortable()->badge()->color('primary')->getStateUsing(fn(Product $record): float => (float)$record->price * (int)$record->total_sales)->tooltip('Estimated revenue: selling price × total sales'),
            // ── Rating ────────────────────────────────────────────────────────────
            TextColumn::make('rating')->label('Rating')->sortable()->badge()->color('warning')->formatStateUsing(fn($state, Product $record): string => $state ? ' ' . number_format((float)$state, 1) . ' (' . $record->total_reviews . ')' : '—')->tooltip('Average rating (number of reviews)'),
            // ── Total Views ───────────────────────────────────────────────────────
            TextColumn::make('total_views')->label('Views')->sortable()->badge()->color('gray')->formatStateUsing(fn($state): string => number_format((int)$state)),
            // ── Seller ────────────────────────────────────────────────────────────
            TextColumn::make('seller.shop_name')->label('Seller')->searchable()->badge()->color('info'),
            // ── Status ────────────────────────────────────────────────────────────
            BadgeColumn::make('is_active')->formatStateUsing(fn(bool $state): string => $state ? 'Active' : 'Inactive')->color(fn(bool $state): string => $state ? 'success' : 'danger'),
        ])->defaultSort('created_at', 'desc')->filters([
            SelectFilter::make('category')->relationship('category', 'name')->searchable()->preload(),
            SelectFilter::make('seller')->relationship('seller', 'shop_name')->searchable()->preload(),
            TernaryFilter::make('is_active'),
            Filter::make('pending_approval')->label('Waiting for approval (seller products)')->query(fn(Builder $query): Builder => $query->where('is_active', false)->whereNotNull('seller_id')),
            TernaryFilter::make('is_featured'),
            TernaryFilter::make('is_trending'),
            TernaryFilter::make('free_shipping'),
            Filter::make('low_stock')->label('Low Stock')->query(fn(Builder $query): Builder => $query->whereRaw('quantity <= low_stock_threshold AND quantity > 0')),
            Filter::make('out_of_stock')->label('Out of Stock')->query(fn(Builder $query): Builder => $query->where('quantity', 0)),
            Filter::make('high_margin')->label('High Margin (≥40%)')->query(fn(Builder $query): Builder => $query->whereRaw('price > 0 AND ((price - cost_price) / price) * 100 >= 40')),
            Filter::make('no_profit')->label('No Profit / Loss')->query(fn(Builder $query): Builder => $query->whereRaw('cost_price >= price')),
        ])->actions([
            ViewAction::make(),
            Action::make('approve')->label('Approve')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn(Product $record): bool => !$record->is_active)
                ->requiresConfirmation()->modalHeading('Approve product?')->modalDescription('The product goes live on the store and the seller is notified.')
                ->action(function (Product $record) {
                    $record->approve();
                    Notification::make()->success()->title('Product approved')->send();
                }),
            Action::make('toggle')->label(fn(Product $record): string => $record->is_active ? ' Deactivate' : ' Activate')->action(function (Product $record) {
                $record->update(['is_active' => !$record->is_active]);
                Notification::make()->success()->title('Status updated!')->send();
            }),
            EditAction::make(),
            DeleteAction::make(),
        ])->bulkActions([
            BulkAction::make('approve')->label('Approve selected')->icon('heroicon-o-check-circle')->requiresConfirmation()
                ->action(function ($records) {
                    $records->each(fn($r) => $r->is_active ? null : $r->approve());
                    Notification::make()->success()->title('Products approved')->send();
                }),
            BulkAction::make('activate')->label(' Activate')->action(fn($records) => $records->each->update(['is_active' => true])),
            BulkAction::make('deactivate')->label(' Deactivate')->action(fn($records) => $records->each->update(['is_active' => false])),
            DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
            'view'   => ViewProduct::route('/{record}'),
        ];
    }
}
