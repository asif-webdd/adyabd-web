<?php

namespace App\Filament\Resources;

use App\Models\IpBlock;
use App\Services\BlockingService;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Textarea, Components\DateTimePicker};
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\IconColumn, Actions\EditAction, Actions\DeleteAction, Actions\Action, Filters\Filter};
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class IpBlockResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'customers';
    protected static ?string $model = IpBlock::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'IP Blocks';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) IpBlock::where('is_active', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('ip_address')->required(),
                Select::make('user_id')
                    ->label('Associated Customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('type')
                    ->options([
                        'manual'               => 'Manual',
                        'auto_cancel'          => 'Auto Cancel',
                        'auto_no_answer'       => 'Auto No Answer',
                        'auto_failed_delivery' => 'Auto Failed Delivery',
                    ])
                    ->required(),
                Textarea::make('reason')->required()->rows(3),
                DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->nullable()
                    ->helperText('Empty = Permanent'),
                Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('ip-blocks', ['ID' => 'id', 'IP' => 'ip_address', 'Type' => 'type', 'Reason' => 'reason', 'Active' => 'is_active', 'Blocks' => 'block_count', 'Expires' => 'expires_at', 'Created' => 'created_at'], [])])
            ->columns([
                TextColumn::make('ip_address')
                    ->searchable()
                    ->copyable()
                    ->description(fn($r) => $r->country ? "{$r->city}, {$r->country}" : null),
                TextColumn::make('user.name')
                    ->placeholder('Unknown')
                    ->description(fn($r) => $r->user?->phone),
                TextColumn::make('reason')->limit(50)->tooltip(fn($r) => $r->reason),
                BadgeColumn::make('type')
                    ->formatStateUsing(fn($state) => match($state) {
                        'manual'               => 'Manual',
                        'auto_cancel'          => 'Cancelled',
                        'auto_no_answer'       => 'No Answer',
                        'auto_failed_delivery' => 'Failed Delivery',
                        default                => $state,
                    })
                    ->color('danger'),
                TextColumn::make('block_count')->badge()->color('danger'),
                TextColumn::make('expires_at')
                    ->formatStateUsing(fn($state) => $state ? $state->format('d M Y') : 'Permanent')
                    ->badge()
                    ->color('warning'),
                IconColumn::make('is_active')->boolean()->trueColor('danger')->falseColor('success'),
                TextColumn::make('created_at')->date('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('active')
                    ->label('Active Only')
                    ->query(fn(Builder $q) => $q->where('is_active', true))
                    ->default(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('unblock')
                    ->label('Unblocked')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function(IpBlock $record) {
                        $record->unblock();
                        if ($record->user && $record->user->is_blocked) {
                            app(BlockingService::class)->unblockUser($record->user, auth()->id());
                        }
                        Notification::make()->success()->title("IP {$record->ip_address}Unblocked!")->send();
                    })
                    ->visible(fn($r) => $r->is_active),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\IpBlockResource\Pages\ListIpBlocks::route('/'),
            'create' => \App\Filament\Resources\IpBlockResource\Pages\CreateIpBlock::route('/create'),
            'edit'   => \App\Filament\Resources\IpBlockResource\Pages\EditIpBlock::route('/{record}/edit'),
        ];
    }
}
