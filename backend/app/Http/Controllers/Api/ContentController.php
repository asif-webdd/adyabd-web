<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Category, Faq, Product, SitePage};
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

/** Public read-only endpoints for admin-managed storefront content. */
class ContentController extends Controller
{
    /** GET /pages/{slug} – content is null when the admin has not written one (the storefront keeps its default text). */
    public function page(string $slug): JsonResponse
    {
        $page = SitePage::where('slug', $slug)->where('is_active', true)->first();
        if (!$page) {
            return $this->success(['slug' => $slug, 'title' => null, 'content' => null]);
        }
        return $this->success([
            'slug' => $page->slug, 'title' => $page->title, 'content' => $page->safeContent(),
            'meta_description' => $page->meta_description, 'updated_at' => $page->updated_at,
        ]);
    }

    /** GET /sitemap-data – slugs for the storefront's sitemap.xml (Nuxt server route). */
    public function sitemap(): JsonResponse
    {
        $data = Cache::remember('sitemap_data', 600, fn() => [
            'products'   => Product::where('is_active', true)->whereNotNull('slug')->orderByDesc('id')->limit(5000)->get(['slug', 'updated_at']),
            'categories' => Category::where('is_active', true)->whereNotNull('slug')->get(['slug', 'updated_at']),
            'posts'      => \App\Models\BlogPost::published()->whereNotNull('slug')->orderByDesc('id')->limit(2000)->get(['slug', 'updated_at']),
        ]);
        return $this->success($data);
    }

    /** GET /faqs?category= */
    public function faqs(): JsonResponse
    {
        $rows = Faq::where('is_active', true)
            ->when(request('category'), fn($q, $c) => $q->where('category', $c))
            ->orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'answer', 'category']);
        return $this->success($rows);
    }
}
