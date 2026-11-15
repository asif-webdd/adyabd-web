<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages\{CreateCoupon, EditCoupon, ListCoupons};
use App\Models\Coupon;
use Filament\Forms\Form;
use Filament\Forms\Components\{
    Section, TextInput, Select, Toggle, DateTimePicker, Placeholder
};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Actions\{BulkAction,
    BulkActionGroup,
    DeleteBulkAction,
    EditAction,
    DeleteAction,
    ViewAction,
    Action};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter, Filter};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'catalog';
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Coupons';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Coupon::where('is_active', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Coupon Details')->schema([
                TextInput::make('code')
                    ->label('Coupon Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('e.g. SAVE20')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('generate')
                            ->icon('heroicon-o-arrow-path')
                            ->action(fn ($set) => $set('code', strtoupper(\Str::random(8))))
                    )
                    ->helperText('Uppercase letters and numbers only. Auto-generated or custom.'),

                TextInput::make('title')
                    ->label('Title')
                    ->maxLength(255)
                    ->placeholder('Optional, e.g. Eid Offer'),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->placeholder('Brief description of this coupon'),

                Select::make('type')
                    ->label('Discount Type')
                    ->options([
                        'percentage' => '% Percentage',
                        'fixed'      => 'BDT  Fixed Amount',
                        'free_ship'  => ' Free Shipping',
                    ])
                    ->required()
                    ->default('percentage')
                    ->reactive(),

                TextInput::make('value')
                    ->label(fn ($get) => $get('type') === 'percentage' ? 'Discount (%)' : 'Discount Amount (BDT )')
                    ->required(fn ($get) => $get('type') !== 'free_ship')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                    ->hidden(fn ($get) => $get('type') === 'free_ship')
                    ->placeholder(fn ($get) => $get('type') === 'percentage' ? '20' : '100'),

                TextInput::make('max_discount_amount')
                    ->label('Max Discount Amount (BDT )')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('Leave empty for no cap')
                    ->helperText('Maximum discount cap for percentage coupons.')
                    ->visible(fn ($get) => $get('type') === 'percentage'),

                TextInput::make('min_order_amount')
                    ->label('Minimum Order Amount (BDT )')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->placeholder('0'),
            ])->columns(2),

            Section::make('Usage Limits')->schema([
                TextInput::make('usage_limit')
                    ->label('Total Usage Limit')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('Leave empty for unlimited')
                    ->helperText('Maximum number of times this coupon can be used in total.'),

                TextInput::make('usage_limit_per_user')
                    ->label('Per-User Limit')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->helperText('How many times a single user can use this coupon.'),

                Placeholder::make('used_count')
                    ->label('Times Used')
                    ->content(fn (?Coupon $record) => $record ? $record->used_count : '—')
                    ->helperText('Read-only. Updated automatically on each use.'),

                Placeholder::make('remaining_uses')
                    ->label('Remaining Uses')
                    ->content(function (?Coupon $record): string {
                        if (! $record) return '—';
                        if (is_null($record->usage_limit)) return 'Unlimited';
                        return (string) max(0, $record->usage_limit - $record->used_count);
                    }),
            ])->columns(2),

            Section::make('Validity')->schema([
                DateTimePicker::make('starts_at')
                    ->label('Valid From')
                    ->default(now())
                    ->required(),

                DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->placeholder('Leave empty for no expiry')
                    ->after('starts_at'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive coupons cannot be redeemed even if still valid.'),

                Toggle::make('is_for_new_users')
                    ->label('New Users Only')
                    ->default(false)
                    ->helperText('If enabled, only first-time customers can use this coupon.'),
            ])->columns(2),

            Section::make('Applicable To')->schema([
                Select::make('applicable_to')
                    ->label('Apply To')
                    ->options([
                        'all'        => 'All Products',
                        'categories' => 'Specific Categories',
                        'products'   => 'Specific Products',
                    ])
                    ->default('all')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($set) => $set('applicable_ids', [])),

                Select::make('applicable_ids')
                    ->label(fn ($get) => $get('applicable_to') === 'categories' ? 'Select Categories' : 'Select Products')
                    ->multiple()
                    ->searchable()
                    ->preload(fn ($get) => $get('applicable_to') === 'categories')
                    ->required(fn ($get) => in_array($get('applicable_to'), ['categories', 'products']))
                    ->visible(fn ($get) => in_array($get('applicable_to'), ['categories', 'products']))
                    ->options(fn ($get) => $get('applicable_to') === 'categories'
                        ? \App\Models\Category::orderBy('name')->pluck('name', 'id')
                        : [])
                    ->getSearchResultsUsing(fn (string $search, $get) => $get('applicable_to') === 'categories'
                        ? \App\Models\Category::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')
                        : \App\Models\Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->getOptionLabelsUsing(fn (array $values, $get) => $get('applicable_to') === 'categories'
                        ? \App\Models\Category::whereIn('id', $values)->pluck('name', 'id')->all()
                        : \App\Models\Product::whereIn('id', $values)->pluck('name', 'id')->all())
                    ->columnSpanFull()
                    ->helperText('Coupon discount is calculated only on the selected items in the cart.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('coupons', ['ID' => 'id', 'Code' => 'code', 'Title' => 'title', 'Type' => 'type', 'Value' => 'value', 'Min order' => 'min_order_amount', 'Max discount' => 'max_discount_amount', 'Usage limit' => 'usage_limit', 'Used' => 'used_count', 'Active' => 'is_active', 'Starts' => 'starts_at', 'Expires' => 'expires_at'], [])])
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Coupon code copied!')
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'percentage' => '% Percentage',
                        'fixed'      => 'BDT  Fixed',
                        'free_ship'  => ' Free Ship',
                        default      => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'percentage' => 'info',
                        'fixed'      => 'warning',
                        'free_ship'  => 'success',
                        default      => 'secondary',
                    }),

                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state, Coupon $record) => match ($record->type) {
                        'percentage' => $state . '%',
                        'fixed'      => 'BDT ' . number_format($state, 2),
                        default      => '—',
                    })
                    ->sortable(),

                TextColumn::make('min_order_amount')
                    ->label('Min Order')
                    ->money('BDT')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('used_count')
                    ->label('Used')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('usage_limit')
                    ->label('Limit')
                    ->placeholder('Unlimited')
                    ->formatStateUsing(fn (?int $state, Coupon $record) =>
                    $state ? $record->used_count . ' / ' . $state : 'Unlimited'
                    ),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn (?Coupon $record) => $record?->expires_at?->isPast() ? 'danger' : null),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed'      => 'Fixed Amount',
                        'free_ship'  => 'Free Shipping',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Filter::make('expired')
                    ->label('Expired coupons')
                    ->query(fn (Builder $query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),

                Filter::make('usage_exhausted')
                    ->label('Usage exhausted')
                    ->query(fn (Builder $query) => $query->whereNotNull('usage_limit')
                        ->whereColumn('used_count', '>=', 'usage_limit')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('toggle_active')
                    ->label(fn (Coupon $record) => $record->is_active ? ' Deactivate' : ' Activate')
                    ->color(fn (Coupon $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (Coupon $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->success()
                            ->title('Coupon ' . ($record->is_active ? 'activated' : 'deactivated') . ' successfully.')
                            ->send();
                    }),

                Action::make('copy_code')
                    ->label(' Copy Code')
                    ->color('gray')
                    ->action(fn () => null) // handled by JS copy on frontend
                    ->tooltip('Copy coupon code to clipboard'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('deactivate_selected')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit'   => EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
