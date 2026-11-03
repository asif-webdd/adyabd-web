<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages\ManageReturnRequests;
use App\Models\{ReturnRequest, Transaction, User};
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\{BadgeColumn, TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/** Return / refund requests created from /order/return on the storefront. */
class ReturnRequestResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'orders';
    protected static ?string $model = ReturnRequest::class;
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?string $navigationLabel = 'Return Requests';
    protected static ?int    $navigationSort  = 30;

    public static function getNavigationBadge(): ?string
    {
        $n = ReturnRequest::where('status', 'pending')->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([\App\Filament\Support\CsvExport::make('return-requests', ['ID' => 'id', 'Date' => 'created_at', 'Order' => 'order.order_number', 'Customer' => 'user.name', 'Type' => 'type', 'Reason' => 'reason', 'Refund method' => 'refund_method', 'Status' => 'status', 'Note' => 'admin_note'], ['order','user'])])
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('order.order_number')->label('Order')->searchable(),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('order.total_amount')->label('Order total')->money('BDT'),
                BadgeColumn::make('type'),
                TextColumn::make('reason')->limit(30)->tooltip(fn($record) => $record->description),
                BadgeColumn::make('refund_method')->label('Refund to'),
                BadgeColumn::make('status')->colors([
                    'warning' => 'pending', 'info' => 'approved', 'danger' => 'rejected', 'success' => 'refunded',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'refunded' => 'Refunded',
                ]),
            ])
            ->actions([
                Action::make('approve')->icon('heroicon-o-check-circle')->color('info')
                    ->visible(fn(ReturnRequest $r) => $r->status === 'pending')
                    ->form([Textarea::make('admin_note')->label('Note to customer (optional)')])
                    ->action(function (ReturnRequest $r, array $data) {
                        $r->update(['status' => 'approved', 'admin_note' => $data['admin_note'] ?? null, 'processed_at' => now()]);
                        $r->notifyCustomer('আপনার রিটার্ন অনুরোধ অনুমোদিত হয়েছে', "অর্ডার #{$r->order?->order_number} এর রিটার্ন অনুমোদিত হয়েছে।" . ($data['admin_note'] ? " নোট: {$data['admin_note']}" : ''));
                        Notification::make()->success()->title('Return approved')->send();
                    }),

                Action::make('reject')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn(ReturnRequest $r) => $r->status === 'pending')
                    ->form([Textarea::make('admin_note')->label('Reason shown to customer')->required()])
                    ->action(function (ReturnRequest $r, array $data) {
                        $r->update(['status' => 'rejected', 'admin_note' => $data['admin_note'], 'processed_at' => now()]);
                        $r->notifyCustomer('আপনার রিটার্ন অনুরোধ বাতিল হয়েছে', "অর্ডার #{$r->order?->order_number} এর রিটার্ন গ্রহণ করা যায়নি। কারণ: {$data['admin_note']}");
                        Notification::make()->success()->title('Return rejected')->send();
                    }),

                Action::make('refund')->icon('heroicon-o-banknotes')->color('success')
                    ->visible(fn(ReturnRequest $r) => $r->status === 'approved')
                    ->requiresConfirmation()
                    ->modalDescription(fn(ReturnRequest $r) => $r->refund_method === 'wallet'
                        ? 'Credit the order total to the customer\'s wallet now?'
                        : 'Mark as refunded AFTER you have paid the customer via ' . strtoupper($r->refund_method) . ' manually.')
                    ->action(function (ReturnRequest $r) {
                        DB::transaction(function () use ($r) {
                            $r = ReturnRequest::whereKey($r->id)->lockForUpdate()->first();
                            if ($r->status !== 'approved') return; // double-click / parallel admin

                            $order = $r->order;
                            if ($r->refund_method === 'wallet') {
                                $user   = User::whereKey($r->user_id)->lockForUpdate()->first();
                                $before = (float) $user->wallet_balance;
                                $user->increment('wallet_balance', $order->total_amount);
                                Transaction::create([
                                    'user_id'        => $user->id,
                                    'reference_id'   => $r->id,
                                    'reference_type' => 'return_refund',
                                    'type'           => 'refund',
                                    'amount'         => $order->total_amount,
                                    'balance_before' => $before,
                                    'balance_after'  => $before + (float) $order->total_amount,
                                    'description'    => "Refund for order #{$order->order_number}",
                                    'status'         => 'completed',
                                ]);
                            }
                            $order->update(['payment_status' => 'refunded']);
                            $order->clawbackSeller('Return refunded'); // seller gives back the payout of this order
                            $r->update(['status' => 'refunded', 'processed_at' => now()]);
                        });
                        $r->refresh()->notifyCustomer('রিফান্ড সম্পন্ন হয়েছে', "অর্ডার #{$r->order?->order_number} এর রিফান্ড " . ($r->refund_method === 'wallet' ? 'আপনার ওয়ালেটে জমা হয়েছে।' : 'প্রসেস করা হয়েছে (' . strtoupper((string) $r->refund_method) . ')।'));
                        Notification::make()->success()->title('Refund recorded')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReturnRequests::route('/')];
    }
}
