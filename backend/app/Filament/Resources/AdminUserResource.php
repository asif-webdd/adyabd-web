<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SuperAdminOnly;
use App\Filament\Resources\AdminUserResource\Pages\ManageAdminUsers;
use App\Models\{AdminRole, AdminUser};
use Filament\Forms\Components\{Select, TextInput, Toggle};
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{DeleteAction, EditAction};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Staff accounts that can log in to this admin panel. */
class AdminUserResource extends Resource
{
    use SuperAdminOnly;

    protected static ?string $model = AdminUser::class;
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?int    $navigationSort  = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->tel()->maxLength(20),
            Select::make('role_id')->label('Role')->options(fn() => AdminRole::orderBy('name')->pluck('name', 'id'))
                ->placeholder('Full access (no role)')->helperText('Leave empty for full access, or choose a restricted role (Admin Users → Roles).'),
            TextInput::make('password')->password()->revealable()
                ->rule(Password::min(8))
                ->required(fn(string $operation) => $operation === 'create')
                ->dehydrated(fn($state) => filled($state))
                ->helperText('On edit, leave empty to keep the current password.'),
            Toggle::make('is_active')->label('Can log in')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role.name')->label('Role')->badge()->default('Full access'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('last_login_at')->since()->label('Last login')->placeholder('never'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (AdminUser $record, DeleteAction $action) {
                        if ($record->id === Auth::guard('admin')->id()) {
                            Notification::make()->danger()->title('You cannot delete your own account.')->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function canDelete($record): bool
    {
        return static::superOnly() && $record->id !== Auth::guard('admin')->id();
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdminUsers::route('/')];
    }
}
