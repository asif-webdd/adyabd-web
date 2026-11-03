<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success($this->_index());
    }

    private function _index()
    {
        $cart = Cart::where('user_id', request()->user()->id)->with(['product.primaryImage', 'variant'])->get()->map(fn($c) => [
            'id'         => $c->id,
            'product_id' => $c->product_id,
            'variant_id' => $c->variant_id,
            'quantity'   => $c->quantity,
            'product'    => [
                'name'     => $c->product->name,
                'slug'     => $c->product->slug,
                'price'    => \App\Models\FlashSaleProduct::currentPrice($c->product, $c->variant),
                'regular_price' => $c->variant?->price ?? $c->product->price,
                'image'    => $c->product->thumbnail,
                'in_stock' => $c->product->isInStock(),
            ],
            'total'      => \App\Models\FlashSaleProduct::currentPrice($c->product, $c->variant) * $c->quantity,
        ]);

        $subtotal = $cart->sum('total');
        return ['items' => $cart, 'subtotal' => $subtotal, 'count' => $cart->sum('quantity')];
    }

    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);
        if ($validator->fails()) return $this->error($validator->errors(), 422);

        $product = Product::findOrFail($request->product_id);
        if (!$product->is_active) return $this->error('Product is not available.', 422);
        if ($product->quantity < $request->quantity) return $this->error('Insufficient stock.', 422);

        $item = Cart::firstOrNew(
            ['user_id' => $request->user()->id, 'product_id' => $request->product_id, 'variant_id' => $request->variant_id]
        );
        $newQty = (int) ($item->exists ? $item->quantity : 0) + (int) $request->quantity;
        if ($product->quantity < $newQty) return $this->error('Insufficient stock.', 422);
        $item->quantity = $newQty;
        $item->save();

        return $this->success($item, 'Added to cart.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), ['quantity' => 'required|integer|min:1']);
        if ($validator->fails()) return $this->error($validator->errors(), 422);

        $item = Cart::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $item->update(['quantity' => $request->quantity]);
        return $this->success($item, 'Updated successfully.');
    }

    public function remove(Request $request, int $id): JsonResponse
    {
        Cart::where('id', $id)->where('user_id', $request->user()->id)->delete();
        return $this->success([], 'Item removed.');
    }

    public function clear(Request $request): JsonResponse
    {
        Cart::where('user_id', $request->user()->id)->delete();
        return $this->success([], 'Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string'
        ]);
        if ($validator->fails()) return $this->error($validator->errors(), 422);

        $coupon = Coupon::where('code', strtoupper($request->code))->first();
        if (!$coupon || !$coupon->isValid()) return $this->error('Coupon code is invalid or expired.', 422);

        $cart = Cart::where('user_id', $request->user()->id)->with(['product', 'variant'])->get();
        $subtotal = $cart->sum(fn($c) => \App\Models\FlashSaleProduct::currentPrice($c->product, $c->variant) * $c->quantity);

        if ($subtotal < $coupon->min_order_amount) {
            return $this->error("A minimum order of \${$coupon->min_order_amount} is required to use this coupon.", 422);
        }

        $discount = $coupon->calculateDiscount($subtotal);
        return $this->success(['coupon' => $coupon, 'discount' => $discount, 'new_total' => $subtotal - $discount], 'Coupon applied successfully!');
    }

}
