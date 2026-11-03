<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionException;

class LandingPageController extends Controller
{
    /**
     * @throws ReflectionException
     */
    public function __invoke(Request $request)
    {
        return $this->success([
            'sliders'      => $this->sliders(),
            'banners'      => $this->banners(),
            'categories'   => $this->categories(),
            'featured'     => $this->featuredProducts(),
            'trending'     => $this->trendingProducts(),
            'new_arrivals' => $this->newArrivalProducts(),
            'flash_sale'   => $this->flashSaleProducts(),
        ]);
    }

    /**
     * Hero sliders (position=hero, type=slider).
     * @throws ReflectionException
     */
    private function sliders()
    {
        $controller = app(BannerController::class);
        $method     = new ReflectionMethod($controller, '_sliders');
        $method->setAccessible(true);
        // Pass 'hero' explicitly — _sliders() now accepts a $position argument
        return $method->invoke($controller, 'hero');
    }

    /**
     * All banners (type=banner) across every position.
     * The store getters filter by position on the frontend.
     * @throws ReflectionException
     */
    private function banners()
    {
        $controller = app(BannerController::class);
        $method     = new ReflectionMethod($controller, '_banners');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    /** @throws ReflectionException */
    private function categories()
    {
        $controller = app(CategoryController::class);
        $method     = new ReflectionMethod($controller, '_index');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    /** @throws ReflectionException */
    private function featuredProducts()
    {
        $controller = app(ProductController::class);
        $method     = new ReflectionMethod($controller, '_featured');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    /** @throws ReflectionException */
    private function trendingProducts()
    {
        $controller = app(ProductController::class);
        $method     = new ReflectionMethod($controller, '_trending');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    /** @throws ReflectionException */
    private function newArrivalProducts()
    {
        $controller = app(ProductController::class);
        $method     = new ReflectionMethod($controller, '_newArrivals');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }

    /** @throws ReflectionException */
    private function flashSaleProducts()
    {
        $controller = app(FlashSaleController::class);
        $method     = new ReflectionMethod($controller, '_active');
        $method->setAccessible(true);
        return $method->invoke($controller);
    }
}
