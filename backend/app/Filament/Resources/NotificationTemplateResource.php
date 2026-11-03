<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages\CreateNotificationTemplate;
use App\Filament\Resources\NotificationTemplateResource\Pages\EditNotificationTemplate;
use App\Filament\Resources\NotificationTemplateResource\Pages\ListNotificationTemplates;
use App\Models\NotificationTemplate;
use Filament\Forms\{Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea};
use Filament\Tables\{Table,
    Columns\TextColumn,
    Columns\BadgeColumn,
    Columns\IconColumn,
    Actions\EditAction,
    Actions\DeleteAction,
    Filters\SelectFilter};
use Filament\Resources\Resource;

class NotificationTemplateResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'marketing';
    protected static ?string $model = NotificationTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Notifications';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')->required()->unique(ignoreRecord: true)->helperText('Used names: order_placed, order_confirmed, order_shipped, order_delivered, order_cancelled, order_delivery_failed, account_blocked, account_unblocked, otp'),
                TextInput::make('title')->required(),
                Select::make('type')->options([
                    'sms'    => ' SMS',
                    'email'  => ' Email',
                    'push'   => ' Push',
                    'in_app' => ' In-App',
                ])->required(),
                TextInput::make('subject')->label('Subject (Email)'),
                Textarea::make('body')->required()->rows(5)->columnSpanFull()->helperText('Variables: {shop_name} {helpline} {site_url} · orders: {order_number} {customer_name} {total_amount} {tracking_id} {tracking_url} · {otp} (otp) · {reason} (account_blocked). Switch the template off to stop that SMS.'),
                Toggle::make('is_active')->default(true),
                Toggle::make('is_system')->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->copyable(),
                TextColumn::make('title')->searchable(),
                BadgeColumn::make('type')
                    ->color(fn(string $state) => match ($state) {
                        'sms' => 'info',
                        'email' => 'primary',
                        'push' => 'warning',
                        'in_app' => 'success',
                        default => 'secondary',
                    }),
                TextColumn::make('body')->limit(60),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_system')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options(['sms' => 'SMS', 'email' => 'Email', 'push' => 'Push']),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()->visible(fn(NotificationTemplate $record) => !$record->is_system),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListNotificationTemplates::route('/'),
            'create' => CreateNotificationTemplate::route('/create'),
            'edit'   => EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
