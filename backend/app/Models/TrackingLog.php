<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class TrackingLog extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'results'    => 'array',
        'has_error'  => 'boolean',
        'value'      => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // php artisan model:prune চালালে ৩০ দিনের পুরনো লগ মুছে যাবে
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
