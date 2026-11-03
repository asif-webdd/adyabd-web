<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;

class FlashSaleController extends Controller
{
    public function active(): JsonResponse
    {
        $flashSale = $this->_active();
        if ($flashSale === null) {
            return $this->success([]);
        }
        return $this->success($flashSale);
    }

    private function _active()
    {
        $flashSale = FlashSale::where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now())->with(['products.product.primaryImage'])->first();
        if (!$flashSale) {
            return null;
        }
        $flashSale->products = $flashSale->products->map(function ($item) {
            if ($item->product && $item->product->primary_image) {
                $item->product->primary_image->image_path = asset($item->product->primary_image->image_path);
            }
            return $item;
        });
        return $flashSale;
    }

}
