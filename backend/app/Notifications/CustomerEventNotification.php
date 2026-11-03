<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** In-app notification for a customer (shown on /account/notifications). */
class CustomerEventNotification extends Notification
{
    use Queueable;

    public function __construct(public string $kind, public string $title, public string $body, public array $extra = [])
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'type' => $this->kind, 'kind' => $this->kind, 'title' => $this->title,
            'message' => $this->body, 'body' => $this->body, 'icon' => 'notifications',
        ], $this->extra);
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
