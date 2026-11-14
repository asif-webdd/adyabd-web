<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\Review;
use Illuminate\Http\Request;

class SellerPublicApiController extends Controller
{
    /**
     * GET /api/v1/sellers/{seller}
     */
    public function show(Request $request, Seller $seller)
    {
        abort_unless($seller->status === 'active', 404);

        $seller->load(['user:id,name,email,phone,created_at']);

        $reviewStats = Review::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg,
                SUM(rating = 5) as five, SUM(rating = 4) as four,
                SUM(rating = 3) as three, SUM(rating = 2) as two, SUM(rating = 1) as one')
            ->first();



        $totalOrders = OrderItem::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->distinct('order_id')->count('order_id');

        $totalSales = OrderItem::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->whereHas('order', fn($q) => $q->where('status', 'delivered'))
            ->sum('total_price');


        // --- Top seller logic ---
        $allSales = Seller::select('sellers.id')
            ->leftJoin('products', 'products.seller_id', '=', 'sellers.id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', '=', 'delivered');
            })
            ->groupBy('sellers.id')
            ->selectRaw('sellers.id, COALESCE(SUM(order_items.total_price), 0) as total_sales')
            ->orderByDesc('total_sales')
            ->pluck('total_sales', 'id');

        $totalSellers   = $allSales->count();
        $cutoffIndex    = (int) ceil($totalSellers * 0.30); // top 30%
        $topSellerIds   = $allSales->keys()->take($cutoffIndex)->flip();
        $isTopSeller    = $topSellerIds->has($seller->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $seller->id,
                'shop_name'    => $seller->shop_name,
                'shop_slug'    => $seller->slug,
                'description'  => $seller->description,
                // full URLs (the DB holds relative paths such as sellers/logos/x.jpg)
                'logo'         => $seller->logo ? $seller->logo_url : null,
                'banner'       => $seller->banner ? (str_starts_with($seller->banner, 'http') ? $seller->banner : storage_url($seller->banner)) : null,
                'address'      => $seller->address,
                'is_verified'  => $seller->is_verified,
                'is_active'    => $seller->is_active,
                'is_top_seller' => $isTopSeller,
                'followers_count' => $seller->followers()->count(),
                'is_following'    => $this->isFollowing($request, $seller),
                'member_since' => $seller->created_at?->format('d M Y'),
                'owner' => [
                    'name'  => $seller->user?->name,
                    'phone' => $seller->user?->phone,
                ],
                'stats' => [
                    'total_products'   => $seller->products()->where('is_active', true)->count(),
                    'total_orders'     => $totalOrders,
                    'total_sales'      => round($totalSales, 2),
                    'total_reviews'    => (int) ($reviewStats->total ?? 0),
                    'average_rating'   => round($reviewStats->avg ?? 0, 1),
                    'rating_breakdown' => [
                        '5_star' => (int) ($reviewStats->five  ?? 0),
                        '4_star' => (int) ($reviewStats->four  ?? 0),
                        '3_star' => (int) ($reviewStats->three ?? 0),
                        '2_star' => (int) ($reviewStats->two   ?? 0),
                        '1_star' => (int) ($reviewStats->one   ?? 0),
                    ],
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/sellers/{seller}/products
     */
    public function products(Request $request, Seller $seller)
    {
        abort_unless($seller->status === 'active', 404);

        $query = $seller->products()
            ->where('is_active', true)
            ->with([
                'primaryImage', 'category:id,name', 'brand:id,name', 'seller:id,shop_name,shop_name_bn',
            ])->withCount(['reviews', 'variants'])->withAvg('reviews', 'rating');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($min = $request->get('min_price')) {
            $query->where('price', '>=', $min);
        }
        if ($max = $request->get('max_price')) {
            $query->where('price', '<=', $max);
        }

        match($request->get('sort', 'latest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular'    => $query->orderBy('total_sales', 'desc'),
            'rating'     => $query->orderBy('rating', 'desc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate($request->get('per_page', 20));

        return $this->success([
            'data'    => $products->map(fn($p) => $this->formatProduct($p)),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ]
        ]);
    }

    /**
     * GET /api/v1/sellers/{seller}/reviews
     */
    public function reviews(Request $request, Seller $seller)
    {
        abort_unless($seller->status === 'active', 404);

        $query = Review::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->where('is_approved', true)
            ->with(['user:id,name', 'product:id,name,slug']);

        if ($rating = $request->get('rating')) {
            $query->where('rating', $rating);
        }

        match($request->get('sort', 'latest')) {
            'highest' => $query->orderBy('rating', 'desc'),
            'lowest'  => $query->orderBy('rating', 'asc'),
            default   => $query->orderBy('created_at', 'desc'),
        };

        $reviews  = $query->paginate($request->get('per_page', 15));

        $stats = Review::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg,
                SUM(rating=5) as r5, SUM(rating=4) as r4,
                SUM(rating=3) as r3, SUM(rating=2) as r2, SUM(rating=1) as r1')
            ->first();

        return response()->json([
            'success' => true,
            'summary' => [
                'total'          => (int) $stats->total,
                'average_rating' => round($stats->avg ?? 0, 1),
                'breakdown'      => [
                    5 => (int) $stats->r5,
                    4 => (int) $stats->r4,
                    3 => (int) $stats->r3,
                    2 => (int) $stats->r2,
                    1 => (int) $stats->r1,
                ],
            ],
            'data' => $reviews->map(fn($r) => [
                'id'          => $r->id,
                'rating'      => $r->rating,
                'title'       => $r->title,
                'review'      => $r->review,
                'images'      => $r->images ?? [],
                'is_verified' => $r->is_verified,
                'is_featured' => $r->is_featured,
                'admin_reply' => $r->admin_reply,
                'created_at'  => $r->created_at?->format('d M Y'),
                'customer'    => ['name' => $r->user?->name ?? 'Anonymous'],
                'product'     => [
                    'id'   => $r->product?->id,
                    'name' => $r->product?->name,
                    'slug' => $r->product?->slug,
                ],
            ]),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Shared product formatter — matches your frontend structure exactly
    // ─────────────────────────────────────────────────────────────────
    private function formatProduct(\App\Models\Product $product)
    {
        $price         = (float) $product->price;
        $originalPrice = (float) ($product->compare_price ?? $price);
        $discount      = $originalPrice > $price ? (int) round((($originalPrice - $price) / $originalPrice) * 100) : 0;

        $imagePath = $product->primaryImage?->image_path;
        $image     = $imagePath ? (str_starts_with($imagePath, 'http') ? $imagePath : storage_url($imagePath)) : asset('images/product-placeholder.png');

        //dd($product->toArray(), $product->rating);
        return [
            // Identity
            'id'               => $product->id,
            'slug'             => $product->slug,
            'sku'              => $product->sku,
            'quantity'         => (int) $product->quantity,
            'has_variants'     => (bool) $product->has_variants || (int) ($product->variants_count ?? 0) > 0,
            'compare_price'    => $product->compare_price,

            // Names
            'name'             => $product->name,
            'nameBn'           => $product->name_bn,

            // Seller
            'seller'           => $product->seller?->shop_name ?? '',
            'sellerBn'         => $product->seller?->shop_name_bn ?? '',
            // Pricing
            'price'            => $price,
            'originalPrice'    => $originalPrice,
            'discount'         => $discount,

            // Ratings & Reviews
            'rating'           => (float) round($product->reviews_avg_rating ?? 0, 1),
            'reviews'          => (int) ($product->reviews_count ?? 0),

            // Descriptions
            'shortDescription' => $product->short_description ?? '',
            'description'      => $product->description ?? '',
            'descriptionBn'    => $product->description_bn ?? '',

            // Media
            'image'            => $image,
            'full_url_image'   => $image,
            // Shipping
            'delivery'         => '2-3 Days',
            // Timestamps
            'createdAt'        => $product->created_at,
            'updatedAt'        => $product->updated_at,
        ];
    }

    /**
     * POST /api/v1/sellers/{seller}/follow  (auth) — toggles follow / unfollow.
     */
    public function follow(Request $request, Seller $seller)
    {
        abort_unless($seller->status === 'active', 404);

        $user   = $request->user();
        $result = $seller->followers()->toggle($user->id);
        $following = count($result['attached']) > 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'is_following'    => $following,
                'followers_count' => $seller->followers()->count(),
            ],
            'message' => $following ? 'Following.' : 'Unfollowed.',
        ]);
    }

    /** Public endpoints are unauthenticated, so look for a token manually. */
    private function isFollowing(Request $request, Seller $seller): bool
    {
        $user = $request->user('api');
        return $user ? $seller->followers()->where('users.id', $user->id)->exists() : false;
    }
}
