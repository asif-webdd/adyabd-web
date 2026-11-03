<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * GET /api/v1/deals/flash
     * Returns flash-sale products with discount_percentage, sold_percentage, quantity.
     * Accepts optional `category` slug filter.
     */
    public function flash(Request $request)
    {
        /*$query = Product::with(['seller:id,shop_name', 'primaryImage'])
            ->where('is_active', true)
            ->where('discount_percentage', '>', 0)
            ->orderByDesc('discount_percentage');*/
        $query = Product::with([
            'seller:id,shop_name',
            'primaryImage'
        ])
            ->where('is_active', true)
            ->whereNotNull('compare_price')
            ->whereColumn('compare_price', '>', 'price')
            ->select('*')
            ->selectRaw('ROUND(((compare_price - price) / compare_price) * 100) as discount_percentage')
            ->orderByDesc('discount_percentage');


        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $limit = min((int)$request->get('limit', 20), 50);
        $products = $query->take($limit)->get();

        $data = $products->map(function (Product $p) {
            $totalStock = $p->stock_alert_quantity ?? 100;
            $sold = $p->total_sales ?? 0;
            $soldPct = $totalStock > 0 ? min(100, round(($sold / $totalStock) * 100)) : 0;

            return [
                'id'                  => $p->id,
                'name'                => $p->name,
                'slug'                => $p->slug,
                'price'               => $p->price,
                'compare_price'       => $p->compare_price,
                'discount_percentage' => $p->discount_percentage,
                'quantity'            => $p->quantity,
                'sold_percentage'     => $soldPct,
                'total_sales'         => $p->total_sales,
                'primary_image'       => $p->primaryImage?->only(['id', 'path']),
                'thumbnail'           => $p->thumbnail,
                'seller'              => $p->seller?->only(['id', 'shop_name']),
                'is_active'           => $p->is_active,
            ];
        });

        return response()->json(['data' => $data, 'total' => $data->count()]);
    }
}
