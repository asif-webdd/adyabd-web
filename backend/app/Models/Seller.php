<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model,
    Relations\HasMany,
    Relations\HasManyThrough,
    SoftDeletes,
    Factories\HasFactory
};
use App\Models\Transaction;

class Seller extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'shop_name', 'shop_name_bn', 'slug', 'description', 'logo', 'banner',
        'phone', 'email', 'address', 'nid_number', 'trade_license', 'bkash_number',
        'bank_account', 'bank_name', 'commission_rate', 'balance', 'status',
        'is_verified', 'is_featured', 'application',
    ];


    /**
     * The commission % that really applies to this seller: their own rate, or the global
     * "platform_commission" setting (Admin → Settings) when their rate is blank (NULL).
     * Use getRawOriginal('commission_rate') if you need the stored value only.
     */
    public function getCommissionRateAttribute($value): float
    {
        return ($value !== null && $value !== '') ? (float) $value : (float) setting('platform_commission', 10);
    }


    protected $casts = [
        'is_verified'     => 'boolean',
        'is_featured'     => 'boolean',
        'commission_rate' => 'decimal:2',
        'balance'         => 'decimal:2',
        'rating'          => 'decimal:2',
        'application'     => 'array',
    ];
    private array $removedFields = ['rating', 'total_reviews', 'total_sales'];

    public function followers()
    {
        return $this->belongsToMany(User::class, 'seller_followers')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Recomputes the cached rating / review count / units sold from this seller's products. */
    public function refreshStats(): void
    {
        $db  = \Illuminate\Support\Facades\DB::class;
        $rev = $db::table('reviews')->join('products', 'products.id', '=', 'reviews.product_id')
            ->where('products.seller_id', $this->id)->where('reviews.is_approved', true)->whereNull('reviews.deleted_at')
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(reviews.rating),0) as a')->first();
        $sold = (int) $db::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $this->id)->where('orders.status', 'delivered')->sum('order_items.quantity');

        static::withTrashed()->whereKey($this->id)->toBase()->update([
            'rating' => round((float) $rev->a, 2), 'total_reviews' => (int) $rev->c, 'total_sales' => $sold,
        ]);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeVerified($q)
    {
        return $q->where('is_verified', true);
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('images/shop-default.png');
    }

    /**
     * Tell the seller about something: in-app notification (always) + SMS through an
     * admin-editable template (respecting its Settings → SMS switch). Never throws.
     */
    public function notifyEvent(string $kind, string $title, string $body, array $extra = [], ?string $smsTemplate = null, array $smsVars = [], ?string $smsToggle = null, ?string $smsFallback = null): void
    {
        try {
            $this->user?->notify(new \App\Notifications\SellerEventNotification($kind, $title, $body, $extra));
        } catch (\Throwable $e) {
            \Log::warning('Seller in-app notification failed: ' . $e->getMessage());
        }

        if ($smsTemplate && $this->phone) {
            try {
                app(\App\Services\SmsService::class)->sendTemplate(
                    $smsTemplate, $this->phone, $smsVars + ['seller_shop' => $this->shop_name], $this, $smsFallback, $smsToggle
                );
            } catch (\Throwable $e) {
                \Log::warning('Seller SMS failed: ' . $e->getMessage());
            }
        }
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(SellerWithdrawal::class);
    }
}
