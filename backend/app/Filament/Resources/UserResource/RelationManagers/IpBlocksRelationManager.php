<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\{Table, Columns\TextColumn, Columns\IconColumn, Actions\Action};
use Filament\Notifications\Notification;

class IpBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'ipBlocks';
    protected static ?string $title       = ' IP Blocks';
    protected static bool $isLazy         = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->copyMessage('IP copied!')
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('type')
                    ->label('Block Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'manual'               => ' Manual',
                        'auto_cancel_order'    => ' Auto — Cancellations',
                        'auto_failed_delivery' => ' Auto — Failed Delivery',
                        'auto_no_answer'       => ' Auto — No Answer',
                        default                => ucfirst(str_replace('_', ' ', $state ?? '—')),
                    })
                    ->color(fn ($state) => str_starts_with($state ?? '', 'auto') ? 'warning' : 'danger'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reason),

                TextColumn::make('country')
                    ->label('Country')
                    ->default('—'),

                TextColumn::make('city')
                    ->label('City')
                    ->default('—'),

                TextColumn::make('block_count')
                    ->label('Block Count')
                    ->badge()
                    ->color(fn ($state) => $state > 2 ? 'danger' : 'gray'),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->default('Permanent'),

                TextColumn::make('blockedBy.name')
                    ->label('Blocked By')
                    ->default('Auto-system'),

                TextColumn::make('created_at')
                    ->label('Blocked At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->actions([
                Action::make('lift')
                    ->label('Lift Block')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Lift this IP block?')
                    ->action(function ($record) {
                        $record->update(['is_active' => false]);
                        Notification::make()->success()->title('IP block lifted for ' . $record->ip_address)->send();
                    })
                    ->visible(fn ($record) => $record->is_active),

                Action::make('reactivate')
                    ->label('Re-activate')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                        Notification::make()->success()->title('IP block reactivated')->send();
                    })
                    ->visible(fn ($record) => ! $record->is_active),
            ])
            ->headerActions([])
            ->paginated([10, 25]);
    }
}
