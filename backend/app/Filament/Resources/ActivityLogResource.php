<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Models\{ActivityLog, AdminUser};
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Infolists\Components\{Grid, Section, TextEntry};
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'system';
    protected static ?string $model            = ActivityLog::class;
    protected static ?string $navigationIcon   = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup  = 'Reports';
    protected static ?string $navigationLabel  = 'Activity Logs';
    protected static ?string $modelLabel       = 'Activity';
    protected static ?string $pluralModelLabel = 'Activity Log';
    protected static ?int    $navigationSort   = 4;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Event Overview')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('event')
                        ->label('Event Type')
                        ->badge()
                        ->color(fn(ActivityLog $r): string => $r->event_color),

                    TextEntry::make('log_name')->label('Model')->badge()->color('gray')->formatStateUsing(fn(mixed $state) => ucfirst($state)),
                    TextEntry::make('subject_id')->label('Record')->formatStateUsing(fn($state, ActivityLog $r) => class_basename($r->subject_type ?? '') . " #{$state}"),

                    TextEntry::make('created_at')
                        ->label('When')
                        ->dateTime('d M Y, h:i:s A')
                        ->icon('heroicon-o-clock'),
                ]),
                TextEntry::make('action')
                    ->label('Action')
                    ->columnSpanFull()
                    ->weight(FontWeight::SemiBold)
                    ->placeholder('—'),
            ]),

            Section::make('Performed By')->schema([
                Grid::make(4)->schema([
                    TextEntry::make('causer_name')
                        ->label('Name')
                        ->icon('heroicon-o-user')
                        ->default('System / API'),

                    TextEntry::make('causer_type')
                        ->label('Role')
                        ->formatStateUsing(fn(mixed $state) => $state ? class_basename($state) : 'System')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('ip_address')
                        ->label('IP Address')
                        ->icon('heroicon-o-globe-alt')
                        ->copyable()
                        ->default('—'),

                    TextEntry::make('user_agent')
                        ->label('User Agent')
                        ->limit(60)
                        ->tooltip(fn(ActivityLog $r) => $r->user_agent ?? '—')
                        ->default('—'),
                ]),
            ]),

            Section::make('Changes')
                ->schema([
                    Grid::make(2)->schema([
                        Section::make('Before')->schema([
                            TextEntry::make('properties.old')
                                ->label('')
                                ->columnSpanFull()
                                ->formatStateUsing(fn(mixed $state) => $state
                                    ? collect($state)->map(fn($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode("\n")
                                    : '—'
                                )
                                ->placeholder('No previous values'),
                        ])->extraAttributes(['class' => 'bg-red-50 dark:bg-red-950/20']),

                        Section::make('After')->schema([
                            TextEntry::make('properties.attributes')
                                ->label('')
                                ->columnSpanFull()
                                ->formatStateUsing(fn(mixed $state) => $state
                                    ? collect($state)->map(fn($v, $k) => "{$k}: " . (is_array($v) ? json_encode($v) : $v))->implode("\n")
                                    : '—'
                                )
                                ->placeholder('No new values'),
                        ])->extraAttributes(['class' => 'bg-green-50 dark:bg-green-950/20']),
                    ]),
                ])
                ->collapsible()
                ->visible(fn(ActivityLog $r) => !empty($r->properties)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('activity-log', ['ID' => 'id', 'Date' => 'created_at', 'Log' => 'log_name', 'Description' => 'description', 'Event' => 'event', 'Subject' => 'subject_type', 'Subject ID' => 'subject_id', 'By' => 'causer_name', 'IP' => 'ip_address'], [])])
            ->query(ActivityLog::query()->latest('created_at'))->columns([
            TextColumn::make('event')->label('Event')->badge()->color(fn(string $state): string => match ($state) {
                'created', 'login' => 'success',
                'updated'          => 'info',
                'deleted'          => 'danger',
                'restored'         => 'warning',
                'logout'           => 'gray',
                default            => 'gray',
            })->icon(fn(string $state): string => match ($state) {
                'created'  => 'heroicon-o-plus-circle',
                'updated'  => 'heroicon-o-pencil-square',
                'deleted'  => 'heroicon-o-trash',
                'restored' => 'heroicon-o-arrow-uturn-left',
                'login'    => 'heroicon-o-arrow-right-on-rectangle',
                'logout'   => 'heroicon-o-arrow-left-on-rectangle',
                default    => 'heroicon-o-ellipsis-horizontal-circle',
            }),

            TextColumn::make('log_name')
                ->label('Model')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn(mixed $state) => ucfirst($state))
                ->searchable(),

            TextColumn::make('action')
                ->label('Action')
                ->searchable()
                ->limit(60)
                ->tooltip(fn(ActivityLog $r) => $r->action ?? $r->description),

            TextColumn::make('causer_name')
                ->label('By')
                ->icon('heroicon-o-user')
                ->default('System')
                ->searchable(),

            TextColumn::make('ip_address')
                ->label('IP')
                ->copyable()
                ->placeholder('—')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('When')
                ->since()
                ->sortable()
                ->tooltip(fn(ActivityLog $r) => $r->created_at->format('d M Y, h:i:s A')),
        ])->filters([
            SelectFilter::make('event')->label('Event')->options([
                'created'  => 'Created',
                'updated'  => 'Updated',
                'deleted'  => 'Deleted',
                'restored' => 'Restored',
                'login'    => 'Login',
                'logout'   => 'Logout',
            ]),

            SelectFilter::make('log_name')->label('Model')->options(fn() => ActivityLog::query()
                ->distinct()->orderBy('log_name')
                ->pluck('log_name', 'log_name')
                ->mapWithKeys(fn($v) => [$v => ucfirst($v)])
                ->toArray()
            ),

            SelectFilter::make('causer_name')->label('User')->options(fn() => ActivityLog::query()->whereNotNull('causer_name')->distinct()->orderBy('causer_name')->pluck('causer_name', 'causer_name')->toArray()),
            Filter::make('today')->label('Today')->query(fn(Builder $q) => $q->whereDate('created_at', today()))->toggle(),

            Filter::make('date_range')->form([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('Until'),
            ])->query(fn(Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn($query, $date) => $query->whereDate('created_at', '>=', $date))
                ->when($data['until'] ?? null, fn($query, $date) => $query->whereDate('created_at', '<=', $date))
            ),
        ])
            ->actions([ViewAction::make()->label('Details')])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('No Activity Yet')
            ->emptyStateDescription('Every create, update, delete, login and logout across the app will appear here.');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view'  => ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $navigation = ActivityLog::whereDate('created_at', today())->count();
        return $navigation > 0 ? (string)$navigation : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'info';
    }
}
