<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, SoftDeletes};

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'rating',
        'title',
        'review',
        'comment',
        'images',
        'is_approved',
        'is_featured',
        //'is_verified',
        'admin_reply',
        'admin_replied_at',
    ];

    protected $hidden = ['deleted_at', 'updated_at', 'is_verified'];

    protected $casts = [
        'images'           => 'array',
        'is_approved'      => 'boolean',
        'is_featured'      => 'boolean',
        //'is_verified'      => 'boolean',
        'admin_replied_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        $refresh = fn(Review $r) => \App\Models\Product::withTrashed()->find($r->product_id)?->refreshStats();
        static::saved(function (Review $r) use ($refresh) {
            $refresh($r);
            try {
                $name = $r->product?->name ?? 'পণ্য';
                if ($r->wasChanged('admin_reply') && filled($r->admin_reply)) {
                    $r->user?->notify(new \App\Notifications\CustomerEventNotification('review_reply', 'আপনার রিভিউয়ের উত্তর এসেছে', "{$name}: {$r->admin_reply}"));
                }
                if ($r->wasChanged('is_approved') && $r->is_approved && !$r->wasRecentlyCreated) {
                    $r->user?->notify(new \App\Notifications\CustomerEventNotification('review_approved', 'আপনার রিভিউ প্রকাশিত হয়েছে', "{$name} এর রিভিউ এখন সাইটে দেখা যাচ্ছে।"));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
        static::deleted($refresh);
        static::restored($refresh);
        static::updating(function (Review $review) {
            if ($review->isDirty('admin_reply') && $review->admin_reply && Str::length($review->admin_reply)) {
                $review->admin_replied_at = now();
            }
        });
    }

    // ── Accessors ──────────────────────────────────────────
    // Always expose a unified 'comment' field so frontend
    // doesn't need to know about the review/comment dual field
    public function getCommentAttribute(): ?string
    {
        return $this->attributes['comment'] ?? $this->attributes['review'] ?? null;
    }

    // Return full URLs for images so frontend needs no apiBase prefix logic
    public function getImagesAttribute($value): array
    {
        $paths = json_decode($value, true) ?? [];
        return array_map(fn($path) => str_starts_with($path, 'http') ? $path : storage_url($path), $paths);
    }

    // ── Relationships ──────────────────────────────────────

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
