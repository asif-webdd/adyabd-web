<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoBlockRule extends Model
{
    protected $fillable = [
        'name', 'trigger', 'threshold', 'within_days', 'block_duration',
        'block_ip_also', 'reason_template', 'is_active',
    ];

    protected $casts = [
        'block_ip_also' => 'boolean',
        'is_active'     => 'boolean',
    ];
}
