<?php

namespace App\Filament\Resources;

use App\Models\{Seller, Transaction};
use Filament\Forms\{
    Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea,
    Components\FileUpload
};
use Filament\Tables\{
    Table,
    Columns\TextColumn,
    Columns\BadgeColumn,
    Columns\IconColumn,
    Columns\ImageColumn,
    Actions\EditAction,
    Actions\ViewAction,
    Actions\Action
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class SellerResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'sellers';
    protected static ?string $model = Seller::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Sellers';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Shop Information')->schema([
                TextInput::make('shop_name')->required(),
                TextInput::make('shop_name_bn'),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->required(),
                TextInput::make('email')->email(),
                Textarea::make('description')->rows(3),
                FileUpload::make('logo')->image()->disk('public')->directory('sellers/logos'),
                FileUpload::make('banner')->label('Cover image')->image()->disk('public')->directory('sellers/banners'),
            ])->columns(2),

            Section::make('Financial Information')->schema([
                //TextInput::make('commission_rate')->numeric()->required()->default(10)->suffix('%'),
                TextInput::make('commission_rate')->label('Commission (%)')->numeric()->minValue(0)->maxValue(100)->suffix('%')
    ->placeholder('Global rate')->helperText('Leave blank to use the global rate from Settings → Commission (%).')
    ->formatStateUsing(fn ($record) => $record?->getRawOriginal('commission_rate')),
                TextInput::make('balance')->disabled()->prefix('BDT '),
                TextInput::make('bkash_number'),
                TextInput::make('bank_account'),
                TextInput::make('bank_name'),
                TextInput::make('trade_license'),
            ])->columns(2),

            Section::make('Status')->schema([
                Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                        'banned'    => 'Banned',
                    ])
                    ->required(),

                Toggle::make('is_verified'),
                Toggle::make('is_featured'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('sellers', ['ID' => 'id', 'Shop' => 'shop_name', 'Phone' => 'phone', 'Email' => 'email', 'Status' => 'status', 'Verified' => 'is_verified', 'Commission %' => 'commission_rate', 'Balance' => 'balance', 'bKash' => 'bkash_number', 'Bank' => 'bank_name', 'Bank account' => 'bank_account', 'Joined' => 'created_at'], [])])
            ->columns([
                ImageColumn::make('logo')->circular()->size(40),

                TextColumn::make('shop_name')
                    ->searchable()
                    ->description(fn(Seller $record): string => $record->phone ?? ''),

                TextColumn::make('commission_rate')->suffix('%'),

                TextColumn::make('balance')->money('BDT'),

                TextColumn::make('total_sales')->badge()->color('primary'),

                BadgeColumn::make('status')
                    ->color(fn(string $state): string => match ($state) {
                        'active'               => 'success',
                        'pending'              => 'warning',
                        'suspended', 'banned'  => 'danger',
                        default                => 'secondary',
                    }),

                IconColumn::make('is_verified')->boolean(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('approve')
                    ->label(' Approve')
                    ->action(fn(Seller $record) => $record->update([
                        'status'      => 'active',
                        'is_verified' => true,
                    ]))
                    ->visible(fn(Seller $record): bool => $record->status === 'pending'),

                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->modalDescription('The shop and its products disappear from the storefront and the seller cannot manage orders.')
                    ->action(fn(Seller $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn(Seller $record): bool => $record->status === 'active'),

                Action::make('reactivate')
                    ->label('Reactivate')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn(Seller $record) => $record->update(['status' => 'active']))
                    ->visible(fn(Seller $record): bool => in_array($record->status, ['suspended', 'banned'], true)),

                Action::make('pay')
                    ->label(' Make Payment')
                    ->form(fn(Seller $record) => [
                        TextInput::make('amount')
                            ->numeric()->required()->minValue(1)
                            ->maxValue((float) $record->balance)
                            ->helperText('Available balance: BDT ' . number_format((float) $record->balance, 2)),
                        TextInput::make('reference')->label('bKash / bank reference')->maxLength(100),
                    ])
                    ->action(function (Seller $record, array $data): void {
                        $done = \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $seller = Seller::whereKey($record->id)->lockForUpdate()->first();
                            $amount = (float) $data['amount'];
                            if ($amount <= 0 || $amount > (float) $seller->balance) {
                                return false;
                            }
                            $before = (float) $seller->balance;
                            $seller->decrement('balance', $amount);

                            Transaction::create([
                                'seller_id'      => $seller->id,
                                'type'           => 'withdrawal',
                                'amount'         => $amount,
                                'balance_before' => $before,
                                'balance_after'  => $before - $amount,
                                'description'    => 'Admin payment' . (!empty($data['reference']) ? " (ref: {$data['reference']})" : ''),
                                'status'         => 'completed',
                            ]);
                            return true;
                        });

                        $done
                            ? Notification::make()->success()->title("BDT {$data['amount']} payment completed!")->send()
                            : Notification::make()->danger()->title('Amount is more than the seller balance')->send();
                    })
                    ->visible(fn(Seller $record): bool => (float) $record->balance > 0),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\SellerResource\RelationManagers\OrdersRelationManager::class,
            \App\Filament\Resources\SellerResource\RelationManagers\ProductsRelationManager::class,
            \App\Filament\Resources\SellerResource\RelationManagers\CustomersRelationManager::class,
            \App\Filament\Resources\SellerResource\RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\SellerResource\Pages\ListSellers::route('/'),
            'create' => \App\Filament\Resources\SellerResource\Pages\CreateSeller::route('/create'),
            'view'   => \App\Filament\Resources\SellerResource\Pages\ViewSeller::route('/{record}'),
            'edit'   => \App\Filament\Resources\SellerResource\Pages\EditSeller::route('/{record}/edit'),
        ];
    }
}
