<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)->whereHas('product')->with(['product.primaryImage'])->get()->map(fn($w) => [
            'product_id' => $w->product_id,
            'product'    => [
                'id'       => $w->product->id,
                'slug'     => $w->product->slug,
                'name'     => $w->product->name,
                'price'    => $w->product->price,
                'image'    => $w->product->thumbnail,
                'in_stock' => $w->product->isInStock(),
            ],
        ]);

        return $this->success($items);
    }

    public function toggle(Request $request, int $productId): JsonResponse
    {
        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->success(['wishlisted' => false], 'Removed from wishlist.');
        }

        Wishlist::create(['user_id' => $request->user()->id, 'product_id' => $productId]);
        return $this->success(['wishlisted' => true], 'Added to wishlist.');
    }

    public function remove(Request $request, int $productId): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)->where('product_id', $productId)->delete();
        return $this->success([], 'Removed from wishlist.');
    }
}
