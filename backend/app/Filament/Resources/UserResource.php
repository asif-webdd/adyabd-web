<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\IpBlocksRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WishlistsRelationManager;
use App\Models\{User, Transaction};
use App\Services\{BlockingService, SmsService};
use Filament\Forms\{Form,
    Components\Section,
    Components\TextInput,
    Components\Select,
    Components\Toggle,
    Components\Textarea,
    Components\DatePicker
};
use Filament\Tables\{Table,
    Columns\TextColumn,
    Columns\IconColumn,
    Actions\EditAction,
    Actions\ViewAction,
    Actions\Action,
    Filters\SelectFilter,
    Filters\Filter
};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'customers';
    protected static ?string $model           = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string)User::where('is_blocked', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([...\App\Filament\Support\CustomerImport::actions(), \App\Filament\Support\CsvExport::make('customers', ['ID' => 'id', 'Name' => 'name', 'Phone' => 'phone', 'Email' => 'email', 'Status' => 'status', 'Blocked' => 'is_blocked', 'Orders' => 'total_orders', 'Completed' => 'completed_orders', 'Cancelled' => 'cancel_order_count', 'Total spent' => 'total_spent', 'Wallet' => 'wallet_balance', 'Joined' => 'created_at', 'Last login' => 'last_login_at'], [])])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn(User $r) => $r->phone),
                TextColumn::make('total_orders')->label('Orders')->badge()->color('primary'),
                TextColumn::make('cancel_order_count')->label('Cancelled')->badge()->color(fn(string $state) => $state > 2 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
                TextColumn::make('no_answer_count')->label('No Answer')->badge()->color(fn(string $state) => $state > 2 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
                TextColumn::make('wallet_balance')->label('Wallet')->money('BDT'),
                TextColumn::make('status')->badge()->color(fn(string $state) => match ($state) {
                    'active'   => 'success',
                    'inactive' => 'warning',
                    'banned'   => 'danger',
                    default    => 'secondary',
                }),
                IconColumn::make('is_blocked')->label('Blocked')->boolean()->trueColor('danger')->falseColor('success'),
                TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable(),
            ])->defaultSort('created_at', 'desc')->filters([
                SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned']),
                Filter::make('blocked')->label('Blocked Users')->query(fn(Builder $q) => $q->where('is_blocked', true)),
                Filter::make('high_cancel')->label('3+ Cancellations')->query(fn(Builder $q) => $q->where('cancel_order_count', '>=', 3)),
            ])->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('block')->label('Block')->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()->form([
                    Textarea::make('reason')->required()->rows(3),
                    Select::make('duration')->options([
                        '7 days'    => '7 Days',
                        '30 days'   => '30 Days',
                        '90 days'   => '90 Days',
                        'permanent' => 'Permanent',
                    ])->default('permanent'),
                    Toggle::make('block_ip')->default(true),
                ])->action(function (User $record, array $data) {
                    app(BlockingService::class)->blockUser($record, $data['reason'], auth()->id(), $data['block_ip'] ?? true, $data['duration']);
                    Notification::make()->success()->title("{$record->name} has been blocked!")->send();
                })->visible(fn(User $r) => !$r->is_blocked),

                Action::make('unblock')->label('Unblock')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->action(function (User $record) {
                    app(BlockingService::class)->unblockUser($record, auth()->id());
                    Notification::make()->success()->title("{$record->name} has been unblocked!")->send();
                })->visible(fn(User $r) => $r->is_blocked),

                Action::make('send_sms')->label('SMS')->icon('heroicon-o-chat-bubble-left-ellipsis')->color('info')->form([
                    Textarea::make('message')->required()->rows(4),
                ])->action(fn(User $record, array $data) => app(SmsService::class)->send($record->phone, $data['message']) &&
                    Notification::make()->success()->title('SMS sent successfully!')->send()
                ),
                Action::make('add_wallet')->label('Add Wallet')->icon('heroicon-o-banknotes')->color('warning')->form([
                    TextInput::make('amount')->numeric()->required()->minValue(1),
                    Textarea::make('reason')->rows(2),
                ])->action(function (User $record, array $data) {
                    $record->increment('wallet_balance', $data['amount']);
                    Transaction::create([
                        'user_id'     => $record->id,
                        'type'        => 'credit',
                        'amount'      => $data['amount'],
                        'description' => 'Admin added: ' . ($data['reason'] ?? ''),
                        'status'      => 'completed',
                    ]);
                    Notification::make()->success()->title("BDT {$data['amount']} added successfully!")->send();
                }),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Personal Information')->schema([
                TextInput::make('name')->required(),
                TextInput::make('phone')->required(),
                TextInput::make('email')->email(),
                Select::make('gender')->options(['male' => 'Male', 'female' => 'Female']),
                DatePicker::make('date_of_birth'),
                Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'])->required(),
            ])->columns(2),

            Section::make('Statistics')->schema([
                TextInput::make('total_orders')->disabled(),
                TextInput::make('cancel_order_count')->disabled(),
                TextInput::make('failed_delivery_count')->disabled(),
                TextInput::make('no_answer_count')->disabled(),
                TextInput::make('wallet_balance')->disabled()->prefix('BDT '),
            ])->columns(3)->collapsed(),
        ]);
    }

    // ── Register Relation Managers ─────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            OrdersRelationManager::class,
            TransactionsRelationManager::class,
            AddressesRelationManager::class,
            ReviewsRelationManager::class,
            WishlistsRelationManager::class,
            IpBlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
