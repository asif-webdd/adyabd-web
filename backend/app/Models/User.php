<?php

namespace App\Models;

use App\Notifications\AccountBlockedNotification;
use Exception;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\{Relations\HasMany, Relations\HasOne, SoftDeletes, Factories\HasFactory};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'avatar', 'gender', 'date_of_birth',
        'status', 'is_blocked', 'blocked_reason', 'blocked_at', 'blocked_by', 'block_type',
        'cancel_order_count', 'failed_delivery_count', 'no_answer_count', 'total_orders',
        'completed_orders', 'referral_code', 'referred_by', 'wallet_balance', 'total_spent',
        'fcm_token', 'last_login_ip', 'last_login_at', 'device_type', 'preferred_language',
        'email_verified_at', 'google_id', 'facebook_id'
    ];

    protected $hidden  = ['password', 'remember_token'];
    protected $appends = ['avatar_url'];
    protected $casts   = [
        'email_verified_at' => 'datetime',
        'blocked_at'        => 'datetime',
        'last_login_at'     => 'datetime',
        'date_of_birth'     => 'date',
        'is_blocked'        => 'boolean',
        'wallet_balance'    => 'decimal:2',
        'password'          => 'hashed',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function ipBlocks(): HasMany
    {
        return $this->hasMany(IpBlock::class);
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active')->where('is_blocked', false);
    }

    public function scopeBlocked($q)
    {
        return $q->where('is_blocked', true);
    }

    public function unblock(): void
    {
        $this->update([
            'is_blocked'     => false,
            'blocked_reason' => null,
            'blocked_at'     => null,
            'blocked_by'     => null,
            'block_type'     => null,
            'status'         => 'active',
        ]);

        IpBlock::where('user_id', $this->id)->update(['is_active' => false]);
    }

    public function incrementCancelCount(): void
    {
        $this->increment('cancel_order_count');
        $this->checkAutoBlock('cancel_order');
    }

    private function checkAutoBlock(string $trigger): void
    {
        $rule = AutoBlockRule::where('trigger', $trigger)->where('is_active', true)->first();

        if (!setting('auto_block_enabled', true) || !$rule || $this->is_blocked) {
            return;
        }

        $count = match ($trigger) {
            'cancel_order'    => $this->cancel_order_count,
            'failed_delivery' => $this->failed_delivery_count,
            'no_answer'       => $this->no_answer_count,
            default           => 0,
        };

        if ($count >= $rule->threshold) {
            $this->block(str_replace('{count}', $count, $rule->reason_template), 'auto_' . $trigger, null, $rule->block_ip_also && setting('auto_block_ip_enabled', true));
        }
    }

    public function block(string $reason, string $type = 'manual', ?int $adminId = null, bool $blockIp = true): void
    {
        $this->update([
            'is_blocked'     => true,
            'blocked_reason' => $reason,
            'block_type'     => $type,
            'blocked_at'     => now(),
            'blocked_by'     => $adminId,
            'status'         => 'banned',
        ]);

        if ($blockIp && $this->last_login_ip) {
            IpBlock::firstOrCreate(
                ['ip_address' => $this->last_login_ip],
                ['user_id' => $this->id, 'reason' => $reason, 'type' => $type, 'blocked_by' => $adminId]
            );
        }

        try {
            $this->notify(new AccountBlockedNotification($reason));
        } catch (Exception $e) {
        }
    }

    public function incrementFailedDelivery(): void
    {
        $this->increment('failed_delivery_count');
        $this->checkAutoBlock('failed_delivery');
    }

    public function incrementNoAnswer(): void
    {
        $this->increment('no_answer_count');
        $this->checkAutoBlock('no_answer');
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ? (str_starts_with($this->avatar, 'http') ? $this->avatar : storage_url($this->avatar)) : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=006A4E&color=fff';
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
