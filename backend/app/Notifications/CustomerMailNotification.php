<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Plain transactional email to a customer (order updates, support replies). Texts are Bangla; shop name comes from Settings. */
class CustomerMailNotification extends Notification
{
    public function __construct(
        public string $subject,
        public array $lines,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $shop = setting('shop_name_bn') ?: (setting('shop_name') ?: config('app.name'));
        $m = (new MailMessage)->subject("[{$shop}] {$this->subject}")->greeting('নমস্কার ' . ($notifiable->name ?: '') . ',');
        foreach ($this->lines as $line) {
            $m->line($line);
        }
        if ($this->actionText && $this->actionUrl) {
            $m->action($this->actionText, $this->actionUrl);
        }
        return $m->salutation($shop);
    }
}
