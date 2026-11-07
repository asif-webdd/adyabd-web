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
            $items = $this->withUrls($base->banners()->get());
            return $this->success(['banners' => $items, 'sliders' => [], 'all' => $items]);
        }

        if ($type === 'slider') {
            $items = $this->withUrls($base->sliders()->get());
            return $this->success(['banners' => [], 'sliders' => $items, 'all' => $items]);
        }

        $all = $this->withUrls($base->get());
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
        return $this->withUrls(Banner::active()->forPosition($position)->sliders()->orderBy('sort_order')->get());
    }

    // ── Internal helpers called by LandingPageController ─────────────────────

    /**
     * GET /api/banners/static?position=hero
     */
    public function static(Request $request): JsonResponse
    {
        $position = $request->get('position', 'hero');
        return $this->success($this->withUrls(Banner::active()->forPosition($position)->banners()->orderBy('sort_order')->get()));
    }

    /**
     * All active banners (type=banner) across ALL positions,
     * so the frontend can filter by position itself.
     */
    public function _banners()
    {
        // scope: type = 'banner'
        return $this->withUrls(Banner::active()->banners()->orderBy('sort_order')->get());
    }

    /**
     * Turn stored paths (banners/abc.jpg) into full public URLs (R2 or local storage).
     * mobile_image falls back to the desktop image when empty.
     */
    private function withUrls($items)
    {
        return $items->map(function ($item) {
            $item->image        = storage_url($item->image);
            $item->mobile_image = storage_url($item->mobile_image) ?: $item->image;
            return $item;
        });
    }
}