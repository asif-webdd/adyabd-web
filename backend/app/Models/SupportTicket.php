<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number', 'user_id', 'order_id', 'subject', 'category',
        'priority', 'status', 'assigned_to',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    /** An admin answers the customer: stored in the conversation, customer notified in-app + by SMS. */
    public function addAdminReply(string $text, ?int $adminId): SupportMessage
    {
        $msg = $this->messages()->create(['sender_id' => $adminId, 'sender_type' => 'admin', 'message' => $text]);
        if ($this->status === 'open') {
            $this->update(['status' => 'in_progress']);
        }

        if ($user = $this->user) {
            try {
                $user->notify(new \App\Notifications\CustomerEventNotification(
                    'support_reply', 'আপনার সাপোর্ট টিকেটে উত্তর এসেছে', \Illuminate\Support\Str::limit($text, 140),
                    ['action_url' => '/support', 'action_label' => 'দেখুন', 'category' => 'support']
                ));
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                if ($user->email && setting('email_on_support_reply', true)) {
                    $user->notify(new \App\Notifications\CustomerMailNotification(
                        "সাপোর্ট টিকেট #{$this->ticket_number}",
                        ['আপনার সাপোর্ট টিকেটে আমাদের টিম উত্তর দিয়েছে:', '"' . \Illuminate\Support\Str::limit($text, 400) . '"'],
                        'টিকেট দেখুন', rtrim((string) (setting('shop_url') ?: config('app.frontend_url') ?: config('app.url')), '/') . '/support'
                    ));
                }
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                app(\App\Services\SmsService::class)->sendTemplate(
                    'support_reply', $user->phone, ['ticket' => $this->ticket_number],
                    $this, '{shop_name}: আপনার সাপোর্ট টিকেট #{ticket} এ উত্তর দেওয়া হয়েছে। দেখুন: {site_url}/support', 'sms_on_support_reply'
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return $msg;
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($t) => $t->ticket_number = 'TKT-' . strtoupper(uniqid()));
    }
}
