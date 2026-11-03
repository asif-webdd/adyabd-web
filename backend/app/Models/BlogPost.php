<?php
// BlogPost.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;             //  was missing (migration has softDeletes())
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class BlogPost extends Model
{
    use HasFactory, SoftDeletes; //  was missing

    protected $fillable = [
        'title',
        'title_bn',
        'slug',
        'blog_category_id',
        'author_id',
        'status',
        'published_at',
        'is_featured',
        'allow_comments',
        'featured_image',
        'tags',
        'excerpt',
        'content',
        'excerpt_bn',
        'content_bn',
        'meta_title',
        'meta_description',
        'view_count',
    ];

    protected $casts = [
        'published_at'   => 'datetime',
        'is_featured'    => 'boolean',
        'allow_comments' => 'boolean',
        'tags'           => 'array',
        'view_count'     => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'blog_post_id'); //  was 'post_id'
    }

    public function likes(): HasMany
    {
        return $this->hasMany(BlogLike::class, 'blog_post_id');    //  was missing
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
