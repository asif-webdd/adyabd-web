<?php

namespace App\Notifications;

use App\Models\SmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SmsSendFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public SmsLog $smsLog) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'  => 'SMS Send Failed',
            'body'   => "Failed to send SMS to {$this->smsLog->recipient_phone}.",
            'icon'   => 'x-circle',
            'color'  => 'danger',
            'url'    => '/admin/sms-logs',
            'sms_id' => $this->smsLog->id,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
