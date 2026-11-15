<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes, Relations\HasMany};

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'description', 'type', 'value', 'min_order_amount',
        'max_discount_amount', 'usage_limit', 'usage_limit_per_user', 'used_count',
        'seller_id', 'is_active', 'is_for_new_users', 'applicable_to', 'applicable_ids', 'starts_at', 'expires_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_for_new_users' => 'boolean',
        'starts_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'value'            => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'applicable_ids'   => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isValid(): bool
    {
        return $this->is_active && (!$this->starts_at || $this->starts_at->isPast()) && (!$this->expires_at || $this->expires_at->isFuture()) && (!$this->usage_limit || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValid() || $amount < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'free_ship') {
            return 0; // free shipping is applied on the delivery charge, not as a money discount
        }

        $d = $this->type === 'percentage' ? ($amount * $this->value / 100) : $this->value;

        return $this->max_discount_amount ? min($d, $this->max_discount_amount) : $d;
    }

    /** Does this coupon cover one order line? (seller restriction + selected products / categories) */
    public function appliesToItem($productId, $categoryId, $sellerId): bool
    {
        if ($this->seller_id && (int) $this->seller_id !== (int) $sellerId) {
            return false;
        }
        $ids = array_map('intval', (array) ($this->applicable_ids ?? []));

        return match ($this->applicable_to ?? 'all') {
            'products'   => in_array((int) $productId, $ids, true),
            'categories' => in_array((int) $categoryId, $ids, true),
            default      => true,
        };
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
