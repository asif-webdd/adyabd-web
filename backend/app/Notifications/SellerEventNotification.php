<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** In-app notification for a seller (shown on /seller/dashboard and in the account notifications). */
class SellerEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,      // new_order | order_cancelled | withdrawal_paid | withdrawal_rejected | payout_reversed
        public string $title,
        public string $body,
        public array $extra = [],
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'type'    => 'seller_' . $this->kind,
            'kind'    => $this->kind,
            'title'   => $this->title,
            'body'    => $this->body,
            'message' => $this->body,
            'icon'    => 'storefront',
        ], $this->extra);
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
