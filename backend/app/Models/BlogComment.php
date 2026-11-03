<?php
// BlogComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class BlogComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id',   //  was 'post_id'
        'user_id',
        'author_name',
        'author_email',   //  was missing
        'comment',
        'is_approved',
        'parent_id',      //  was missing
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id'); //  was 'post_id'
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogComment::class, 'parent_id'); //  was missing
    }

    public function replies(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'parent_id');   //  was missing
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }
}
