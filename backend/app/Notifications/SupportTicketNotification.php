<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int    $ticketId,
        public string $subject,
        public string $customerName,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'     => 'New Support Ticket',
            'body'      => "{$this->customerName}: {$this->subject}",
            'icon'      => 'chat-bubble-left-ellipsis',
            'color'     => 'warning',
            'url'       => '/admin/support-tickets/' . $this->ticketId,
            'ticket_id' => $this->ticketId,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
