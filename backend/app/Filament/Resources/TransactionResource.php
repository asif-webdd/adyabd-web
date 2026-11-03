<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Transaction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Filters\SelectFilter, Filters\TernaryFilter, Actions\Action};
use Filament\Resources\Resource;

class TransactionResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'finance';
    protected static ?string $model = \App\Models\Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'report';
    protected static ?string $navigationLabel = 'Transaction';
    protected static ?int $navigationSort = 1;

    /**
     * Wallet withdrawal requests are stored as ordinary debit transactions
     * with payment_method set (see UserController::walletWithdraw) — there
     * is no separate withdrawals table. This badge count surfaces pending
     * ones in the sidebar so they don't get missed.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::pendingWithdrawalsQuery()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static function pendingWithdrawalsQuery()
    {
        return Transaction::where('type', 'debit')
            ->where('status', 'pending')
            ->whereNotNull('payment_method');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('transactions', ['ID' => 'id', 'Date' => 'created_at', 'Type' => 'type', 'Amount' => 'amount', 'Balance before' => 'balance_before', 'Balance after' => 'balance_after', 'Status' => 'status', 'Method' => 'payment_method', 'Reference' => 'transaction_id', 'Description' => 'description', 'Customer' => 'user.name', 'Seller' => 'seller.shop_name'], ['user','seller'])])
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.phone')->label('Phone')->searchable()->toggleable(),
                BadgeColumn::make('type')
                    ->label('Type')
                    ->color(fn($state) => in_array($state, ['credit', 'bonus', 'refund']) ? 'success' : 'danger'),
                BadgeColumn::make('payment_method')
                    ->label('Withdraw Via')
                    ->placeholder('—')
                    ->color('gray'),
                TextColumn::make('amount')->money('BDT')->sortable(),
                TextColumn::make('description')->limit(50)->wrap(),
                BadgeColumn::make('status')
                    ->color(fn($state) => $state === 'completed' ? 'success' : ($state === 'pending' ? 'warning' : 'danger')),
                TextColumn::make('transaction_id')->label('Reference')->toggleable()->copyable(),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')->options([
                    'credit'     => 'Credit',
                    'debit'      => 'Debit',
                    'refund'     => 'Refund',
                    'commission' => 'Commission',
                    'withdrawal' => 'Withdrawal',
                    'bonus'      => 'Bonus',
                ]),
                SelectFilter::make('status')->options([
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'failed'    => 'Failed',
                ]),
                SelectFilter::make('payment_method')->label('Withdraw Method')->options([
                    'bkash'  => 'bKash',
                    'nagad'  => 'Nagad',
                    'rocket' => 'Rocket',
                    'bank'   => 'Bank',
                ]),
                TernaryFilter::make('is_withdrawal')
                    ->label('Withdrawal Requests Only')
                    ->queries(
                        true: fn($query) => $query->where('type', 'debit')->whereNotNull('payment_method'),
                        false: fn($query) => $query,
                        blank: fn($query) => $query,
                    ),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Transaction $record) => $record->type === 'debit' && $record->status === 'pending' && $record->payment_method)
                    ->requiresConfirmation()
                    ->modalDescription('Mark this withdrawal as paid out? The wallet balance was already deducted when the request was made.')
                    ->action(function (Transaction $record) {
                        $record->update(['status' => 'completed']);
                        Notification::make()->success()->title('Withdrawal approved')->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Transaction $record) => $record->type === 'debit' && $record->status === 'pending' && $record->payment_method)
                    ->requiresConfirmation()
                    ->modalDescription('Reject this withdrawal and refund the amount back to the user\'s wallet?')
                    ->action(function (Transaction $record) {
                        // Atomic + re-checked so a double click can never refund twice.
                        $done = \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $tx = Transaction::whereKey($record->id)->lockForUpdate()->first();
                            if ($tx->status !== 'pending') return false;
                            $user   = \App\Models\User::whereKey($tx->user_id)->lockForUpdate()->first();
                            $before = (float) ($user?->wallet_balance ?? 0);
                            $user?->increment('wallet_balance', $tx->amount);
                            $tx->update([
                                'status'         => 'failed',
                                'description'    => $tx->description . ' (Rejected — refunded)',
                                'balance_before' => $before,
                                'balance_after'  => $before + (float) $tx->amount,
                            ]);
                            return true;
                        });
                        Notification::make()->{$done ? 'success' : 'warning'}()
                            ->title($done ? 'Withdrawal rejected and refunded' : 'Already processed')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
