<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackingLogResource\Pages;
use App\Models\TrackingLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackingLogResource extends Resource
{
    protected static ?string $model = TrackingLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports & Analytics';

    protected static ?string $navigationLabel = 'Tracking Log';

    protected static ?string $modelLabel = 'Tracking Log';

    protected static ?string $pluralModelLabel = 'Tracking Log';

    public static function canCreate(): bool
    {
        return false;
    }

    /** একটা platform (meta / ga4) এর ফলাফল: ok | failed | skipped | — */
    public static function platformStatus(TrackingLog $record, string $platform): string
    {
        foreach ($record->results ?? [] as $r) {
            if (($r['platform'] ?? null) === $platform) {
                return $r['status'] ?? '—';
            }
        }

        return '—';
    }

    public static function firstError(TrackingLog $record): ?string
    {
        foreach ($record->results ?? [] as $r) {
            if (($r['status'] ?? null) === 'failed') {
                $platform = strtoupper((string) ($r['platform'] ?? ''));

                return trim($platform . ': ' . ($r['message'] ?? 'failed'));
            }
        }

        return null;
    }

    public static function table(Table $table): Table
    {
        $statusColor = fn (string $state): string => match ($state) {
            'ok'     => 'success',
            'failed' => 'danger',
            default  => 'gray',
        };

        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M, h:i:s A')
                    ->timezone(\App\Support\TrackingAnalytics::TZ)
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_name')
                    ->label('Event')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'server' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : '৳' . number_format((float) $state, 0)),
                Tables\Columns\TextColumn::make('meta_status')
                    ->label('Meta')
                    ->state(fn (TrackingLog $record) => self::platformStatus($record, 'meta'))
                    ->badge()
                    ->color($statusColor),
                Tables\Columns\TextColumn::make('ga4_status')
                    ->label('GA4')
                    ->state(fn (TrackingLog $record) => self::platformStatus($record, 'ga4'))
                    ->badge()
                    ->color($statusColor),
                Tables\Columns\TextColumn::make('error')
                    ->label('Error')
                    ->state(fn (TrackingLog $record) => self::firstError($record))
                    ->limit(60)
                    ->tooltip(fn (TrackingLog $record) => self::firstError($record))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('page_path')
                    ->label('Page')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_name')
                    ->label('Event')
                    ->options([
                        'ViewContent'      => 'ViewContent',
                        'AddToCart'        => 'AddToCart',
                        'InitiateCheckout' => 'InitiateCheckout',
                        'AddPaymentInfo'   => 'AddPaymentInfo',
                        'Purchase'         => 'Purchase',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['browser' => 'Browser', 'server' => 'Server']),
                Tables\Filters\TernaryFilter::make('has_error')
                    ->label('Failed only')
                    ->trueLabel('Failed')
                    ->falseLabel('Successful'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackingLogs::route('/'),
        ];
    }
}
