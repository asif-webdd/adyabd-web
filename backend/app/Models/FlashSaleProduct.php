<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id', 'product_id', 'discount_percentage',
        'special_price', 'quantity_limit', 'sold_count',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }


    public function activeFlashSale(): HasOneThrough
    {
        return $this->hasOneThrough(
            FlashSale::class,
            static::class,
            'product_id',
            'id',
            'id',
            'flash_sale_id'
        );
    }


    /** The running flash-sale row for a product (active flag + start/end window), or null. */
    public static function activeFor(int $productId, bool $lock = false): ?self
    {
        $q = static::where('product_id', $productId)->whereHas('flashSale', fn($q) => $q
            ->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now()));
        if ($lock) {
            $q->lockForUpdate();
        }
        return $q->first();
    }

    /** Units still available at the sale price (null = unlimited). */
    public function remaining(): ?int
    {
        return $this->quantity_limit ? max(0, (int) $this->quantity_limit - (int) $this->sold_count) : null;
    }

    /** Price per unit while the sale runs. Variants get the same percentage off their own price. */
    public function priceFor(?ProductVariant $variant, float $regularPrice): float
    {
        if ($variant) {
            return round($regularPrice * (1 - (float) $this->discount_percentage / 100), 2);
        }
        return (float) $this->special_price;
    }

    /** Current unit price for display/cart purposes (no stock-limit exception thrown). */
    public static function currentPrice(Product $product, ?ProductVariant $variant = null): float
    {
        $regular = (float) ($variant?->price ?? $product->price);
        $fs = static::activeFor($product->id);
        return ($fs && ($fs->remaining() === null || $fs->remaining() > 0)) ? $fs->priceFor($variant, $regular) : $regular;
    }
}
