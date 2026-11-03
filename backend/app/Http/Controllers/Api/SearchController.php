<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->q;
        if (strlen($q) < 2) return $this->success([]);

        $products = Product::active()
            ->where(fn($query) => $query->where('name', 'like', "%{$q}%")->orWhere('name_bn', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"))
            ->with('primaryImage', 'seller:id,slug,shop_name,shop_name_bn,logo')
            ->limit(20)->get();

        $categories = Category::active()->where('name', 'like', "%{$q}%")->orWhere('name_bn', 'like', "%{$q}%")->limit(5)->get(['id', 'name', 'name_bn', 'slug', 'icon']);
        $sellers = Seller::active()->where('shop_name', 'like', "%{$q}%")->limit(5)->get(['id', 'shop_name', 'slug', 'logo']);

        return $this->success(['products' => $products, 'categories' => $categories, 'sellers' => $sellers, 'total' => $products->count()]);
    }
}
