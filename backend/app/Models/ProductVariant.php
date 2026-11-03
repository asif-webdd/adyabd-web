<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'price', 'compare_price', 'quantity',
        'image', 'attribute_values', 'is_active',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'is_active'        => 'boolean',
        'price'            => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
