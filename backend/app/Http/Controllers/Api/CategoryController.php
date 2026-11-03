<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success($this->_index());
    }

    private function _index()
    {
        return Category::with('children')->withCount('products')->active()->root()->orderBy('sort_order')->get()->map(fn($c) => [
            'id'             => $c->id,
            'name'           => $c->name,
            'name_bn'        => $c->name_bn,
            'slug'           => $c->slug,
            'icon'           => $c->icon,
            'color'          => $c->color,
            'image'          => $c->image_url,
            'products_count' => $c->products_count,
            'children'       => $c->children->where('is_active', true)->values(),
        ]);
    }

    private function _tree()
    {
        return Category::active()->root()->with('children.children')->orderBy('sort_order')->get();
    }

    public function tree(): JsonResponse
    {
        return $this->success($this->_tree());
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::with('children')->active()->where('slug', $slug)->first();
        return $this->success($category);
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::with('children')->active()->where('slug', $slug)->first();
        if (!$category) {
            return $this->error(error: "Oops! category not found.", code: Response::HTTP_NOT_FOUND);
        }
        $childIds = $category->children()->pluck('id')->push($category->id);
        $products = Product::with(['primaryImage', 'seller:id,shop_name', 'brand' => fn($q) => $q->where('is_active', true)->select('id', 'name', 'slug', 'logo')])->active()->whereIn('category_id', $childIds)
            ->when($request->sort === 'price_asc', fn($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'rating', fn($q) => $q->orderByDesc('rating'))
            ->when($request->sort === 'newest', fn($q) => $q->orderByDesc('created_at'))
            ->when($request->min_price, fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->in_stock, fn($q) => $q->where('quantity', '>', 0))
            ->paginate($request->per_page ?? 20);

        return $this->success($products);
    }

}
