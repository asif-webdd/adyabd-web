<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes};

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'name_bn', 'slug', 'description', 'image', 'icon',
        'color', 'sort_order', 'is_active', 'show_in_homepage', 'show_in_menu',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'show_in_homepage'=> 'boolean',
        'show_in_menu'    => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRoot($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image ? storage_url($this->image) : asset('images/category-placeholder.png');
    }
}
