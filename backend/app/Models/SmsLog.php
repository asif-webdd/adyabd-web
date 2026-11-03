<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_logs';

    protected $fillable = [
        'recipient_phone', 'user_id', 'message', 'gateway', 'status',
        'gateway_message_id', 'gateway_response', 'reference_type', 'reference_id', 'cost',
    ];

    protected $casts = ['gateway_response' => 'array'];
}
