<?php

namespace App\Filament\Resources;

use App\Models\BulkNotification;
use App\Jobs\SendBulkNotificationJob;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Textarea, Components\DateTimePicker};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Actions\ViewAction, Actions\DeleteAction, Actions\Action};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class BulkNotificationResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'marketing';
    protected static ?string $model = BulkNotification::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Notifications';
    protected static ?string $navigationLabel = 'Bulk Notifications';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('title')->required(),
                Select::make('channel')
                    ->options([
                        'sms'   => ' SMS',
                        'email' => ' Email',
                        'push'  => ' Push',
                        'all'   => 'All',
                    ])
                    ->required()
                    ->default('sms'),
                Select::make('audience')
                    ->options([
                        'all_users'      => 'All Customers',
                        'active_users'   => 'Active',
                        'inactive_users' => 'Inactive',
                        'sellers'        => 'Sellers',
                    ])
                    ->required()
                    ->default('all_users'),
                Textarea::make('message')->required()->rows(5)->columnSpanFull(),
                DateTimePicker::make('scheduled_at')->label('Scheduled Time')->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->limit(40),
                BadgeColumn::make('channel')
                    ->color(fn($state) => match($state) {
                        'sms'   => 'info',
                        'email' => 'primary',
                        'push'  => 'warning',
                        'all'   => 'success',
                        default => 'secondary',
                    }),
                TextColumn::make('audience')
                    ->formatStateUsing(fn($state) => match($state) {
                        'all_users'      => 'All',
                        'active_users'   => 'Active',
                        'inactive_users' => 'Inactive',
                        'sellers'        => 'Sellers',
                        default          => $state,
                    }),
                TextColumn::make('total_recipients')->badge()->color('primary'),
                TextColumn::make('sent_count')->badge()->color('success'),
                TextColumn::make('failed_count')->badge()->color('danger'),
                BadgeColumn::make('status')
                    ->color(fn($state) => match($state) {
                        'draft'     => 'secondary',
                        'queued'    => 'warning',
                        'sending'   => 'info',
                        'completed' => 'success',
                        'failed'    => 'danger',
                        default     => 'secondary',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                Action::make('send_now')
                    ->label('Send Now')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function($r) {
                        $r->update(['status' => 'queued']);
                        SendBulkNotificationJob::dispatch($r);
                        Notification::make()->success()->title('Sending started!')->send();
                    })
                    ->visible(fn($r) => in_array($r->status, ['draft', 'queued'])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\BulkNotificationResource\Pages\ListBulkNotifications::route('/'),
            'create' => \App\Filament\Resources\BulkNotificationResource\Pages\CreateBulkNotification::route('/create'),
            'view'   => \App\Filament\Resources\BulkNotificationResource\Pages\ViewBulkNotification::route('/{record}'),
        ];
    }
}
