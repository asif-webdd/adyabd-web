<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes, Relations\HasMany};

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'description', 'type', 'value', 'min_order_amount',
        'max_discount_amount', 'usage_limit', 'usage_limit_per_user', 'used_count',
        'seller_id', 'is_active', 'is_for_new_users', 'starts_at', 'expires_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_for_new_users' => 'boolean',
        'starts_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'value'            => 'decimal:2',
        'min_order_amount' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isValid(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at->isFuture()) && (!$this->usage_limit || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValid() || $amount < $this->min_order_amount) {
            return 0;
        }

        $d = $this->type === 'percentage' ? ($amount * $this->value / 100) : $this->value;

        return $this->max_discount_amount ? min($d, $this->max_discount_amount) : $d;
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }
}
