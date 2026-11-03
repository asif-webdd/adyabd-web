<?php

namespace App\Filament\Widgets;

use App\Models\SmsLog;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;

class SmsLogWidget extends BaseTableWidget
{
    protected static ?string $heading = 'Recent SMS Activity';
    protected static ?int    $sort    = 8;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(SmsLog::whereDate('created_at', today())->latest()->limit(10))
            ->columns([
                TextColumn::make('recipient_phone')
                    ->label('Recipient')
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn (SmsLog $record): string => $record->message),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent', 'delivered' => 'success',
                        'failed'            => 'danger',
                        default             => 'warning',
                    }),

                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->tooltip(fn (SmsLog $record): string => $record->created_at->format('d M Y, h:i:s A')),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->emptyStateHeading('No SMS sent today')
            ->emptyStateDescription('SMS messages sent today will appear here.');
    }

    public static function canView(): bool
    {
        return SmsLog::whereDate('created_at', today())->exists();
    }
}
