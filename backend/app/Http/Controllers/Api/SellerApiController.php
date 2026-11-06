<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Order, Product, ProductImage, Seller, Transaction};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{DB, Validator};

class SellerApiController extends Controller
{
    /** Statuses a seller may move an order to (admin handles the rest). */
    private const SELLER_STATUSES = ['confirmed', 'processing', 'shipped', 'cancelled'];

    /**
     * POST /api/v1/seller/register  (auth:api)
     * Creates a PENDING seller profile for the logged-in user; an admin approves it.
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->seller) {
            return $this->error('You have already applied for a seller account.', 422);
        }

        $v = Validator::make($request->all(), [
            'shop_name'      => 'required|string|max:100',
            'business_type'  => 'nullable|string|max:50',
            'category'       => 'nullable|string|max:50',
            'monthly_sales'  => 'nullable|string|max:20',
            'description'    => 'nullable|string|max:2000',
            'owner_name'     => 'required|string|max:100',
            'phone'          => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'email'          => 'nullable|email|max:150',
            'nid'            => 'nullable|string|max:30',
            'address'        => 'required|string|max:500',
            'payment_method' => 'required|in:bkash,nagad,bank',
            'mfs_number'     => 'required_if:payment_method,bkash,nagad|nullable|string|max:20',
            'bank_account'   => 'required_if:payment_method,bank|nullable|string|max:60',
            'bank_name'      => 'required_if:payment_method,bank|nullable|string|max:100',
            'agreed'         => 'accepted',
        ], [
            'phone.regex'     => 'Enter a valid Bangladeshi mobile number.',
            'agreed.accepted' => 'You must accept the seller terms.',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $seller = DB::transaction(function () use ($request, $user) {
            $slug = Str::slug($request->shop_name) ?: 'shop';
            if (Seller::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-' . Str::lower(Str::random(5));
            }

            return Seller::create([
                'user_id'       => $user->id,
                'shop_name'     => $request->shop_name,
                'slug'          => $slug,
                'description'   => $request->description,
                'phone'         => $request->phone,
                'email'         => $request->email,
                'address'       => $request->address,
                'nid_number'    => $request->nid,
                'bkash_number'  => in_array($request->payment_method, ['bkash', 'nagad']) ? $request->mfs_number : null,
                'bank_account'  => $request->payment_method === 'bank' ? $request->bank_account : null,
                'bank_name'     => $request->payment_method === 'bank' ? $request->bank_name : null,
                //'commission_rate' => (float) setting('platform_commission', 10),
                'commission_rate' => null, // blank = follow the global rate (Settings → Commission %)
                'status'        => 'pending',
                'application'   => $request->only([
                    'business_type', 'category', 'monthly_sales', 'owner_name', 'payment_method',
                ]),
            ]);
        });

        \App\Support\AdminAlert::send(
            'New seller application',
            "{$seller->shop_name} ({$seller->phone}) applied to become a seller",
            \App\Support\AdminAlert::url(fn() => \App\Filament\Resources\SellerResource::getUrl('view', ['record' => $seller], panel: 'admin')),
            'heroicon-o-building-storefront',
        );

        return $this->success(
            ['id' => $seller->id, 'status' => $seller->status],
            'Application submitted. We will review it shortly.',
            201
        );
    }

    public function dashboard(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;

        return $this->success([
            'shop_name'      => $seller->shop_name,
            'total_orders'   => Order::where('seller_id', $seller->id)->count(),
            'pending_orders' => Order::where('seller_id', $seller->id)->where('status', 'pending')->count(),
            'total_revenue'  => Order::where('seller_id', $seller->id)->where('status', 'delivered')->sum('seller_payout'),
            'balance'        => $seller->balance,
            'total_products' => Product::where('seller_id', $seller->id)->where('is_active', true)->count(),
            'pending_products' => Product::where('seller_id', $seller->id)->where('is_active', false)->count(),
            'followers'      => $seller->followers()->count(),
            'pending_withdrawals' => (float) $seller->withdrawals()->where('status', 'pending')->sum('amount'),
            'min_withdrawal' => (float) setting('seller_min_withdrawal', 500),
            'unread_notifications' => $request->user()->unreadNotifications()->where('type', 'like', '%SellerEvent%')->count(),
            'payout_methods' => $this->payoutMethods($seller),
            'rating'         => $seller->rating,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        $orders = Order::where('seller_id', $seller->id)->with('items', 'user:id,name,phone')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')->paginate(15);
        return $this->success($orders);
    }

    /** GET /seller/orders/{id} – full details of one of the seller's own orders. */
    public function showOrder(Request $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        $order  = Order::where('id', $id)->where('seller_id', $seller->id)
            ->with(['items', 'user:id,name,phone', 'statusHistories'])->firstOrFail();
        return $this->success($order);
    }

    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $seller = $request->user()->seller;
        $order  = Order::where('id', $id)->where('seller_id', $seller->id)->firstOrFail();

