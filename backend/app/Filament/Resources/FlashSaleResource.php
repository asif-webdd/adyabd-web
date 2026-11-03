<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSaleResource\Pages\{CreateFlashSale, EditFlashSale, ListFlashSales, ViewFlashSale};
use App\Models\{FlashSale, Product};
use Filament\Forms\{
    Form,
    Components\Section,
    Components\TextInput,
    Components\Toggle,
    Components\DateTimePicker,
    Components\Repeater,
    Components\Select,
    Components\Placeholder,
    Components\Grid,
};
use Filament\Tables\{
    Table,
    Columns\TextColumn,
    Columns\IconColumn,
    Actions\EditAction,
    Actions\DeleteAction,
    Actions\Action,
    Filters\Filter,
    Filters\SelectFilter,
};
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{
    Section as InfoSection,
    TextEntry,
    IconEntry,
    Grid as InfoGrid,
    RepeatableEntry,
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class FlashSaleResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model = FlashSale::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Flash Sales';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $live = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->count();
        return $live ? (string) $live : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Left Column ───────────────────────────────────────────
            Grid::make(3)->schema([
                Section::make(' Flash Sale Details')->schema([
                    TextInput::make('title')
                        ->label('Title (English)')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Eid Mega Sale'),

                    TextInput::make('title_bn')
                        ->label('Title (Bangla)')
                        ->placeholder('e.g. Eid Mega Sale'),

                    TextInput::make('discount_percentage')
                        ->label('Global Discount')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->suffix('%')
                        ->helperText('Applied to all products unless overridden per-product'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Enable or disable this flash sale'),
                ])->columns(2)->columnSpan(2),

                Section::make('⏱ Schedule')->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Start Date & Time')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y h:i A'),

                    DateTimePicker::make('ends_at')
                        ->label('End Date & Time')
                        ->required()
                        ->native(false)
                        ->after('starts_at')
                        ->displayFormat('d M Y h:i A'),

                    Placeholder::make('duration')
                        ->label('Duration')
                        ->content(fn($record) => $record && $record->starts_at && $record->ends_at
                            ? $record->starts_at->diffForHumans($record->ends_at, true)
                            : '—'
                        ),

                    Placeholder::make('live_status')
                        ->label('Current Status')
                        ->content(function ($record) {
                            if (!$record) return new HtmlString('<span class="text-gray-400">Not saved yet</span>');
                            if (!$record->is_active) return new HtmlString('<span class="text-red-500 font-bold"> Inactive</span>');
                            if (now()->lt($record->starts_at)) return new HtmlString('<span class="text-yellow-500 font-bold">⏳ Upcoming</span>');
                            if (now()->gt($record->ends_at)) return new HtmlString('<span class="text-gray-400 font-bold"> Ended</span>');
                            return new HtmlString('<span class="text-red-500 font-bold animate-pulse"> LIVE NOW</span>');
                        }),
                ])->columnSpan(1),
            ]),

            // ── Products Repeater ─────────────────────────────────────
            Section::make(' Products in this Sale')->schema([
                Repeater::make('products')
                    ->relationship('products')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn() => Product::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('discount_percentage')
                            ->label('Discount (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('%')
                            ->helperText('Leave empty to use global discount'),

                        TextInput::make('special_price')
                            ->label('Special Price (BDT )')
                            ->numeric()
                            ->prefix('BDT ')
                            ->helperText('Fixed price override'),

                        TextInput::make('quantity_limit')
                            ->label('Stock Limit')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Unlimited'),

                        TextInput::make('sold_count')
                            ->label('Sold')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])
                    ->columns(3)
                    ->addActionLabel('+ Add Product')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn(array $state) =>
                        Product::find($state['product_id'] ?? null)?->name ?? 'New Product'
                    ),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('flash-sales', ['ID' => 'id', 'Title' => 'title', 'Discount %' => 'discount_percentage', 'Starts' => 'starts_at', 'Ends' => 'ends_at', 'Active' => 'is_active'], [])])
            ->columns([
                TextColumn::make('title')
                    ->label('Flash Sale')
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->description(fn(FlashSale $r) => $r->title_bn)
                    ->icon('heroicon-m-bolt'),

                TextColumn::make('discount_percentage')
                    ->label('Global Discount')
                    ->formatStateUsing(fn($state) => $state ? "{$state}% OFF" : '—')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('starts_at')
                    ->label('Start')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->icon('heroicon-m-play'),

                TextColumn::make('ends_at')
                    ->label('End')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->icon('heroicon-m-stop'),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->badge()
                    ->color('info')
                    ->suffix(' items'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn(FlashSale $r) => match(true) {
                        !$r->is_active                              => 'inactive',
                        now()->lt($r->starts_at)                   => 'upcoming',
                        now()->gt($r->ends_at)                     => 'ended',
                        default                                     => 'live',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'live'     => ' Live',
                        'upcoming' => '⏳ Upcoming',
                        'ended'    => ' Ended',
                        default    => ' Inactive',
                    })
                    ->color(fn($state) => match($state) {
                        'live'     => 'danger',
                        'upcoming' => 'warning',
                        'ended'    => 'success',
                        default    => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('ends_at')
                    ->label('Time Left')
                    ->getStateUsing(fn(FlashSale $r) =>
                    now()->lt($r->ends_at) && $r->is_active
                        ? now()->diffForHumans($r->ends_at, true) . ' left'
                        : '—'
                    )
                    ->color('warning')
                    ->icon('heroicon-m-clock'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Filter::make('live')
                    ->label(' Live Now')
                    ->query(fn(Builder $q) => $q
                        ->where('is_active', true)
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>=', now())
                    ),
                Filter::make('upcoming')
                    ->label('⏳ Upcoming')
                    ->query(fn(Builder $q) => $q
                        ->where('is_active', true)
                        ->where('starts_at', '>', now())
                    ),
                Filter::make('ended')
                    ->label(' Ended')
                    ->query(fn(Builder $q) => $q->where('ends_at', '<', now())),
                Filter::make('active')
                    ->label('Active Only')
                    ->query(fn(Builder $q) => $q->where('is_active', true)),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                Action::make('toggle')
                    ->label(fn(FlashSale $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn(FlashSale $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(FlashSale $r) => $r->is_active ? 'danger' : 'success')
                    ->action(function (FlashSale $record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()->success()
                            ->title($record->is_active ? 'Flash sale activated!' : 'Flash sale deactivated!')
                            ->send();
                    }),

                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (FlashSale $record) {
                        $new = $record->replicate();
                        $new->title = $record->title . ' (Copy)';
                        $new->is_active = false;
                        $new->save();

                        foreach ($record->products as $product) {
                            $p = $product->replicate();
                            $p->flash_sale_id = $new->id;
                            $p->sold_count = 0;
                            $p->save();
                        }

                        Notification::make()->success()->title('Flash sale duplicated!')->send();
                    }),

                EditAction::make()->icon('heroicon-o-pencil-square'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('activate_all')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn($records) => $records->each->update(['is_active' => true]))
                    ->requiresConfirmation(),
                \Filament\Tables\Actions\BulkAction::make('deactivate_all')
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-pause')
                    ->color('danger')
                    ->action(fn($records) => $records->each->update(['is_active' => false]))
                    ->requiresConfirmation(),
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFlashSales::route('/'),
            'create' => CreateFlashSale::route('/create'),
            'view'   => ViewFlashSale::route('/{record}'),
            'edit'   => EditFlashSale::route('/{record}/edit'),
        ];
    }
}
