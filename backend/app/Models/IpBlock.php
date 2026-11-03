<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpBlock extends Model
{
    protected $fillable = [
        'ip_address', 'user_id', 'reason', 'type', 'is_active',
        'expires_at', 'block_count', 'blocked_by', 'country', 'city',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function blockedBy()
    {
        return $this->belongsTo(AdminUser::class, 'blocked_by');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public static function isBlocked(string $ip): bool
    {
        return static::active()->where('ip_address', $ip)->exists();
    }

    public function unblock(): void
    {
        $this->update(['is_active' => false]);
    }
}
