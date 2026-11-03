<?php
// Seller payouts/withdrawals/notifications, flash-sale pricing, enforced switches, seller coupons.
// usage (from backend/, on a freshly seeded DB): php tools/inproc_step6.php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Illuminate\Http\Request::create('http://127.0.0.1:8000/'));
$kernel->bootstrap();

use Illuminate\Support\Facades\{Http, Cache, DB};
use Illuminate\Http\Client\Factory;
use App\Models\{User, Order, Seller, Setting, SmsLog, Transaction, Product, FlashSale, FlashSaleProduct, Coupon, SellerWithdrawal};

function t($n, $c, $x = '') { echo ($c ? 'PASS ' : 'FAIL ') . $n . ($c ? '' : ' ' . json_encode($x)) . "\n"; }
function api($kernel, $m, $uri, $data = [], $tok = null) {
    $s = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
    if ($tok) $s['HTTP_AUTHORIZATION'] = "Bearer $tok";
    app("auth")->forgetGuards();
    $res = $kernel->handle(Illuminate\Http\Request::create($uri, $m, [], [], [], $s, json_encode($data)));
    return [$res->getStatusCode(), json_decode($res->getContent(), true)];
}
function put($k, $v) { Setting::set($k, $v); Cache::flush(); }
Http::swap(new Factory()); Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED']]], 200)]);

$cust = User::where('phone', '01711000001')->first();
$ctok = $cust->createToken('t')->accessToken;
$addr = \App\Models\Address::where('user_id', $cust->id)->value('id');
if (!$addr) {
    $addr = \App\Models\Address::create(['user_id' => $cust->id, 'name' => 'T', 'phone' => '01711000001', 'address_line' => 'x', 'division_id' => 1, 'district_id' => 1, 'type' => 'home'])->id;
}
$prod = fn($s) => Product::where('seller_id', $s)->where('is_active', true)->where('quantity', '>', 20)->first();
$p1 = $prod(1); $p2 = $prod(2);
$order = fn($items, $extra = []) => api($kernel, 'POST', '/api/v1/orders', array_merge(['address_id' => $addr, 'payment_method' => 'cod', 'items' => $items], $extra), $ctok);

// ---- flash sale price is what gets charged; limit enforced; cancel gives units back
$fs = FlashSale::create(['title' => 'T', 'title_bn' => 'T', 'discount_percentage' => 50, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'is_active' => true]);
$fsp = FlashSaleProduct::create(['flash_sale_id' => $fs->id, 'product_id' => $p1->id, 'discount_percentage' => 50, 'special_price' => 100, 'quantity_limit' => 3, 'sold_count' => 0]);
[$c, $d] = $order([['product_id' => $p1->id, 'quantity' => 2]]);
t('flash price (100) is charged instead of regular', $c == 201 && abs((float) $d['data']['subtotal'] - 200) < 0.01, [$c, $d['data']['subtotal'] ?? $d]);
t('sold_count counted', $fsp->fresh()->sold_count == 2);
[$c, $d2] = $order([['product_id' => $p1->id, 'quantity' => 2]]);
t('over the flash limit -> 422 with clear message', $c == 422 && str_contains($d2['message'], 'left'), [$c, $d2]);
$o = Order::find($d['data']['id']); $o->update(['cancelled_by' => 'user']); $o->updateStatus('cancelled', 't', 'user');
t('cancel returns the flash-sale units', $fsp->fresh()->sold_count == 0, $fsp->fresh()->sold_count);
[$c, $cart] = api($kernel, 'GET', '/api/v1/cart', [], $ctok);
FlashSale::whereKey($fs->id)->update(['is_active' => false]);
[$c, $d3] = $order([['product_id' => $p1->id, 'quantity' => 1]]);
t('sale ended -> regular price again', $c == 201 && abs((float) $d3['data']['subtotal'] - (float) $p1->price) < 0.01, $d3['data']['subtotal'] ?? $d3);

// ---- admin switches enforced on the server
put('enable_zinipay', '0');
[$c, $d] = $order([['product_id' => $p2->id, 'quantity' => 1]], ['payment_method' => 'zinipay']);
t('payment method switched off -> 422', $c == 422, [$c, $d]);
put('enable_zinipay', '1'); put('enable_express_shipping', '0');
[$c, $d] = $order([['product_id' => $p2->id, 'quantity' => 1]], ['delivery_type' => 'express']);
t('delivery option switched off -> 422', $c == 422, [$c, $d]);
put('enable_express_shipping', '1');

// ---- seller-specific coupon only discounts that seller's items
Coupon::where('code', 'S2ONLY')->delete();
Coupon::create(['code' => 'S2ONLY', 'title' => 't', 'type' => 'fixed', 'value' => 50, 'min_order_amount' => 0, 'is_active' => true, 'seller_id' => 2, 'usage_limit_per_user' => 0]);
[$c, $d] = $order([['product_id' => $p1->id, 'quantity' => 1]], ['coupon_code' => 'S2ONLY']);
t("coupon of seller 2 refused for a cart without seller 2's items", $c == 422, [$c, $d]);
[$c, $d] = $order([['product_id' => $p1->id, 'quantity' => 1], ['product_id' => $p2->id, 'quantity' => 1]], ['coupon_code' => 'S2ONLY']);
$rows = collect($d['data']['orders'] ?? [])->mapWithKeys(fn($x) => [$x['seller_id'] => Order::find($x['id'])]);
t('discount lands only on seller 2 order (50), seller 1 order has none', $c == 201 && abs((float) $rows[2]->coupon_discount - 50) < 0.01 && (float) $rows[1]->coupon_discount == 0, [$c, $rows->map->coupon_discount ?? null]);

