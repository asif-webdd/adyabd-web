<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * GET /api/v1/coupons/public
     * Returns publicly listed coupons for the /deals page.
     */
    public function public()
    {
        $coupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderByDesc('value')
            ->limit(12)
            ->get(['code', 'description', 'type', 'value', 'min_order_amount', 'expires_at']);

        return response()->json($coupons);
    }

    /**
     * POST /api/v1/coupons/validate
     * and return coupon details without applying it.
     */
    public function validate(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $coupon = Coupon::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $coupon) {
            return response()->json(['message' => 'Coupon code is invalid or expired.'], 422);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit has been reached.'], 422);
        }

        return response()->json([
            'valid'            => true,
            'code'             => $coupon->code,
            'description'      => $coupon->description,
            'discount_type'    => $coupon->type,
            'discount_value'   => $coupon->value,
            'min_order_amount' => $coupon->min_order_amount,
        ]);
    }
}
