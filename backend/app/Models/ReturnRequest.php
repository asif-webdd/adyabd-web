<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'order_item_id', 'type', 'reason', 'description',
        'refund_method', 'status', 'admin_note', 'processed_at',
    ];

    protected $casts = ['processed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** In-app (+ email when the customer has one) notice about a status change. Never throws. */
    public function notifyCustomer(string $title, string $body): void
    {
        try {
            $user = $this->user;
            if (!$user) return;
            $user->notify(new \App\Notifications\CustomerEventNotification('return_' . $this->status, $title, $body, ['order_id' => $this->order_id]));
            if ($user->email && (bool) setting('email_on_order_status', true)) {
                $user->notify(new \App\Notifications\CustomerMailNotification($title, [$body]));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
