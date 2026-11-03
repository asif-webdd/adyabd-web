<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\BlockingService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseTableWidget;

class BlockedUsersWidget extends BaseTableWidget implements HasTable
{
    use \App\Filament\Concerns\ChecksAdminAreaWidget;
    protected static string $area = 'customers';
    use InteractsWithTable;

    protected static ?string $heading = ' Recently Blocked Customers';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::where('is_blocked', true)
                    ->latest('blocked_at')
                    ->limit(6)
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->description(fn (User $record) => $record->phone),

                TextColumn::make('blocked_reason')
                    ->label('Reason')
                    ->limit(35),

                BadgeColumn::make('block_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'manual'               => '‍ Manual',
                        'auto_cancel'          => ' Auto Cancel',
                        'auto_no_answer'       => ' No Answer',
                        'auto_failed_delivery' => ' Failed Delivery',
                        default                => $state ?? 'Unknown',
                    })
                    ->color('danger'),

                TextColumn::make('cancel_order_count')
                    ->label('Cancelled')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('no_answer_count')
                    ->label('No Answer')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('blocked_at')
                    ->label('Blocked At')
                    ->since(),
            ])

            ->actions([
                Action::make('unblock')
                    ->label(' Unblock')
                    ->color('success')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        app(BlockingService::class)->unblockUser($record, auth()->id());

                        Notification::make()
                            ->success()
                            ->title("{$record->name} has been unblocked!")
                            ->send();
                    }),
            ])

            ->paginated(false);
    }
}
