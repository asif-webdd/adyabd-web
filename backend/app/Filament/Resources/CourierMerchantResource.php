<?php

namespace App\Filament\Resources;

use App\Models\CourierMerchant;
use Filament\Forms\{Form,
    Components\Section,
    Components\TextInput,
    Components\Toggle,
    Components\Textarea,
    Components\FileUpload,
    Components\Actions\Action as FormAction,
    Get,
    Set
};
use Filament\Tables\{Table,
    Columns\TextColumn,
    Columns\BadgeColumn,
    Columns\IconColumn,
    Columns\ImageColumn,
    Actions\ViewAction,
    Actions\EditAction,
    Actions\DeleteAction,
    Actions\Action
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class CourierMerchantResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'orders';
    protected static ?string $model = CourierMerchant::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Courier Merchants';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Information')->schema([
                TextInput::make('name')->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('API slug: steadfast, pathao, redx, paperfly, sundarban'),
                FileUpload::make('logo')->image()->directory('couriers'),
                TextInput::make('base_url')->required()->url(),
                TextInput::make('tracking_url')->placeholder('https://example.com/track/{tracking_id}'),
            ])->columns(2),
            Section::make('API Credentials')->schema([
                TextInput::make('api_key')->password()->revealable(),
                TextInput::make('api_secret')->password()->revealable(),
                TextInput::make('api_username'),
                TextInput::make('api_password')->password()->revealable(),
            ])->columns(2),
            Section::make('Charge Settings')->schema([
                TextInput::make('base_charge')->numeric()->required()->default(60)->prefix('BDT '),
                TextInput::make('express_charge')->numeric()->required()->default(120)->prefix('BDT '),
                TextInput::make('cod_charge_percentage')->numeric()->default(1.0)->suffix('%'),
            ])->columns(3),
            Section::make('Features')->schema([
                Toggle::make('supports_cod')->default(true),
                Toggle::make('supports_same_day'),
                Toggle::make('is_active')->default(true),
                Toggle::make('is_default')->helperText('Setting this as default will unset others'),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(3),
            Section::make('Extra Config (JSON)')->schema([
                Textarea::make('config')->rows(4)->placeholder('{"store_id": 12345}'),
            ])->collapsed(),

            Section::make('Webhook Settings')
                ->description('Use this URL to receive delivery status updates from the courier.')
                ->icon('heroicon-o-bolt')
                ->schema([
                    TextInput::make('webhook_secret')
                        ->label('Webhook Secret')
                        ->placeholder('Click Generate to create one')
                        ->readOnly()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (TextInput $component, $state, $record) {
                            $component->state($record?->config['webhook_secret'] ?? null);
                        })
                        ->suffixAction(
                            FormAction::make('generateSecret')
                                ->label('Generate')
                                ->icon('heroicon-o-arrow-path')
                                ->color('primary')
                                ->action(function (Set $set, Get $get, $record) {
                                    $newSecret = bin2hex(random_bytes(20));
                                    $set('webhook_secret', $newSecret);

                                    if ($record) {
                                        $config = $record->config ?? [];
                                        $config['webhook_secret'] = $newSecret;
                                        $record->update(['config' => $config]);
                                        $set('config', json_encode($config, JSON_PRETTY_PRINT));
                                        $set('webhook_url', $record->fresh()->getWebhookUrl());

                                        \Filament\Notifications\Notification::make()
                                            ->success()
                                            ->title('New webhook secret generated')
                                            ->send();
                                    }
                                })
                        )
                        ->columnSpanFull(),

                    TextInput::make('webhook_url')
                        ->label('Webhook URL')
                        ->readOnly()
                        ->dehydrated(false)
                        ->extraInputAttributes(['onclick' => 'this.select();'])
                        ->placeholder('The URL will appear here after saving')
                        ->afterStateHydrated(function (TextInput $component, $state, $record) {
                            $component->state($record?->getWebhookUrl());
                        })
                        ->helperText('Click the field to select the URL, copy it with Ctrl+C, then paste it into the courier panel\'s Delivery Status Webhook field. (A copy button is available on the View page.)')
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->visible(fn(?CourierMerchant $record) => (bool) $record),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->circular()->size(40),
                TextColumn::make('name')->searchable()->description(fn(CourierMerchant $record) => $record->slug),
                TextColumn::make('base_charge')->money('BDT'),
                TextColumn::make('express_charge')->money('BDT'),
                TextColumn::make('total_deliveries')->badge()->color('primary'),
                TextColumn::make('success_rate')
                    ->suffix('%')
                    ->badge()
                    ->color(fn(string $state) => $state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger')),
                IconColumn::make('supports_cod')->boolean(),
                IconColumn::make('is_default')->boolean()->trueColor('warning'),
                BadgeColumn::make('is_active')
                    ->formatStateUsing(fn(string $state) => $state ? 'Active' : 'Inactive')
                    ->color(fn(string $state) => $state ? 'success' : 'danger'),
            ])
            ->reorderable('sort_order')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('set_default')
                    ->label('Set Default')
                    ->action(function (CourierMerchant $record) {
                        $record->setAsDefault();
                        Notification::make()->success()->title("{$record->name} is now the default!")->send();
                    })
                    ->visible(fn(CourierMerchant $record) => !$record->is_default),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\CourierMerchantResource\Pages\ListCourierMerchants::route('/'),
            'create' => \App\Filament\Resources\CourierMerchantResource\Pages\CreateCourierMerchant::route('/create'),
            'view'   => \App\Filament\Resources\CourierMerchantResource\Pages\ViewCourierMerchant::route('/{record}'),
            'edit'   => \App\Filament\Resources\CourierMerchantResource\Pages\EditCourierMerchant::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CourierMerchantResource\RelationManagers\ConsignmentsRelationManager::class,
        ];
    }
}
