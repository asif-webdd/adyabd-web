<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{BlogPost, BlogCategory, BlogComment, BlogLike};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    // ─── GET /api/v1/blog ─────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::published()
            ->with(['category:id,name,name_bn,slug,color', 'author:id,name'])
            ->when($request->category,  fn($q) => $q->whereHas('category', fn($q2) => $q2->where('slug', $request->category)))
            ->when($request->featured,  fn($q) => $q->where('is_featured', true))
            ->when($request->search,    fn($q) => $q->where(fn($q2) => $q2
                ->where('title',    'like', '%' . $request->search . '%')
                ->orWhere('title_bn', 'like', '%' . $request->search . '%')
                ->orWhere('excerpt',  'like', '%' . $request->search . '%')
            ))
            ->when($request->tag, fn($q) => $q->whereJsonContains('tags', $request->tag))
            ->orderByDesc('published_at')
            ->paginate($request->per_page ?? 9);

        $posts->getCollection()->transform(fn($p) => $this->transformPost($p));

        return $this->success($posts);
    }

    // ─── GET /api/v1/blog/{slug} ──────────────────────────

    public function show(Request $request, string $slug): JsonResponse
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with(['category:id,name,name_bn,slug,color', 'author:id,name,avatar'])
            ->firstOrFail();

        $post->increment('view_count');

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->with('category:id,name,slug')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn($p) => $this->transformPost($p));

        $liked      = false;
        $likeCount  = $post->likes()->count();

        if ($request->user()) {
            $liked = BlogLike::where('blog_post_id', $post->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        return $this->success([
            'post'       => $this->transformPost($post, true),
            'related'    => $related,
            'like_count' => $likeCount,
            'liked'      => $liked,
        ]);
    }

    // ─── GET /api/v1/blog/categories ─────────────────────

    public function categories(): JsonResponse
    {
        $categories = BlogCategory::active()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->ordered()           //  use the scopeOrdered() defined on BlogCategory
            ->get();

        return $this->success($categories);
    }

    // ─── GET /api/v1/blog/popular ────────────────────────

    public function popular(): JsonResponse
    {
        $posts = BlogPost::published()
            ->with('category:id,name,slug')
            ->orderByDesc('view_count')
            ->limit(5)
            ->get()
            ->map(fn($p) => $this->transformPost($p));

        return $this->success($posts);
    }

    // ─── GET /api/v1/blog/tags ───────────────────────────

    public function tags(): JsonResponse
    {
        $tags = BlogPost::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(20);

        return $this->success($tags);
    }

    // ─── GET /api/v1/blog/{id}/comments ──────────────────

    public function comments(int $id): JsonResponse
    {
        BlogPost::published()->findOrFail($id); // 404 if post doesn't exist or not published

        $comments = BlogComment::with([
            'user:id,name,avatar',
            'replies' => fn($q) => $q->approved()->with('user:id,name,avatar'),
        ])
            ->where('blog_post_id', $id)   //  was 'post_id'
            ->whereNull('parent_id')        //  replaces missing scopeRootLevel
            ->approved()
            ->orderByDesc('created_at')
            ->paginate(10);

        return $this->success($comments);
    }

    // ─── POST /api/v1/blog/{id}/comments ─────────────────

    public function storeComment(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'comment'      => 'required|string|max:1000',
            'author_name'  => 'required_without:user|string|max:100', //  was 'required_without:auth' (invalid)
            'author_email' => 'nullable|email',
            'parent_id'    => 'nullable|exists:blog_comments,id',
        ]);

        if ($v->fails()) {
            return $this->error($v->errors(), 422);
        }

        $post = BlogPost::published()->findOrFail($id);

        if (! $post->allow_comments) {
            return $this->error('Comments are disabled.', 422);
        }

        $user = $request->user();

        $comment = BlogComment::create([
            'blog_post_id' => $post->id,
            'user_id'      => $user?->id,
            'author_name'  => $user ? $user->name  : $request->author_name,
            'author_email' => $user ? $user->email : $request->author_email,
            'comment'      => $request->comment,
            'parent_id'    => $request->parent_id,
            'is_approved'  => (bool) $user, // Auto-approve logged-in users
        ]);

        return $this->success($comment->load('user:id,name,avatar'), 'Comment added.', 201);
    }

    // ─── POST /api/v1/blog/{id}/like ─────────────────────

    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $post   = BlogPost::published()->findOrFail($id);
        $userId = $request->user()?->id;
        $ip     = $request->ip();

        $existing = BlogLike::where('blog_post_id', $post->id)
            ->where(fn($q) =>
            $userId
                ? $q->where('user_id', $userId)
                : $q->where('ip_address', $ip)
            )
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            BlogLike::create([
                'blog_post_id' => $post->id,
                'user_id'      => $userId,
                'ip_address'   => $ip,
            ]);
            $liked = true;
        }

        return $this->success([
            'liked' => $liked,
            'count' => $post->likes()->count(),
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────

    private function transformPost(BlogPost $post, bool $full = false): array
    {
        $data = [
            'id'               => $post->id,
            'title'            => $post->title,
            'title_bn'         => $post->title_bn,
            'slug'             => $post->slug,
            'excerpt'          => $post->excerpt,
            'excerpt_bn'       => $post->excerpt_bn,
            'featured_image'   => $post->featured_image,
            'tags'             => $post->tags ?? [],
            'is_featured'      => $post->is_featured,
            'view_count'       => $post->view_count,
            'read_time'        => $post->read_time,
            'published_at'     => $post->published_at?->format('d M Y'),
            'published_at_iso' => $post->published_at?->toISOString(),
            'category'         => $post->category ? [
                'id'      => $post->category->id,
                'name'    => $post->category->name,
                'name_bn' => $post->category->name_bn,
                'slug'    => $post->category->slug,
                'color'   => $post->category->color,
            ] : null,
            'author'         => $post->author ? [
                'id'     => $post->author->id,
                'name'   => $post->author->name,
                'avatar' => $post->author->avatar_url ?? null,
            ] : ['name' => 'Shop Team'],
            'comments_count' => $post->comments()->approved()->count(),
            'likes_count'    => $post->likes()->count(),
        ];

        if ($full) {
            $data['content']          = $post->content;
            $data['content_bn']       = $post->content_bn;
            $data['allow_comments']   = $post->allow_comments;
            $data['meta_title']       = $post->meta_title       ?? $post->title;
            $data['meta_description'] = $post->meta_description ?? $post->excerpt;
        }

        return $data;
    }
}
