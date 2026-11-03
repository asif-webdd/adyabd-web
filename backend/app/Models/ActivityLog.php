<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'log_name', 'description', 'event', 'action',
        'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'causer_name',
        'ip_address', 'user_agent',
        'properties', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function subject(): MorphTo { return $this->morphTo(); }
    public function causer(): MorphTo  { return $this->morphTo(); }

    public function getSubjectLabelAttribute(): string
    {
        return class_basename($this->subject_type ?? '') . ' #' . $this->subject_id;
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created'  => 'success',
            'updated'  => 'info',
            'deleted'  => 'danger',
            'restored' => 'warning',
            'login'    => 'success',
            'logout'   => 'gray',
            default    => 'gray',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'created'  => 'heroicon-o-plus-circle',
            'updated'  => 'heroicon-o-pencil-square',
            'deleted'  => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-uturn-left',
            'login'    => 'heroicon-o-arrow-right-on-rectangle',
            'logout'   => 'heroicon-o-arrow-left-on-rectangle',
            default    => 'heroicon-o-ellipsis-horizontal-circle',
        };
    }
}
