<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $casts = ['replied_at' => 'datetime'];

    protected $fillable = [
        'name', 'phone', 'email', 'subject', 'order_id', 'message',
        'ip_address', 'status', 'admin_note', 'reply', 'reply_channel', 'replied_at', 'replied_by',
    ];
}
