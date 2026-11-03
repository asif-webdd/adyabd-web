<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\SmsService;
use App\Services\BlockingService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    // ── Page title & subheading ─────────────────────────────────────────

    public function getTitle(): string
    {
        return 'Edit Order — ' . ($this->record->order_number ?? '');
    }

    public function getSubheading(): ?string
    {
        $status  = ucwords(str_replace('_', ' ', $this->record->status));
        $total   = 'BDT  ' . number_format($this->record->total_amount, 0);
        $created = $this->record->created_at->diffForHumans();

        return "Created {$created} · {$status} · {$total}";
    }

    // ── Header action buttons ───────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_invoice')
                ->label('Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('orders.invoice', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('view_order')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => OrderResource::getUrl('view', ['record' => $this->record])),

            Actions\Action::make('book_courier')
                ->label('Send Courier')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send Courier for this Order')
                ->form([
                    Select::make('courier_merchant_id')
                        ->label('Select Courier')
                        ->options(\App\Models\CourierMerchant::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => \App\Models\CourierMerchant::where('is_default', true)->value('id'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $result = $this->record->bookCourier($data['courier_merchant_id']);
                    if ($result['success']) {
                        Notification::make()->success()->title("Courier booked! Tracking: {$result['tracking_id']}")->send();
                        app(SmsService::class)->sendCourierBooked($this->record->fresh());
                    } else {
                        Notification::make()->danger()->title('Courier failed: ' . ($result['message'] ?? 'Unknown error'))->send();
                    }
                })
                ->visible(fn () => $this->record->canBookCourier()),

            Actions\Action::make('send_sms')
                ->label('Send SMS')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('info')
                ->form([
                    Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(4)
                        ->placeholder('Type your SMS message here...'),
                ])
                ->action(function (array $data) {
                    app(SmsService::class)->send($this->record->shipping_phone, $data['message'], $this->record);
                    Notification::make()->success()->title('SMS sent successfully!')->send();
                }),

            Actions\Action::make('block_user')
                ->label('Block Customer')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Block this Customer')
                ->modalDescription('This will prevent the customer from placing new orders.')
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for blocking')
                        ->required()
                        ->rows(3),
                    Select::make('block_duration')
                        ->label('Block duration')
                        ->options([
                            '7 days'    => '7 Days',
                            '30 days'   => '30 Days',
                            '90 days'   => '90 Days',
                            'permanent' => 'Permanent',
                        ])
                        ->default('permanent')
                        ->native(false),
                    Toggle::make('block_ip')
                        ->label('Also block IP address')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    app(BlockingService::class)->blockUser(
                        $this->record->user,
                        $data['reason'],
                        auth()->id(),
                        $data['block_ip'] ?? true,
                        $data['block_duration']
                    );
                    Notification::make()->success()->title('Customer has been blocked!')->send();
                })
                ->visible(fn () => $this->record->user && ! $this->record->user->is_blocked),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-currency-dollar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Process Refund')
                ->modalDescription('Are you sure you want to refund this order? This action cannot be undone.')
                ->action(function () {
                    $this->record->processRefund();
                    $this->record->updateStatus('refunded', 'Admin refund', 'admin', auth()->id());
                    Notification::make()->success()->title('Refund completed successfully!')->send();
                    $this->refreshFormData(['status', 'payment_status']);
                })
                ->visible(fn () =>
                    $this->record->payment_status === 'paid' &&
                    in_array($this->record->status, ['cancelled', 'returned'])
                ),

            Actions\DeleteAction::make()
                ->label('Delete')
                ->successRedirectUrl(OrderResource::getUrl('index')),
        ];
    }

    // ── Form behaviour ──────────────────────────────────────────────────

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Strip any read-only fields that may accidentally come through
        unset(
            $data['shipping_name'],
            $data['shipping_phone'],
            $data['shipping_address'],
            $data['shipping_district'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        // Record the status change in history if status was updated
        $original = $this->record->getOriginal('status');
        $new      = $this->record->status;

        if ($original !== $new) {
            $this->record->updateStatus(
                $new,
                $this->data['admin_note'] ?? '',
                'admin',
                auth()->id()
            );
        }
    }

    // ── Notifications & redirect ────────────────────────────────────────

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Order updated successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
