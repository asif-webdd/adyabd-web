<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'user_id', 'variant_id', 'flash_sale_product_id', 'product_name', 'product_image',
        'variant_options', 'quantity', 'unit_price', 'compare_price',
        'discount_amount', 'total_price',
    ];

    protected $casts = [
        'variant_options' => 'array',
        'unit_price'      => 'decimal:2',
        'total_price'     => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** A return request covers the whole order (order_item_id is optional). */
    public function returnRequest(): HasOne
    {
        return $this->hasOne(ReturnRequest::class, 'order_id', 'order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
