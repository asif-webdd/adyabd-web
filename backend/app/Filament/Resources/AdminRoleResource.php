<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SuperAdminOnly;
use App\Filament\Resources\AdminRoleResource\Pages\ManageAdminRoles;
use App\Models\AdminRole;
use App\Support\AdminAreas;
use Filament\Forms\Components\{CheckboxList, Textarea, TextInput, Toggle};
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\{DeleteAction, EditAction};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;

/** Roles = which parts of the admin panel a staff member may open. */
class AdminRoleResource extends Resource
{
    use SuperAdminOnly;

    protected static ?string $model = AdminRole::class;
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Admin Roles';
    protected static ?int    $navigationSort  = 51;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->unique(ignoreRecord: true)->maxLength(100),
            TextInput::make('description')->maxLength(255),
            Toggle::make('is_super')->label('Full access (everything, including Admin Users & Roles)')->live()->columnSpanFull(),
            CheckboxList::make('permissions')->label('Allowed areas')->options(AdminAreas::LABELS)->columns(2)->bulkToggleable()
                ->hidden(fn($get) => (bool) $get('is_super'))->columnSpanFull()
                ->helperText('Members of this role will only see and open the checked areas. Dashboard and "My Profile" are always available.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('description')->limit(50),
                IconColumn::make('is_super')->boolean()->label('Full access'),
                TextColumn::make('permissions')->label('Areas')
                    ->formatStateUsing(fn($state, AdminRole $r) => $r->is_super ? 'All' : (is_array($state) ? count($state) . ' area(s)' : '0'))
                    ->badge(),
                TextColumn::make('admins_count')->counts('admins')->label('Admins'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()->before(function (AdminRole $record, DeleteAction $action) {
                    if ($record->admins()->exists()) {
                        Notification::make()->danger()->title('This role is still assigned to admin users.')->send();
                        $action->cancel();
                    }
                }),
            ]);
    }

    public static function canDelete($record): bool
    {
        return static::superOnly();
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdminRoles::route('/')];
    }
}
