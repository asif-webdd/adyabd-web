<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    /**
     * GET /api/banners
     */
    public function index(Request $request): JsonResponse
    {
        $position = $request->get('position', 'hero');
        $type = $request->get('type'); // null = return both

        $base = Banner::active()->forPosition($position)->orderBy('sort_order');

        if ($type === 'banner') {
            $items = $base->banners()->get();
            return $this->success(['banners' => $items, 'sliders' => [], 'all' => $items]);
        }

        if ($type === 'slider') {
            $items = $base->sliders()->get();
            return $this->success(['banners' => [], 'sliders' => $items, 'all' => $items]);
        }

        $all = $base->get();
        $banners = $all->where('type', 'banner')->values();
        $sliders = $all->where('type', 'slider')->values();

        return $this->success(['banners' => $banners, 'sliders' => $sliders, 'all' => $all]);
    }

    /**
     * GET /api/banners/sliders?position=hero
     */
    public function sliders(Request $request): JsonResponse
    {
        return $this->success($this->_sliders());
    }

    /**
     * All active sliders for a given position (default: hero).
     */
    public function _sliders(?string $position = null)
    {
        $position ??= request('position', 'hero');
        return Banner::active()->forPosition($position)->sliders()->orderBy('sort_order')->get();
    }

    // ── Internal helpers called by LandingPageController ─────────────────────

    /**
     * GET /api/banners/static?position=hero
     */
    public function static(Request $request): JsonResponse
    {
        $position = $request->get('position', 'hero');
        return $this->success(Banner::active()->forPosition($position)->banners()->orderBy('sort_order')->get());
    }

    /**
     * All active banners (type=banner) across ALL positions,
     * so the frontend can filter by position itself.
     */
    public function _banners()
    {
        // scope: type = 'banner'
        return Banner::active()->banners()->orderBy('sort_order')->get()->map(function ($item) {
            if (!Str::startsWith($item->mobile_image, 'http')){
                $item->mobile_image = storage_url($item->mobile_image);
            }
            if (!Str::startsWith($item->image, 'http')){
                $item->image = storage_url($item->image);
            }
            return $item;
        });
    }
}
