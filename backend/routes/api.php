<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController,
    BlogController,
    ProductController,
    OrderController,
    CartController,
    SellerPublicApiController,
    WishlistController,
    AddressController,
    ReviewController,
    PaymentController,
    NotificationController,
    SupportController,
    SearchController,
    SettingController,
    AppearanceController,
    BannerController,
    FlashSaleController,
    ContentController,
    CategoryController,
    UserController,
    SellerApiController,
    CouponController,
    DealController,
    GeoController,
    ReturnController
};
use App\Http\Controllers\Api\TrackingLogController;



Route::post('/tracking/log', [TrackingLogController::class, 'store'])
    ->withoutMiddleware('throttle:api');

Route::get("health", function () {
    return [
        'success' => true,
        'message' => "OK"
    ];
});
Route::prefix('v1')->group(function () {
    // Courier webhooks (no auth)
    //Route::post('webhooks/steadfast/{secret?}', [\App\Http\Controllers\Api\SteadfastWebhookController::class, 'handle'])->name('api.webhooks.steadfast');
    Route::post('webhooks/steadfast', [\App\Http\Controllers\Api\SteadfastWebhookController::class, 'handle'])->name('api.webhooks.steadfast');

    Route::get("/landing-page", \App\Http\Controllers\Api\LandingPageController::class);
    Route::get('checkout/config', [\App\Http\Controllers\Api\CheckoutController::class, 'config']);

    // ─── Geography (public — no auth) ────────────────────
    Route::prefix('geo')->group(function () {
        Route::get('divisions', [GeoController::class, 'divisions']);
        Route::get('districts', [GeoController::class, 'districts']);
        Route::get('upazilas', [GeoController::class, 'upazilas']);
    });

    // ─── Public Routes ────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:3,1');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');
        Route::post('social-login', [AuthController::class, 'socialLogin'])->middleware('throttle:10,1');
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/featured', [ProductController::class, 'featured']);
        Route::get('/trending', [ProductController::class, 'trending']);
        Route::get('/new-arrivals', [ProductController::class, 'newArrivals']);
        Route::get('/{product:slug}', [ProductController::class, 'show']);
        Route::get('/{id}/related', [ProductController::class, 'related']);
        Route::get('/{id}/reviews', [ReviewController::class, 'productReviews']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/tree', [CategoryController::class, 'tree']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
        Route::get('/{slug}/products', [CategoryController::class, 'products']);
    });

    Route::get('banners', [BannerController::class, 'index']);
    Route::get('flash-sales/active', [FlashSaleController::class, 'active']);
    Route::get('pages/{slug}', [ContentController::class, 'page']);
    Route::get('sitemap-data', [ContentController::class, 'sitemap']);
    Route::get('faqs', [ContentController::class, 'faqs']);
    Route::get('settings/public', [SettingController::class, 'public']);
    Route::get('appearance/public', [AppearanceController::class, 'public']);
    Route::post('coupons/validate', [CouponController::class, 'validate']);
    Route::get('search', [SearchController::class, 'search']);
    Route::post('contact', [\App\Http\Controllers\Api\ContactController::class, 'store'])->middleware('throttle:5,1');

    // Flash deals & public coupons (used by /deals page)
    Route::get('deals/flash', [DealController::class, 'flash']);
    Route::get('coupons/public', [CouponController::class, 'public']);

    /*Route::get('sellers/{id}',          [SellerApiController::class, 'show']);
    Route::get('sellers/{id}/products', [SellerApiController::class, 'sellerProducts']);*/
    // Seller public routes
    Route::prefix('sellers/{seller:slug}')->group(function () {
        Route::get('/', [SellerPublicApiController::class, 'show']);
        Route::get('/products', [SellerPublicApiController::class, 'products']);
        Route::get('/reviews', [SellerPublicApiController::class, 'reviews']);
        Route::post('/follow', [SellerPublicApiController::class, 'follow'])->middleware(['auth:api', 'check.blocked']);
    });

    // Payment webhook (no auth)
    Route::any('payment/zinipay/webhook', [PaymentController::class, 'zinipayWebhook']);

    // ─── Authenticated Routes ─────────────────────────────
    Route::middleware(['auth:api', 'check.blocked'])->group(function () {

        Route::prefix('/auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('update-profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
            Route::post('update-fcm', [AuthController::class, 'updateFcmToken']);
        });

        Route::prefix('/user')->group(function () {
            Route::get('profile', [UserController::class, 'profile']);
            Route::put('profile', [UserController::class, 'updateProfile']);
            Route::put('password', [UserController::class, 'changePassword']);     // NEW – used by account/profile
            Route::post('avatar', [UserController::class, 'uploadAvatar']);        // NEW – used by account/profile
            Route::delete('account', [UserController::class, 'deleteAccount']);    // NEW – danger zone
            Route::get('wallet', [UserController::class, 'wallet']);
            Route::get('transactions', [UserController::class, 'transactions']);
            Route::post('wallet/withdraw', [UserController::class, 'walletWithdraw']); // NEW – used by account/wallet
            Route::get('dashboard', [UserController::class, 'dashboard']);
        });

        Route::apiResource('addresses', AddressController::class);
        Route::put('/addresses/{id}/default', [AddressController::class, 'setDefault']);

        Route::prefix('/cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/', [CartController::class, 'add']);
            Route::put('/{id}', [CartController::class, 'update']);
            Route::delete('/{id}', [CartController::class, 'remove']);
            Route::delete('/', [CartController::class, 'clear']);
            Route::post('apply-coupon', [CartController::class, 'applyCoupon']);
        });

        Route::prefix('/wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/{productId}', [WishlistController::class, 'toggle']);
            Route::delete('/{productId}', [WishlistController::class, 'remove']);
        });

        Route::prefix('/orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{orderNumber}', [OrderController::class, 'show']);
            Route::put('/{id}/cancel', [OrderController::class, 'cancel']);
            Route::post('/{id}/return', [OrderController::class, 'requestReturn']);
            Route::get('/{orderNumber}/track', [OrderController::class, 'track']);
            Route::get('/{id}/invoice', [OrderController::class, 'invoice']);
        });

        // Returns (standalone endpoint used by /order/return page)
        Route::prefix('/returns')->group(function () {
            Route::get('eligible-orders', [ReturnController::class, 'eligibleOrders']); // NEW
            Route::post('/', [ReturnController::class, 'store']);                       // NEW
            Route::get('/', [ReturnController::class, 'index']);                        // NEW
        });

        Route::prefix('/reviews')->group(function () {
            Route::get('/my', [ReviewController::class, 'myReviews']);            // renamed from /
            Route::get('/pending', [ReviewController::class, 'pendingReviews']);  // NEW – items bought, not reviewed
            Route::post('/', [ReviewController::class, 'store']);
            Route::put('/{id}', [ReviewController::class, 'update']);
            Route::delete('/{id}', [ReviewController::class, 'destroy']);
        });

        Route::prefix('/payment')->group(function () {
            Route::post('zinipay/create', [PaymentController::class, 'zinipayCreate']);
            Route::post('zinipay/verify', [PaymentController::class, 'zinipayVerify']);
        });

        Route::prefix('/notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::put('/{id}/read', [NotificationController::class, 'markRead']);
            Route::put('/read-all', [NotificationController::class, 'markAllRead']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        });

        // Support / tickets  (prefix changed: /support/* instead of /support/tickets/*)
        Route::prefix('/support')->group(function () {
            Route::get('/', [SupportController::class, 'index']);
            Route::post('/', [SupportController::class, 'create']);
            Route::get('/{id}', [SupportController::class, 'show']);
            Route::post('/{id}/messages', [SupportController::class, 'sendMessage']);
            // Keep old /tickets/* aliases for backward-compat
            Route::get('tickets', [SupportController::class, 'index']);
            Route::post('tickets', [SupportController::class, 'create']);
            Route::get('tickets/{id}', [SupportController::class, 'show']);
            Route::post('tickets/{id}/messages', [SupportController::class, 'sendMessage']);
        });
    });

    // ─── Seller Routes ────────────────────────────────────
    // Applying for a seller account only needs a login; everything else needs an ACTIVE seller profile.
    Route::post('/seller/register', [SellerApiController::class, 'register'])
        ->middleware(['auth:api', 'check.blocked', 'throttle:5,1']);

    Route::prefix('/seller')->middleware(['auth:api', 'check.blocked', 'seller'])->group(function () {
        Route::get('dashboard', [SellerApiController::class, 'dashboard']);
        Route::get('orders', [SellerApiController::class, 'orders']);
        Route::get('orders/{id}', [SellerApiController::class, 'showOrder'])->whereNumber('id');
        Route::put('orders/{id}/status', [SellerApiController::class, 'updateOrderStatus']);
        Route::get('products', [SellerApiController::class, 'products']);
        Route::post('products', [SellerApiController::class, 'createProduct']);
        Route::get('products/{id}', [SellerApiController::class, 'showProduct']);
        Route::put('products/{id}', [SellerApiController::class, 'updateProduct']);
        Route::delete('products/{id}', [SellerApiController::class, 'deleteProduct']);
        Route::post('products/{id}/variants', [SellerApiController::class, 'addVariant']);
        Route::put('products/{id}/variants/{variantId}', [SellerApiController::class, 'updateVariant']);
        Route::delete('products/{id}/variants/{variantId}', [SellerApiController::class, 'deleteVariant']);
        Route::get('profile', [SellerApiController::class, 'profile']);
        Route::post('profile', [SellerApiController::class, 'updateProfile']);
        Route::post('products/{id}/images', [SellerApiController::class, 'addProductImages']);
        Route::delete('products/{id}/images/{imageId}', [SellerApiController::class, 'deleteProductImage']);
        Route::get('earnings', [SellerApiController::class, 'earnings']);
        Route::get('withdrawals', [SellerApiController::class, 'withdrawals']);
        Route::post('withdrawals', [SellerApiController::class, 'requestWithdrawal'])->middleware('throttle:10,1');
        Route::get('notifications', [SellerApiController::class, 'notifications']);
        Route::post('notifications/read', [SellerApiController::class, 'markNotificationsRead']);
    });

    Route::prefix('/blog')->controller(BlogController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/categories', 'categories');
        Route::get('/popular', 'popular');
        Route::get('/tags', 'tags');

        Route::get('/{slug}', 'show');

        Route::get('/{id}/comments', 'comments');
        Route::post('/{id}/comments', 'storeComment');

        Route::post('/{id}/like', 'toggleLike');
    });
});
