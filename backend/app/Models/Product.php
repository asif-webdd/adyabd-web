<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\{Model,
    Relations\BelongsTo,
    Relations\HasMany,
    Relations\HasOne,
    SoftDeletes,
    Factories\HasFactory
};

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id', 'category_id', 'brand_id', 'name', 'name_bn', 'slug', 'sku',
        'short_description', 'description', 'description_bn', 'price', 'compare_price',
        'cost_price', 'quantity', 'low_stock_threshold', 'weight', 'unit', 'has_variants',
        'is_active', 'is_featured', 'is_new', 'is_trending', 'free_shipping', 'total_views', 'video_url', 'tags',
    ];

    protected $hidden = ['deleted_at'];

    private array $removedFields = ['total_reviews', 'rating', 'total_sales'];

    protected $casts = [
        'has_variants'  => 'boolean',
        'is_active'     => 'boolean',
        'is_featured'   => 'boolean',
        'is_new'        => 'boolean',
        'is_trending'   => 'boolean',
        'free_shipping' => 'boolean',
        'price'         => 'decimal:2',
        'compare_price' => 'decimal:2',
        'tags'          => 'array',
    ];


    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function primaryImage(): HasOne
    {
        // primary image first; if none is flagged, fall back to the first by sort order
        return $this->hasOne(ProductImage::class)
            ->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }

    public function scopeTrending($q)
    {
        return $q->where('is_trending', true);
    }

    public function scopeInStock($q)
    {
        return $q->where('quantity', '>', 0);
    }

    public function scopeLowStock($q)
    {
        return $q->whereRaw('quantity <= low_stock_threshold AND quantity > 0');
    }

    public function getDiscountPercentageAttribute(): int
    {
        return ($this->compare_price && $this->compare_price > $this->price) ? (int)round((($this->compare_price - $this->price) / $this->compare_price) * 100) : 0;
    }

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    /** Recomputes the cached rating / review count (approved reviews) and units sold (delivered orders). */
    public function refreshStats(bool $cascadeSeller = true): void
    {
        $rev = \Illuminate\Support\Facades\DB::table('reviews')->where('product_id', $this->id)->where('is_approved', true)->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating),0) as a')->first();
        $sold = (int) \Illuminate\Support\Facades\DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $this->id)->where('orders.status', 'delivered')->sum('order_items.quantity');

        static::withTrashed()->whereKey($this->id)->toBase()->update([
            'rating' => round((float) $rev->a, 2), 'total_reviews' => (int) $rev->c, 'total_sales' => $sold,
        ]);
        if ($cascadeSeller && $this->seller_id) {
            Seller::withTrashed()->find($this->seller_id)?->refreshStats();
        }
    }

    /** Admin approval of a (seller) product: makes it live and tells the seller. */
    public function approve(): void
    {
        $this->update(['is_active' => true]);
        try {
            $this->seller?->user?->notify(new \App\Notifications\CustomerEventNotification(
                'product_approved', 'Product approved', "Your product \"{$this->name}\" is now live on the store."
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** The unit column is NOT NULL (default 'piece'); forms send an empty value when left blank. */
    public function setUnitAttribute($value): void
    {
        $this->attributes['unit'] = filled($value) ? $value : 'piece';
    }

    public function getThumbnailAttribute(): string
    {
        $img = $this->primaryImage ?? $this->images()->first();
        if (!$img) {
            return asset('images/product-placeholder.png');
        }
        if (Str::startsWith($img->image_path, 'http')) {
            return $img->image_path;
        }
        return storage_url($img->image_path);
    }


    public function flashSale(): HasOne
    {
        return $this->hasOne(FlashSaleProduct::class)->with('flashSale');
    }


    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