        $v = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', self::SELLER_STATUSES),
            'note'   => 'nullable|string|max:300',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        // 'delivered' is set by the courier/admin (it credits the seller balance), never by the seller.
        if (in_array($order->status, ['delivered', 'cancelled', 'returned'], true)) {
            return $this->error('This order can no longer be changed.', 422);
        }
        if ($request->status === 'cancelled') {
            $order->update(['cancelled_by' => 'seller', 'cancellation_reason' => $request->note ?: 'Cancelled by seller']);
        }

        $order->updateStatus($request->status, $request->note ?? '', 'seller', $seller->id);
        return $this->success([], 'Status updated.');
    }

    public function products(Request $request): JsonResponse
    {
        $seller   = $request->user()->seller;
        $products = Product::where('seller_id', $seller->id)->with('primaryImage', 'images', 'variants', 'category:id,name')
            ->latest()->paginate(20);
        return $this->success($products);
    }

    public function createProduct(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;

        // Variants may be sent as a JSON string (multipart form) or as a real array.
        if (is_string($request->input('variants'))) {
            $decoded = json_decode($request->input('variants'), true);
            $request->merge(['variants' => is_array($decoded) ? $decoded : []]);
        }

        $v = Validator::make($request->all(), [
            'name'              => 'required|string|max:200',
            'category_id'       => 'required|exists:categories,id',
            'price'             => 'required|numeric|min:1',
            'compare_price'     => 'nullable|numeric|gte:price',

            // with variants the stock is the sum of the variants, so the plain quantity becomes optional
            'quantity'          => 'required_without:variants|nullable|integer|min:0',
            'variants'                                => 'nullable|array|max:50',
            'variants.*.sku'                          => 'nullable|string|max:60',
            'variants.*.price'                        => 'required|numeric|min:1',
            'variants.*.compare_price'                => 'nullable|numeric|min:0',
            'variants.*.quantity'                     => 'required|integer|min:0',
            'variants.*.is_active'                    => 'sometimes|boolean',
            'variants.*.attribute_values'             => 'required|array|min:1|max:5',
            'variants.*.attribute_values.*.attribute' => 'required|string|max:40',
            'variants.*.attribute_values.*.value'     => 'required|string|max:60',

            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string|max:10000',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'gallery'           => 'nullable|array|max:8',
            'gallery.*'         => 'image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $product = DB::transaction(function () use ($request, $seller) {
            $product = Product::create([
                // Whitelisted fields only: a seller must never set is_featured, seller_id, etc.
                'seller_id'         => $seller->id,
                'category_id'       => $request->category_id,
                'name'              => $request->name,
                'slug'              => Str::slug($request->name) . '-' . Str::lower(Str::random(6)),
                'price'             => $request->price,
                'compare_price'     => $request->compare_price,
                'quantity'          => (int) $request->input('quantity', 0),
                'short_description' => $request->short_description,
                'description'       => $request->description,
                // New seller products wait for admin review before going live.
                'is_active'         => (bool) setting('seller_products_auto_approve', false),
            ]);

            // Featured image (primary) + gallery images. If only a gallery is sent, its first image is the primary.
            $files = [];
            if ($request->hasFile('image')) $files[] = $request->file('image');
            foreach ((array) $request->file('gallery', []) as $g) $files[] = $g;
            foreach ($files as $i => $f) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $f->store('products', 'public'),
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                ]);
            }

            // Variants (size / colour …) sent together with the product
            foreach ((array) $request->input('variants', []) as $row) {
                $product->variants()->create([
                    'sku'              => $row['sku'] ?? null,
                    'price'            => $row['price'],
                    'compare_price'    => !empty($row['compare_price']) ? $row['compare_price'] : null,
                    'quantity'         => (int) $row['quantity'],
                    'is_active'        => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'attribute_values' => array_values(array_map(fn($a) => ['attribute' => $a['attribute'], 'value' => $a['value']], $row['attribute_values'])),
                ]);
            }
            $this->syncVariantState($product);   // has_variants + stock = sum of active variants

            return $product;
        });

        \App\Support\AdminAlert::send(
            $product->is_active ? 'New seller product' : 'Seller product waiting for approval',
            "{$seller->shop_name}: {$product->name} (৳{$product->price})",
            \App\Support\AdminAlert::url(fn() => \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $product], panel: 'admin')),
            'heroicon-o-shopping-bag',
        );

        return $this->success($product, 'Product submitted' . ($product->is_active ? '.' : ' for review.'), 201);
    }

    /** GET /seller/products/{id} – one product with images and variants (edit form). */
    public function showProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)
            ->with('images', 'variants', 'category:id,name')->firstOrFail();
        return $this->success($product);
    }

    /** DELETE /seller/products/{id} – soft delete (order history keeps its product name/price snapshot). */
    public function deleteProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $product->delete();
        return $this->success([], 'Product deleted.');
    }

    // ── Variants ─────────────────────────────────────────────────────────
    private function variantRules(): array
    {
        return [
            'sku'                        => 'nullable|string|max:60',
            'price'                      => 'required|numeric|min:1',
            'compare_price'              => 'nullable|numeric|gte:price',
            'quantity'                   => 'required|integer|min:0',
            'is_active'                  => 'sometimes|boolean',
            'attribute_values'           => 'required|array|min:1|max:5',
            'attribute_values.*.attribute' => 'required|string|max:40',
            'attribute_values.*.value'     => 'required|string|max:60',
        ];
    }

    /** Keeps products.has_variants / quantity consistent with the variants. */
    private function syncVariantState(Product $product): void
    {
        $count = $product->variants()->count();
        $data = ['has_variants' => $count > 0];
        if ($count > 0) $data['quantity'] = (int) $product->variants()->where('is_active', true)->sum('quantity');
        $product->update($data);
    }

    /** POST /seller/products/{id}/variants */
    public function addVariant(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $v = Validator::make($request->all(), $this->variantRules());
        if ($v->fails()) return $this->error($v->errors(), 422);
        if ($product->variants()->count() >= 50) return $this->error('A product can have at most 50 variants.', 422);

        $variant = $product->variants()->create([
            'sku' => $request->sku, 'price' => $request->price, 'compare_price' => $request->compare_price,
            'quantity' => $request->quantity, 'is_active' => $request->boolean('is_active', true),
            'attribute_values' => array_values(array_map(fn($a) => ['attribute' => $a['attribute'], 'value' => $a['value']], $request->attribute_values)),
        ]);
        $this->syncVariantState($product);
        return $this->success($variant, 'Variant added.', 201);
    }

    /** PUT /seller/products/{id}/variants/{variantId} */
    public function updateVariant(Request $request, int $id, int $variantId): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $variant = $product->variants()->whereKey($variantId)->firstOrFail();
        $v = Validator::make($request->all(), $this->variantRules());
        if ($v->fails()) return $this->error($v->errors(), 422);

        $variant->update([
            'sku' => $request->sku, 'price' => $request->price, 'compare_price' => $request->compare_price,
            'quantity' => $request->quantity, 'is_active' => $request->boolean('is_active', true),
            'attribute_values' => array_values(array_map(fn($a) => ['attribute' => $a['attribute'], 'value' => $a['value']], $request->attribute_values)),
        ]);
        $this->syncVariantState($product);
        return $this->success($variant->fresh(), 'Variant updated.');
    }

    /** DELETE /seller/products/{id}/variants/{variantId} */
    public function deleteVariant(Request $request, int $id, int $variantId): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $product->variants()->whereKey($variantId)->firstOrFail()->delete();
        $this->syncVariantState($product);
        return $this->success([], 'Variant removed.');
    }

    // ── Shop profile ─────────────────────────────────────────────────────
    private function profilePayload(Seller $s): array
    {
        return [
            'shop_name' => $s->shop_name, 'shop_name_bn' => $s->shop_name_bn, 'slug' => $s->slug, 'description' => $s->description,
            'phone' => $s->phone, 'email' => $s->email, 'address' => $s->address,
            'bkash_number' => $s->bkash_number, 'bank_name' => $s->bank_name, 'bank_account' => $s->bank_account,
            'logo' => $s->logo ? $s->logo_url : null, 'banner' => $s->banner ? (str_starts_with($s->banner, 'http') ? $s->banner : storage_url($s->banner)) : null,
            'status' => $s->status, 'is_verified' => (bool) $s->is_verified,
        ];
    }

    /** GET /seller/profile */
    public function profile(Request $request): JsonResponse
    {
        return $this->success($this->profilePayload($request->user()->seller));
    }

    /** POST /seller/profile (multipart, so logo/banner can ride along). The shop name, slug and commission are admin-controlled. */
    public function updateProfile(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        $v = Validator::make($request->all(), [
            'shop_name_bn' => 'nullable|string|max:120',
            'description'  => 'nullable|string|max:2000',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:120',
            'address'      => 'nullable|string|max:300',
            'bkash_number' => 'nullable|string|max:20',
            'bank_name'    => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:100',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'banner'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $data = $request->only('shop_name_bn', 'description', 'phone', 'email', 'address', 'bkash_number', 'bank_name', 'bank_account');
        foreach (['logo' => 'sellers/logos', 'banner' => 'sellers/banners'] as $field => $dir) {
            if ($request->hasFile($field)) {
                if ($seller->{$field} && !str_starts_with((string) $seller->{$field}, 'http')) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($seller->{$field});
                }
                $data[$field] = $request->file($field)->store($dir, 'public');
            }
        }
        $seller->update($data);
        return $this->success($this->profilePayload($seller->fresh()), 'Shop profile updated.');
    }

    /** POST /seller/products/{id}/images – add gallery images (max 8 per product in total). */
    public function addProductImages(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $v = Validator::make($request->all(), [
            'gallery'   => 'required|array|min:1|max:8',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $have = $product->images()->count();
        $new  = (array) $request->file('gallery', []);
        if ($have + count($new) > 8) return $this->error('A product can have at most 8 images.', 422);

        foreach ($new as $i => $f) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $f->store('products', 'public'),
                'sort_order' => $have + $i,
                'is_primary' => $have === 0 && $i === 0,
            ]);
        }
        return $this->success($product->load('images'), 'Images added.');
    }

    /** DELETE /seller/products/{id}/images/{imageId} */
    public function deleteProductImage(Request $request, int $id, int $imageId): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->seller->id)->firstOrFail();
        $img = $product->images()->whereKey($imageId)->firstOrFail();
        $wasPrimary = $img->is_primary;
        \Illuminate\Support\Facades\Storage::disk('public')->delete($img->image_path);
        $img->delete();
        if ($wasPrimary && ($next = $product->images()->orderBy('sort_order')->first())) {
            $next->update(['is_primary' => true]);
        }
        return $this->success($product->load('images'), 'Image removed.');
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $seller  = $request->user()->seller;
        $product = Product::where('id', $id)->where('seller_id', $seller->id)->firstOrFail();

        $v = Validator::make($request->all(), [
            'name'              => 'sometimes|string|max:200',
            'category_id'       => 'sometimes|exists:categories,id',
            'price'             => 'sometimes|numeric|min:1',
            'compare_price'     => 'sometimes|nullable|numeric|gte:price',
            'quantity'          => 'sometimes|integer|min:0',
            'short_description' => 'sometimes|nullable|string|max:500',
            'description'       => 'sometimes|nullable|string|max:10000',
            'unit'              => 'sometimes|nullable|string|max:30',
            'weight'            => 'sometimes|nullable|numeric|min:0',
            'is_active'         => 'sometimes|boolean',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $data = $request->only('name', 'category_id', 'price', 'compare_price', 'quantity', 'short_description', 'description', 'unit', 'weight');
        // With variants the stock is the sum of the variants, not a separate number.
        if ($product->variants()->exists()) unset($data['quantity']);
        // A seller can hide a live product, but cannot self-approve a product still under review.
        if ($request->has('is_active') && (!$request->boolean('is_active') || $product->is_active)) {
            $data['is_active'] = $request->boolean('is_active');
        }
        $product->update($data);

        return $this->success($product, 'Updated.');
    }

    public function earnings(Request $request): JsonResponse
    {
        $seller       = $request->user()->seller;
        $transactions = Transaction::where('seller_id', $seller->id)->latest()->paginate(20);
        return $this->success(['balance' => $seller->balance, 'transactions' => $transactions]);
    }

    /** Where the seller can be paid: taken from the application / profile. */
    private function payoutMethods(Seller $seller): array
    {
        $app = $seller->application ?? [];
        $out = [];
        $mfs = $seller->bkash_number ?: ($app['mfs_number'] ?? null);
        $pm  = $app['payment_method'] ?? 'bkash';
        if ($mfs) {
            $out[] = ['method' => in_array($pm, ['bkash', 'nagad'], true) ? $pm : 'bkash', 'account' => $mfs];
        }
        if ($seller->bank_account) {
            $out[] = ['method' => 'bank', 'account' => trim(($seller->bank_name ? $seller->bank_name . ' / ' : '') . $seller->bank_account)];
        }
        return $out;
    }

    /** GET /seller/withdrawals */
    public function withdrawals(Request $request): JsonResponse
    {
        $seller = $request->user()->seller;
        return $this->success($seller->withdrawals()->latest()->paginate(15));
    }

    /** POST /seller/withdrawals – the amount is held (deducted) immediately; admin pays or rejects (rejection returns it). */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $min = (float) setting('seller_min_withdrawal', 500);
        $v = Validator::make($request->all(), [
            'amount'  => 'required|numeric|min:' . $min,
            'method'  => 'required|in:bkash,nagad,bank',
            'account' => 'required|string|max:200',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        try {
            $w = DB::transaction(function () use ($request) {
                $seller = Seller::whereKey($request->user()->seller->id)->lockForUpdate()->first();
                $amount = round((float) $request->amount, 2);

                if ($seller->status !== 'active') {
                    throw new \DomainException('Your seller account is not active.');
                }
                if ($seller->withdrawals()->where('status', 'pending')->count() >= 3) {
                    throw new \DomainException('You already have 3 pending withdrawal requests.');
                }
                if ($amount > (float) $seller->balance) {
                    throw new \DomainException('Amount is more than your available balance.');
                }

                $before = (float) $seller->balance;
                $seller->decrement('balance', $amount);

                $w = $seller->withdrawals()->create([
                    'amount' => $amount, 'method' => $request->method, 'account' => $request->account, 'status' => 'pending',
                ]);

                Transaction::create([
                    'seller_id'      => $seller->id,
                    'reference_id'   => $w->id,
                    'reference_type' => 'seller_withdrawal',
                    'type'           => 'withdrawal',
                    'amount'         => $amount,
                    'balance_before' => $before,
                    'balance_after'  => $before - $amount,
                    'description'    => "Withdrawal request #{$w->id} ({$request->method})",
                    'payment_method' => $request->method,
                    'status'         => 'pending',
                ]);

                return $w;
            });
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 422);
        }

        \App\Support\AdminAlert::send(
            'Seller withdrawal request',
            "{$request->user()->seller->shop_name} requested ৳" . number_format((float) $w->amount, 2) . " via {$w->method}",
            \App\Support\AdminAlert::url(fn() => \App\Filament\Resources\SellerWithdrawalResource::getUrl('index', panel: 'admin')),
            'heroicon-o-banknotes',
        );

        return $this->success($w, 'Withdrawal request submitted.', 201);
    }

    /** GET /seller/notifications */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $user->notifications()->where('type', 'like', '%SellerEvent%')->latest()->paginate(20)
            ->through(fn($n) => ['id' => $n->id, 'title' => $n->data['title'] ?? '', 'body' => $n->data['body'] ?? '', 'kind' => $n->data['kind'] ?? '', 'order_id' => $n->data['order_id'] ?? null, 'read' => (bool) $n->read_at, 'created_at' => $n->created_at]);
        return $this->success($items);
    }

    /** POST /seller/notifications/read */
    public function markNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->where('type', 'like', '%SellerEvent%')->update(['read_at' => now()]);
        return $this->success([], 'Marked as read.');
    }
}
