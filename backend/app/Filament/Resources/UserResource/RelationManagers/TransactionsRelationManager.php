<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\{Table, Columns\TextColumn, Filters\SelectFilter, Actions\Action};
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, Grid};

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    protected static ?string $title       = ' Transactions';
    protected static bool $isLazy         = false;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'credit'           => ' Credit',
                        'debit'            => ' Debit',
                        'sale'             => ' Sale',
                        'withdrawal'       => ' Withdrawal',
                        'commission'       => '% Commission',
                        'refund'           => '↩ Refund',
                        'refund_received'  => '↩ Refund Received',
                        default            => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'credit', 'sale', 'refund_received' => 'success',
                        'withdrawal', 'debit', 'commission' => 'danger',
                        'refund'                            => 'warning',
                        default                             => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->color(fn ($record) =>
                        in_array($record->type, ['credit', 'sale', 'refund_received']) ? 'success' : 'danger'
                    ),

                TextColumn::make('balance_before')
                    ->label('Before')
                    ->money('BDT')
                    ->toggleable(),

                TextColumn::make('balance_after')
                    ->label('After')
                    ->money('BDT'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending'   => 'warning',
                        'failed'    => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'credit'     => 'Credit',
                        'debit'      => 'Debit',
                        'sale'       => 'Sale',
                        'withdrawal' => 'Withdrawal',
                        'commission' => 'Commission',
                        'refund'     => 'Refund',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'pending'   => 'Pending',
                        'failed'    => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('view_txn')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->modalHeading('Transaction Details')
                    ->infolist(fn (Infolist $infolist, $record) =>
                        $infolist->record($record)->schema([
                            Section::make()->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('created_at')->label('Date & Time')->dateTime('d M Y, h:i A'),
                                    TextEntry::make('type')->label('Type')->badge()
                                        ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                                        ->color(fn ($state) => match ($state) {
                                            'credit', 'sale', 'refund_received' => 'success',
                                            'withdrawal', 'debit', 'commission' => 'danger',
                                            default                             => 'gray',
                                        }),
                                    TextEntry::make('amount')->label('Amount')->money('BDT')
                                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                        ->color(fn ($record) =>
                                            in_array($record->type, ['credit', 'sale', 'refund_received']) ? 'success' : 'danger'
                                        ),
                                    TextEntry::make('status')->label('Status')->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'completed' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                                        }),
                                    TextEntry::make('balance_before')->label('Balance Before')->money('BDT'),
                                    TextEntry::make('balance_after')->label('Balance After')->money('BDT'),
                                    TextEntry::make('payment_method')->label('Payment Method')->badge()->color('info')->default('—'),
                                    TextEntry::make('transaction_id')->label('Transaction ID')->copyable()->default('—'),
                                    TextEntry::make('reference_type')->label('Ref. Type')->default('—'),
                                    TextEntry::make('reference_id')->label('Ref. ID')->default('—'),
                                ]),
                                TextEntry::make('description')->label('Description')->columnSpanFull()->default('—'),
                            ]),
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([])
            ->paginated([15, 25, 50]);
    }
}
