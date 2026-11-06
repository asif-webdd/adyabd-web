<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSaleProduct;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->with(['primaryImage', 'seller:id,shop_name,slug', 'category:id,name,slug'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->seller_id))
            ->when($request->min_price, fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->in_stock, fn($q) => $q->where('quantity', '>', 0))
            ->when($request->sort === 'price_asc', fn($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'rating', fn($q) => $q->orderByDesc('rating'))
            ->when($request->sort === 'newest', fn($q) => $q->orderByDesc('created_at'))
            ->when($request->sort === 'popular', fn($q) => $q->orderByDesc('total_sales'))
            ->when(!$request->sort, fn($q) => $q->orderByDesc('is_featured')->orderByDesc('created_at'))
            ->paginate(min((int) ($request->per_page ?: setting('products_per_page', 20)), 100));


        return $this->success($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with([
            'images',
            'seller:id,shop_name,slug,logo',
            'category:id,name,slug',
            'variants',
            'brand',
            'flashSale',
            'reviews' => fn($q) => $q->with(['user' => fn($uQ) => $uQ->select('id', 'name', 'email')])->where('is_approved', true)->latest()->limit(5)])
            ->where('is_active', true)->where('slug', $slug)->first();

        if (!$product) {
            return $this->error(error: "Oops! product not found", code: Response::HTTP_NOT_FOUND);
        }
        $product->increment('total_views');
        $flashSale = FlashSaleProduct::where('product_id', $product->id)->whereHas('flashSale', fn($q) => $q->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now()))->with('flashSale')->first();
        $flashSaleProducts = $flashSale ? ['discount_percentage' => $flashSale->discount_percentage, 'special_price' => $flashSale->special_price, 'ends_at' => $flashSale->flashSale->ends_at] : null;
        $relatedProducts = Product::where('is_active', true)->where('id', '!=', $product->id)->where('category_id', $product->category_id)->with([
            'primaryImage',
            'seller' => fn($q) => $q->select([
                'id',
                'shop_name',
                'shop_name_bn',
                'slug',
                'logo'
            ])
        ])->limit(6)->get();
        $canReview = false;
        if (Auth::guard('api')->check()) {
            $userId = Auth::guard("api")->id();
            $alreadyReviewed = Review::where('product_id', $product->id)->where('user_id', $userId)->exists();
            if (!$alreadyReviewed) {
                $canReview = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $userId)->where('status', 'delivered'))->where('product_id', $product->id)->exists();
            }
        }
        return $this->success([
            'product'    => $product,
            'flash_sale' => $flashSaleProducts,
            'related'    => $relatedProducts,
            'can_review' => $canReview
        ]);
    }

    public function featured(): JsonResponse
    {
        return $this->success($this->_featured());
    }

    ##private function _featured()
    ##{
        ##return Product::where('is_active', true)->where('is_featured', true)->with([
            ##'primaryImage',
            ##'seller' => fn($q) => $q->select([
                ##'id',
                ##'shop_name',
                ##'shop_name_bn',
                ##'slug',
                ##'logo'
            ##])
        ##])->whereHas('reviews', fn($query) => $query->where('is_approved', true))->withCount('reviews')->withAvg('reviews', 'rating')
            ##->orderByDesc('created_at')->limit(request('limit') ?? 10)->get();
    ##}

    private function _featured()
    {
        $with = [
            'primaryImage',
            'seller' => fn($q) => $q->select([
                'id',
                'shop_name',
                'shop_name_bn',
                'slug',
                'logo'
            ])
        ];

        $limit = (int) (request('limit') ?? 10);

        // "All products" tab: featured products first, then newest of the rest.
        // No review requirement, so new stores with no reviews still show products.
        return Product::where('is_active', true)
            ->with($with)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function trending(): JsonResponse
    {
        // ->orderByDesc('total_sales')
        return $this->success($this->_trending());
    }

    private function _trending()
    {
        return Product::where('is_active', true)->where('is_trending', true)->with([
            'primaryImage',
            'seller' => fn($query) => $query->select([
                'id',
                'shop_name',
                'shop_name_bn',
                'slug',
                'logo'
            ])
        ])->withCount('reviews')->withAvg('reviews', 'rating')->limit(request('limit') ?? 10)->get();
    }

    public function newArrivals(): JsonResponse
    {
        return $this->success($this->_newArrivals());
    }

    private function _newArrivals()
    {
        return Product::where('is_active', true)->with([
            'primaryImage',
            'seller' => fn($query) => $query->select([
                'id',
                'shop_name',
                'shop_name_bn',
                'slug',
                'logo'
            ])
        ])->withCount('reviews')->withAvg('reviews', 'rating')->orderByDesc('created_at')->limit(request('limit') ?? 10)->get();
    }

    public function related(mixed $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $relatedProducts = Product::where('is_active', true)->where('id', '!=', $id)->where('category_id', $product->category_id)->with([
            'primaryImage',
            'seller' => fn($query) => $query->select([
                'id',
                'shop_name',
                'shop_name_bn',
                'slug',
                'logo'
            ])
        ])->withCount('reviews')->withAvg('reviews', 'rating')->limit(6)->get();

        return $this->success($relatedProducts);
    }
}