// ---- payout credited once with ledger, notified, clawed back once
$ord = Order::find($d['data']['orders'][0]['id']); $sid = $ord->seller_id; $seller = Seller::find($sid);
$ord->update(['status' => 'shipped']); $b0 = (float) $seller->balance;
$ord->updateStatus('delivered', 't', 'system'); $ord->updateStatus('delivered', 't', 'system'); // repeated
$seller->refresh();
t('delivered credits payout exactly once', abs((float) $seller->balance - ($b0 + (float) $ord->seller_payout)) < 0.01, [$b0, $seller->balance, $ord->seller_payout]);
$led = Transaction::where('seller_id', $sid)->where('reference_type', 'order_earning')->where('reference_id', $ord->id)->get();
t('earning ledger row exists (once)', $led->count() == 1 && abs((float) $led[0]->balance_after - (float) $seller->balance) < 0.01);
$ord->refresh(); $ord->clawbackSeller('Return refunded'); $ord->clawbackSeller('again'); $seller->refresh();
t('refund claws payout back once', abs((float) $seller->balance - $b0) < 0.01 && Transaction::where('reference_type', 'order_payout_reversal')->where('reference_id', $ord->id)->count() == 1, $seller->balance);
$ord->updateStatus('returned', 't', 'admin'); $seller->refresh();
t("marking 'returned' afterwards does not reverse twice", abs((float) $seller->balance - $b0) < 0.01);
$su = $seller->user; 
t('seller got in-app notifications (new order + reversal)', $su->notifications()->where('type', 'like', '%SellerEvent%')->count() >= 1);

// ---- withdrawals
Seller::whereKey($sid)->update(['balance' => 5000, 'status' => 'active']); $stok = $su->createToken('s')->accessToken;
[$c, $d] = api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 100, 'method' => 'bkash', 'account' => '01711000000'], $stok);
t('below minimum -> 422', $c == 422, [$c, $d]);
[$c, $d] = api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 999999, 'method' => 'bkash', 'account' => '01711000000'], $stok);
t('more than balance -> 422', $c == 422, [$c, $d]);
[$c, $d] = api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 1000, 'method' => 'bkash', 'account' => '01711000000'], $stok);
t('valid request -> 201, amount held immediately', $c == 201 && abs((float) Seller::find($sid)->balance - 4000) < 0.01, [$c, $d]);
$w1 = SellerWithdrawal::find($d['data']['id']);
[$c, $dash] = api($kernel, 'GET', '/api/v1/seller/dashboard', [], $stok);
t('dashboard shows pending + minimum', $c == 200 && $dash['data']['pending_withdrawals'] == 1000 && $dash['data']['min_withdrawal'] == 500, $dash);
api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 600, 'method' => 'nagad', 'account' => '01811111111'], $stok);
api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 600, 'method' => 'bank', 'account' => 'DBBL 123'], $stok);
[$c, $d] = api($kernel, 'POST', '/api/v1/seller/withdrawals', ['amount' => 600, 'method' => 'bkash', 'account' => '01711000000'], $stok);
t('4th pending request refused', $c == 422, [$c, $d]);
$w2 = SellerWithdrawal::where('seller_id', $sid)->where('amount', 600)->first();
t('admin marks paid -> status/reference/ledger', $w1->markPaid('TRX123', 1) && $w1->fresh()->status == 'paid' && Transaction::where('reference_type', 'seller_withdrawal')->where('reference_id', $w1->id)->value('status') == 'completed');
t('paid twice is refused', !$w1->markPaid('TRX999', 1));
$bal = (float) Seller::find($sid)->balance;
t('admin rejects -> amount returned', $w2->reject('Wrong number', 1) && abs((float) Seller::find($sid)->balance - ($bal + 600)) < 0.01 && !$w2->reject('x', 1));
[$c, $n] = api($kernel, 'GET', '/api/v1/seller/notifications', [], $stok);
t('seller notifications list (paid + rejected)', $c == 200 && count(array_filter($n['data']['data'], fn($x) => str_starts_with($x['kind'], 'withdrawal'))) == 2, [$c, $n]);
api($kernel, 'POST', '/api/v1/seller/notifications/read', [], $stok);
[$c, $dash] = api($kernel, 'GET', '/api/v1/seller/dashboard', [], $stok);
t('mark read -> unread count 0', $dash['data']['unread_notifications'] == 0, $dash['data']['unread_notifications']);
t('seller SMS sent through the template', SmsLog::where('message', 'like', '%TRX123%')->exists());
