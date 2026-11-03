<?php

namespace App\Observers;

use App\Models\{AdminUser, SmsLog};
use App\Notifications\SmsSendFailedNotification;

class SmsLogObserver
{
    /**
     * Notify admins when an SMS fails to send.
     */
    public function created(SmsLog $smsLog): void
    {
        if ($smsLog->status !== 'failed') {
            return;
        }

        // Only notify once per minute per phone number to avoid spam
        $recentFail = SmsLog::where('recipient_phone', $smsLog->recipient_phone)
            ->where('status', 'failed')
            ->where('id', '!=', $smsLog->id)
            ->where('created_at', '>=', now()->subMinute())
            ->exists();

        if ($recentFail) {
            return;
        }

        $admins = AdminUser::where('is_active', true)->get();

        foreach ($admins as $admin) {
            $admin->notify(new SmsSendFailedNotification($smsLog));
        }
    }
}
