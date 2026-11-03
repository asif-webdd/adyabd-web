<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Banner extends Model
{
    protected $fillable = [
        'title', 'title_bn', 'subtitle', 'image', 'mobile_image', 'link',
        'position', 'type', 'slider_duration', 'sort_order',
        'is_active', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'slider_duration' => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeBanners(Builder $query): Builder
    {
        return $query->where('type', 'banner');
    }

    public function scopeSliders(Builder $query): Builder
    {
        return $query->where('type', 'slider');
    }

    public function scopeForPosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function isBanner(): bool
    {
        return $this->type === 'banner';
    }

    public function isSlider(): bool
    {
        return $this->type === 'slider';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'slider' => ' Slider',
            default  => ' Banner',
        };
    }
}
