<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerWithdrawalResource\Pages\ManageSellerWithdrawals;
use App\Models\SellerWithdrawal;
use Filament\Forms\Components\{Textarea, TextInput};
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\{BadgeColumn, TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Payout requests raised by sellers from /seller/dashboard. The amount is already held (deducted) when requested. */
class SellerWithdrawalResource extends Resource
{
    use \App\Filament\Concerns\ChecksAdminArea;
    protected static string $area = 'sellers';
    protected static ?string $model = SellerWithdrawal::class;
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Seller Withdrawals';
    protected static ?int    $navigationSort  = 20;

    public static function getNavigationBadge(): ?string
    {
        $n = SellerWithdrawal::where('status', 'pending')->count();
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
            ->headerActions([\App\Filament\Support\CsvExport::make('seller-withdrawals', ['ID' => 'id', 'Requested' => 'created_at', 'Seller' => 'seller.shop_name', 'Amount' => 'amount', 'Method' => 'method', 'Account' => 'account', 'Status' => 'status', 'Reference' => 'reference', 'Note' => 'admin_note', 'Processed at' => 'processed_at'], ['seller'])])
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('seller.shop_name')->label('Seller')->searchable(),
                TextColumn::make('amount')->money('BDT')->sortable(),
                BadgeColumn::make('method'),
                TextColumn::make('account')->label('Send to')->copyable(),
                BadgeColumn::make('status')->colors(['warning' => 'pending', 'success' => 'paid', 'danger' => 'rejected']),
                TextColumn::make('reference')->toggleable(),
                TextColumn::make('admin_note')->limit(30)->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'paid' => 'Paid', 'rejected' => 'Rejected']),
            ])
            ->actions([
                Action::make('paid')->label('Mark paid')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn(SellerWithdrawal $w) => $w->status === 'pending')
                    ->modalDescription(fn(SellerWithdrawal $w) => "Send BDT " . number_format((float) $w->amount, 2) . " to {$w->account} ({$w->method}) first, then record the reference here.")
                    ->form([TextInput::make('reference')->label('bKash TrxID / bank reference')->required()->maxLength(100)])
                    ->action(function (SellerWithdrawal $w, array $data) {
                        $w->markPaid($data['reference'], auth()->id())
                            ? Notification::make()->success()->title('Marked as paid – seller notified')->send()
                            : Notification::make()->warning()->title('Already processed')->send();
                    }),

                Action::make('reject')->label('Reject')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn(SellerWithdrawal $w) => $w->status === 'pending')
                    ->form([Textarea::make('admin_note')->label('Reason (shown to the seller)')->required()])
                    ->action(function (SellerWithdrawal $w, array $data) {
                        $w->reject($data['admin_note'], auth()->id())
                            ? Notification::make()->success()->title('Rejected – amount returned to the seller')->send()
                            : Notification::make()->warning()->title('Already processed')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSellerWithdrawals::route('/')];
    }
}
