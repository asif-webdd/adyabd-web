<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\DeliveryCharge;
use App\Models\Coupon;
use App\Models\CourierMerchant;
use App\Models\Order;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\Seller;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BlockingService;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items' => fn($q) => $q->with('product:id,slug'), 'courierMerchant'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')->paginate(10);
        return $this->success($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $block = app(BlockingService::class)->checkRequest($request, $user->id);
        if ($block) return $this->error($block['message'], 403);

        $validation = Validator::make($request->all(), [
            'address_id'         => 'required|exists:addresses,id',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'delivery_type'      => 'nullable|in:standard,express,same_day',
            'payment_method'     => 'required|in:cod,zinipay,wallet',
            'coupon_code'        => 'nullable|string',
            'customer_note'      => 'nullable|string|max:500'
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        // Admin → Settings switches are enforced here too (the checkout page only hides the options).
        $payFlag = ['cod' => 'enable_cod', 'zinipay' => 'enable_zinipay', 'wallet' => 'enable_wallet'][$request->payment_method];
        if (!setting($payFlag, true)) return $this->error('This payment method is currently unavailable.', 422);
        $shipFlag = ['standard' => 'enable_standard_shipping', 'express' => 'enable_express_shipping', 'same_day' => 'enable_same_day_shipping'][$request->delivery_type ?? 'standard'];
        if (!setting($shipFlag, true)) return $this->error('This delivery option is currently unavailable.', 422);

        try {
            $order = DB::transaction(function () use ($request, $user) {
                $address = Address::where('id', $request->address_id)
                    ->where('user_id', $user->id)
                    ->with(['division', 'district', 'upazila'])
                    ->firstOrFail();

                // Merge duplicate lines so the stock check sees the real total per product/variant.
                $lines = [];
                foreach ($request->items as $item) {
                    $key = $item['product_id'] . ':' . ($item['variant_id'] ?? 0);
                    $lines[$key] = [
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity'   => ($lines[$key]['quantity'] ?? 0) + (int) $item['quantity'],
                    ];
                }

                $subtotal  = 0;
                $itemsData = [];
                $sellers   = []; // seller_id => Seller (cache for status / commission)

                foreach ($lines as $line) {
                    // Row locks: two buyers can no longer both take the last unit.
                    $product = Product::whereKey($line['product_id'])->lockForUpdate()->firstOrFail();
                    if (!$product->is_active) {
                        throw new \DomainException("Product '{$product->name}' is not available.");
                    }

                    $variant = null;
                    if ($line['variant_id']) {
                        $variant = ProductVariant::whereKey($line['variant_id'])
                            ->where('product_id', $product->id)
                            ->lockForUpdate()->first();
                        if (!$variant) {
                            throw new \DomainException("Selected option of '{$product->name}' is not available.");
                        }
                    }

                    $price = $variant ? $variant->price : $product->price;
                    $stock = $variant ? $variant->quantity : $product->quantity;

                    // Running flash sale: the sale price is what is charged (and the sale units are counted).
                    $flash = FlashSaleProduct::activeFor($product->id, true);
                    if ($flash) {
                        $left = $flash->remaining();
                        if ($left !== null && $left < $line['quantity']) {
                            throw new \DomainException($left > 0
                                ? "Only {$left} unit(s) of '{$product->name}' are left at the flash-sale price. Please reduce the quantity."
                                : "The flash sale for '{$product->name}' has sold out.");
                        }
                        $price = $flash->priceFor($variant, (float) $price);
                        $flash->increment('sold_count', $line['quantity']);
                    }
                    if ($stock < $line['quantity']) {
                        throw new \DomainException("Product '{$product->name}' has insufficient stock.");
                    }

                    if ($product->seller_id) {
                        $sellers[$product->seller_id] ??= Seller::find($product->seller_id);
                        if (($sellers[$product->seller_id]->status ?? null) !== 'active') {
                            throw new \DomainException("Product '{$product->name}' is not available.");
                        }
                    }

                    $total     = $price * $line['quantity'];
                    $subtotal += $total;
                    $itemsData[] = [
                        '_seller_id'      => $product->seller_id,
                        'flash_sale_product_id' => $flash?->id,
                        'user_id'         => $user->id,
                        'product_id'      => $product->id,
                        'variant_id'      => $variant?->id,
                        'product_name'    => $product->name,
                        'product_image'   => $product->thumbnail,
                        'variant_options' => $variant?->attribute_values,
                        'quantity'        => $line['quantity'],
                        'unit_price'      => $price,
                        'compare_price'   => $variant?->compare_price ?? $product->compare_price,
                        'total_price'     => $total,
                    ];

                    $variant ? $variant->decrement('quantity', $line['quantity']) : $product->decrement('quantity', $line['quantity']);
                }

                // ── Coupon (validated, not silently ignored) ──────────────────────
                $couponDiscount = 0;
                $coupon = null;
                $eligibleSubtotal = 0.0;
                if ($request->filled('coupon_code')) {
                    $coupon = Coupon::where('code', $request->coupon_code)->lockForUpdate()->first();
                    if (!$coupon || !$coupon->isValid()) {
                        throw new \DomainException('This coupon is invalid or has expired.');
                    }
                    if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
                        throw new \DomainException('This coupon is not active yet.');
                    }
                    if ($coupon->is_for_new_users && $user->total_orders > 0) {
                        throw new \DomainException('This coupon is only for new customers.');
                    }
                    if ($coupon->usage_limit_per_user > 0) {
                        $usedByUser = Order::where('user_id', $user->id)->where('coupon_id', $coupon->id)
                            ->whereNotIn('status', ['cancelled'])
                            ->selectRaw('COUNT(DISTINCT COALESCE(checkout_group, id)) as c')->value('c');
                        if ($usedByUser >= $coupon->usage_limit_per_user) {
                            throw new \DomainException('You have already used this coupon the maximum number of times.');
                        }
                    }
                    // A coupon created for one seller only applies to that seller's products.
                    $eligibleSubtotal = $coupon->seller_id
                        ? (float) collect($itemsData)->where('_seller_id', $coupon->seller_id)->sum('total_price')
                        : (float) $subtotal;
                    if ($coupon->seller_id && $eligibleSubtotal <= 0) {
                        throw new \DomainException('This coupon is only valid for products of a specific seller.');
                    }
                    $couponDiscount = $coupon->calculateDiscount($eligibleSubtotal);
                    if ($couponDiscount <= 0) {
                        throw new \DomainException('This coupon does not apply to your order (minimum amount not reached).');
                    }
                    $couponDiscount = min($couponDiscount, $eligibleSubtotal);
                    $coupon->increment('used_count');
                }

                $deliveryType = $request->delivery_type ?? 'standard';
                $freeMinOrder = (float) setting('free_shipping_min_order', 500);
                $globalRate   = (float) setting('platform_commission', 10);

                // One order per seller (platform-owned products = group 0). Each is a separate
                // shipment, so each gets its own delivery charge, commission and payout.
                $groups  = collect($itemsData)->groupBy(fn($i) => (int) ($i['_seller_id'] ?? 0));
                $groupId = $groups->count() > 1 ? (string) \Illuminate\Support\Str::uuid() : null;

                $plans = [];
                $allocated = 0.0;
                $lastKey = $groups->keys()->last();
                foreach ($groups as $sellerKey => $rows) {
                    $gSub = (float) $rows->sum('total_price');
                    // Coupon discount is shared between the orders in proportion to their value.
                    $eligible = !$coupon?->seller_id || (int) $coupon->seller_id === (int) $sellerKey;
                    if (!$eligible) {
                        $gDisc = 0.0;
                    } else {
                        $base  = $coupon?->seller_id ? $eligibleSubtotal : $subtotal;
                        $isLastEligible = $coupon?->seller_id ? true : ($sellerKey === $lastKey);
                        $gDisc = $isLastEligible
                            ? round($couponDiscount - $allocated, 2)
                            : round($couponDiscount * $gSub / max($base, 0.01), 2);
                        $allocated += $gDisc;
                    }

                    $gShip = match ($deliveryType) {
                        'express'  => (float) setting('express_shipping_charge', 120),
                        'same_day' => (float) setting('same_day_shipping_charge', 150),
                        default    => ($gSub >= $freeMinOrder && $freeMinOrder > 0)
                            ? 0.0
                            : DeliveryCharge::resolveCharge(
                                $address->district?->name,
                                $address->division?->name,
                                $address->upazila?->name,
                            ),
                    };

                    $net = $gSub - $gDisc;
                    $rate = $sellerKey ? (float) ($sellers[$sellerKey]->commission_rate ?? $globalRate) : 0.0;
                    $commission = $sellerKey ? round($net * $rate / 100, 2) : 0.0;

                    $plans[] = [
                        'seller_id'  => $sellerKey ?: null,
                        'rows'       => $rows,
                        'subtotal'   => $gSub,
                        'discount'   => $gDisc,
                        'shipping'   => $gShip,
                        'total'      => $net + $gShip,
                        'commission' => $commission,
                        'payout'     => $sellerKey ? $net - $commission : 0.0,
                    ];
                }
                $totalAmount = (float) collect($plans)->sum('total');

                // ── Wallet payment: locked read, ONE debit, one ledger entry per order ──
                $walletBefore = null;
                if ($request->payment_method === 'wallet') {
                    $wallet = User::whereKey($user->id)->lockForUpdate()->first();
                    $walletBefore = (float) $wallet->wallet_balance;
                    if ($walletBefore < $totalAmount) {
                        throw new \DomainException('Insufficient wallet balance.');
                    }
                    $wallet->decrement('wallet_balance', $totalAmount);
                }

                $createdOrders = [];
                $running = $walletBefore;
                foreach ($plans as $plan) {
                    $order = Order::create([
                        'user_id'              => $user->id,
                        'checkout_group'       => $groupId,
                        'seller_id'            => $plan['seller_id'],
                        'coupon_id'            => $coupon?->id,
                        'coupon_code'          => $coupon?->code,
                        'payment_method'       => $request->payment_method,
                        'payment_status'       => $request->payment_method === 'wallet' ? 'paid' : 'pending',
                        'subtotal'             => $plan['subtotal'],
                        'coupon_discount'      => $plan['discount'],
                        'shipping_charge'      => $plan['shipping'],
                        'total_amount'         => $plan['total'],
                        'commission_amount'    => $plan['commission'],
                        'seller_payout'        => $plan['payout'],
                        'shipping_name'        => $address->name,
                        'shipping_phone'       => $address->phone,
                        'shipping_address'     => $address->address_line,
                        'shipping_division'    => $address->division?->name,
                        'shipping_district'    => $address->district?->name,
                        'shipping_upazila'     => $address->upazila?->name,
                        'shipping_postal_code' => $address->postal_code,
                        'delivery_type'        => $deliveryType,
                        'customer_note'        => $request->customer_note,
                        'user_ip'              => $request->ip(),
                        'user_device'          => $request->header('X-Device-Type', 'web'),
                    ]);

                    foreach ($plan['rows'] as $item) {
                        unset($item['_seller_id']);
                        $order->items()->create($item);
                    }

                    if ($running !== null) {
                        Transaction::create([
                            'user_id'        => $user->id,
                            'reference_id'   => $order->id,
                            'reference_type' => 'order_payment',
                            'type'           => 'debit',
                            'amount'         => $plan['total'],
                            'balance_before' => $running,
                            'balance_after'  => $running - $plan['total'],
                            'description'    => "Order #{$order->order_number} paid from wallet",
                            'payment_method' => 'wallet',
                            'status'         => 'completed',
                        ]);
                        $running -= $plan['total'];
                    }

                    $order->updateStatus('pending', 'Order received', 'system');
                    $createdOrders[] = $order;
                }

                $user->increment('total_orders');

                $order = $createdOrders;
                return $order;
            });
        } catch (\DomainException $e) {
            // Business-rule failure: the transaction is rolled back automatically.
            return $this->error($e->getMessage(), 422);
        } catch (Exception $e) {
            report($e);
            return $this->error('Failed to place order. Please try again.', 500);
        }

        // ── After commit: slow / external work must never undo a placed order ──
        $orders = $order; // list: one order per seller
        foreach ($orders as $o) {
            try {
                $o->autoConfirmIfEnabled();
                if ($request->payment_method === 'cod' && setting('auto_book_courier', false)) {
                    $merchant = CourierMerchant::where('is_default', true)->where('is_active', true)->first();
                    if ($merchant) $o->bookCourier($merchant->id);
                }
                app(SmsService::class)->sendOrderPlaced($o);
                $o->emailCustomer('placed');
                $o->notifySeller('new');
            } catch (Exception $e) {
                report($e);
            }
        }
        Cart::where('user_id', $user->id)->delete();


        // ── Server-side tracking (Purchase): response পাঠানোর পরে চলে ──
        // ZiniPay বাদ: ওই অর্ডারে ক্রেতা পেমেন্ট পেজে চলে যায়, পেমেন্ট নিশ্চিত হওয়ার আগে Purchase পাঠানো ঠিক না।
        if ($request->payment_method !== 'zinipay') {
            try {
                $tracking = app(\App\Services\TrackingService::class);
                $context  = $tracking->contextFromRequest($request);
                $purchase = $tracking->purchaseFromOrders($orders, $user, $context);

                try {
                    \App\Models\Order::whereIn('id', collect($orders)->pluck('id'))
                        ->update(['tracking_context' => json_encode($context)]);
                } catch (\Throwable $e) {
                    report($e);
                }

                dispatch(function () use ($purchase) {
                    app(\App\Services\TrackingService::class)->sendPurchase($purchase);
                })->afterResponse();
            } catch (\Throwable $e) {
                report($e);
            }
        }


        // The first order keeps the old response shape; extra fields describe the whole checkout.
        $data = $orders[0]->fresh()->load('items', 'statusHistories')->toArray();
        $data['order_numbers'] = collect($orders)->pluck('order_number')->all();
        $data['orders']        = collect($orders)->map(fn($o) => ['id' => $o->id, 'order_number' => $o->order_number, 'total_amount' => (float) $o->total_amount, 'seller_id' => $o->seller_id])->all();
        $data['grand_total']   = (float) collect($orders)->sum('total_amount');

        return $this->success($data, count($orders) > 1
            ? 'Order placed successfully! Items from different sellers were split into ' . count($orders) . ' orders.'
            : 'Order placed successfully!', 201);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->where('user_id', $request->user()->id)->with(['items.product', 'statusHistories', 'courierMerchant'])->firstOrFail();
        return $this->success($order);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), ['reason' => 'required|string|max:500']);
        if ($v->fails()) return $this->error($v->errors(), 422);

        try {
            $order = DB::transaction(function () use ($request, $id) {
                // Lock so a double-click / parallel request cannot cancel (and refund) twice.
                $order = Order::where('id', $id)->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
                if (!$order->canBeCancelled()) {
                    throw new \DomainException('This order cannot be cancelled.');
                }
                $order->update(['cancellation_reason' => $request->reason, 'cancelled_by' => 'user']);
                // Stock, coupon usage and wallet refund are handled by Order::handleCancelled().
                $order->updateStatus('cancelled', $request->reason, 'user', $request->user()->id);
                return $order;
            });
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 422);
        }

        app(BlockingService::class)->handleOrderCancellation($order);
        return $this->success([], 'Order cancelled.');
    }

    public function track(Request $request, mixed $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->where('user_id', $request->user()->id)->with(['statusHistories', 'courierMerchant', 'items'])->first();
        if (!$order) return $this->error('Order not found.', 404);
        return $this->success([
            'order'            => $order,
            'tracking_id'      => $order->courier_tracking_id,
            'tracking_url'     => $order->courier_tracking_url,
            'courier'          => $order->courierMerchant?->name,
            'status_histories' => $order->statusHistories
        ]);
    }

    public function requestReturn(Request $request, int $id): JsonResponse
    {
        $order = Order::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        if ($order->status !== 'delivered') return $this->error('Only delivered orders can be returned.', 422);
        if ($order->delivered_at && $order->delivered_at->lt(now()->subDays(7))) {
            return $this->error('The 7-day return window has passed.', 422);
        }

        $v = Validator::make($request->all(), ['reason' => 'required|string|max:255']);
        if ($v->fails()) return $this->error($v->errors(), 422);

        if (\App\Models\ReturnRequest::where('order_id', $order->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            return $this->error('A return request for this order is already open.', 422);
        }

        // Creates a request for admin review (same flow as /returns); the order stays "delivered" until approved.
        \App\Models\ReturnRequest::create([
            'user_id'       => $request->user()->id,
            'order_id'      => $order->id,
            'type'          => 'return',
            'reason'        => $request->reason,
            'description'   => $request->reason,
            'refund_method' => 'wallet',
            'status'        => 'pending',
        ]);
        return $this->success([], 'Return request submitted.');
    }

    public function invoice(Request $request, int $id): JsonResponse
    {
        $order = Order::where('id', $id)->where('user_id', $request->user()->id)->with(['items', 'user'])->firstOrFail();
        $order->setAttribute('invoice_url', \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'invoice', now()->addMinutes(30), ['order' => $order->order_number]
        ));
        return $this->success($order);
    }
}
