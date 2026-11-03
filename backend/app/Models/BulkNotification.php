<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkNotification extends Model
{
    protected $fillable = [
        'title', 'message', 'channel', 'audience', 'recipient_ids', 'filters',
        'total_recipients', 'sent_count', 'failed_count', 'status', 'created_by',
        'scheduled_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'filters'       => 'array',
        'scheduled_at'  => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];
}
