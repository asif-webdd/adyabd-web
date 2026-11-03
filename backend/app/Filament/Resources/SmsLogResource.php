<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsLogResource\Pages\ListSmsLogs;
use App\Models\SmsLog;
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

class SmsLogResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'marketing';
    protected static ?string $model            = SmsLog::class;
    protected static ?string $navigationIcon   = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup  = 'Reports';
    protected static ?string $navigationLabel  = 'SMS Logs';
    protected static ?string $modelLabel       = 'SMS Log';
    protected static ?string $pluralModelLabel = 'SMS Logs';
    protected static ?int    $navigationSort   = 2;

    public static function form(Form $form): Form { return $form->schema([]); }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Message Details')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('recipient_phone')
                        ->label('Recipient Phone')
                        ->icon('heroicon-o-phone')
                        ->copyable()
                        ->weight(FontWeight::Bold),

                    TextEntry::make('gateway')
                        ->label('Gateway')
                        ->icon('heroicon-o-signal')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'sent', 'delivered' => 'success',
                            'failed'            => 'danger',
                            default             => 'warning',
                        })
                        ->icon(fn (string $state): string => match ($state) {
                            'sent', 'delivered' => 'heroicon-o-check-circle',
                            'failed'            => 'heroicon-o-x-circle',
                            default             => 'heroicon-o-clock',
                        }),

                    TextEntry::make('cost')
                        ->label('Cost')
                        ->money('BDT')
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('Sent At')
                        ->icon('heroicon-o-calendar')
                        ->dateTime('d M Y, h:i A'),

                    TextEntry::make('gateway_message_id')
                        ->label('Gateway Message ID')
                        ->copyable()
                        ->placeholder('—'),
                ]),

                TextEntry::make('message')
                    ->label('Message Content')
                    ->columnSpanFull()
                    ->icon('heroicon-o-chat-bubble-left'),

                TextEntry::make('gateway_response')
                    ->label('Gateway Response (JSON)')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => $state
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : '—'
                    )
                    ->placeholder('No response data'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('sms-log', ['ID' => 'id', 'Date' => 'created_at', 'Phone' => 'recipient_phone', 'Message' => 'message', 'Gateway' => 'gateway', 'Status' => 'status', 'Reference' => 'reference_type', 'Cost' => 'cost'], [])])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('recipient_phone')
                    ->label('Recipient')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(55)
                    ->tooltip(fn (SmsLog $record): string => $record->message)
                    ->searchable(),

                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent', 'delivered' => 'success',
                        'failed'            => 'danger',
                        default             => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'sent', 'delivered' => 'heroicon-o-check-circle',
                        'failed'            => 'heroicon-o-x-circle',
                        default             => 'heroicon-o-clock',
                    }),

                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('BDT')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn (SmsLog $record): string => $record->created_at->format('d M Y, h:i:s A')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sent'      => 'Sent',
                        'delivered' => 'Delivered',
                        'failed'    => 'Failed',
                        'pending'   => 'Pending',
                    ]),

                SelectFilter::make('gateway')
                    ->label('Gateway')
                    ->options(fn () => SmsLog::query()
                        ->distinct()->orderBy('gateway')
                        ->pluck('gateway', 'gateway')
                        ->filter()
                        ->toArray()
                    ),

                Filter::make('today')
                    ->label('Today Only')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today()))
                    ->toggle(),

                Filter::make('failed_only')
                    ->label('Failed Only')
                    ->query(fn (Builder $query) => $query->where('status', 'failed'))
                    ->toggle(),

                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([ViewAction::make()->label('Details')])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->emptyStateHeading('No SMS Logs Found')
            ->emptyStateDescription('SMS logs will appear here once messages are sent through the system.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsLogs::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }

    public static function getNavigationBadge(): ?string
    {
        $failed = static::getModel()::where('status', 'failed')
            ->whereDate('created_at', today())
            ->count();
        return $failed > 0 ? (string) $failed : null;
    }

    public static function getNavigationBadgeColor(): string { return 'danger'; }
    public static function getNavigationBadgeTooltip(): string { return 'Failed SMS today'; }
}
