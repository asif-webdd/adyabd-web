<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string  $title,
        public string  $body,
        public string  $icon  = 'bell',
        public string  $color = 'primary',
        public ?string $url   = null,
        public array   $extra = [],
    ) {}

    public function via($notifiable): array { return ['database']; }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'body'  => $this->body,
            'icon'  => $this->icon,
            'color' => $this->color,
            'url'   => $this->url,
        ], $this->extra);
    }

    public function toArray($notifiable): array { return $this->toDatabase($notifiable); }
}
