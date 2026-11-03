<?php
// In-process checks for payments of split orders, SMS templates/toggles, referral, reviews, auto-block switch.
// usage (from backend/): php tools/inproc_step5.php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Illuminate\Http\Request::create('http://127.0.0.1:8000/'));
$kernel->bootstrap();

use Illuminate\Support\Facades\{Http, Cache};
use App\Models\{User, Order, Seller, Setting, SmsLog, Transaction, NotificationTemplate, Review, OrderItem, AutoBlockRule};
use Illuminate\Http\Client\Factory;

function t($n, $c, $x = '') { echo ($c ? 'PASS ' : 'FAIL ') . $n . ($c ? '' : ' ' . json_encode($x)) . "\n"; }
function api($kernel, $m, $uri, $data = [], $tok = null) {
    $s = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
    if ($tok) $s['HTTP_AUTHORIZATION'] = "Bearer $tok";
    $r = Illuminate\Http\Request::create($uri, $m, [], [], [], $s, json_encode($data));
    $res = $kernel->handle($r);
    return [$res->getStatusCode(), json_decode($res->getContent(), true)];
}
function put($k, $v) { Setting::set($k, $v); Cache::flush(); }

$user = User::where('phone', '01711000001')->first();
$tok  = $user->createToken('t')->accessToken;
$addr = $user->addresses()->first()->id ?? \App\Models\Address::where('user_id', $user->id)->value('id');
$prod = fn($s) => \App\Models\Product::where('seller_id', $s)->where('is_active', true)->where('quantity', '>', 10)->first();
$p1 = $prod(1); $p2 = $prod(2);

// ---------- ZiniPay: one invoice for the whole checkout
put('zinipay_api_key', 'test-key');
[$c, $d] = api($kernel, 'POST', '/api/v1/orders', ['address_id' => $addr, 'payment_method' => 'zinipay', 'items' => [['product_id' => $p1->id, 'quantity' => 1], ['product_id' => $p2->id, 'quantity' => 1]]], $tok);
t('zinipay mixed checkout 201 with 2 orders', $c == 201 && count($d['data']['orders'] ?? []) == 2, [$c, $d['message'] ?? $d]);
$ids = array_column($d['data']['orders'], 'id'); $nums = $d['data']['order_numbers']; $sum = $d['data']['grand_total'];

Http::swap(new Factory());
Http::fake(['*/v1/payment/create' => Http::response(['status' => true, 'payment_url' => 'https://pay.example/inv_abc'], 200),
            '*/v1/payment/verify' => Http::response(['status' => 'COMPLETED', 'amount' => $sum, 'transaction_id' => 'TX9'], 200),
            '*' => Http::response([], 200)]);
[$c, $r] = api($kernel, 'POST', '/api/v1/payment/zinipay/create', ['order_id' => $ids[0]], $tok);
t('create invoice ok', $c == 200 && !empty($r['data']['payment_url']), [$c, $r]);
$sent = false; foreach (Http::recorded() as [$req]) { if (str_contains($req->url(), '/payment/create')) $sent = abs((float) $req['amount'] - $sum) < 0.01; }
t('invoice amount = total of BOTH orders (server-side)', $sent, [$sum]);
t('both orders carry the invoice id', Order::whereIn('id', $ids)->where('payment_transaction_id', 'inv_abc')->count() == 2);
[$c, $r] = api($kernel, 'POST', '/api/v1/payment/zinipay/verify', ['order_number' => $nums[1]], $tok);
t('verify via 2nd order marks BOTH paid', $c == 200 && Order::whereIn('id', $ids)->where('payment_status', 'paid')->count() == 2, [$c, $r]);

[$c, $d] = api($kernel, 'POST', '/api/v1/orders', ['address_id' => $addr, 'payment_method' => 'zinipay', 'items' => [['product_id' => $p1->id, 'quantity' => 1], ['product_id' => $p2->id, 'quantity' => 1]]], $tok);
$ids2 = array_column($d['data']['orders'], 'id'); $sum2 = $d['data']['grand_total'];
Http::swap(new Factory());
Http::fake(['*/v1/payment/create' => Http::response(['status' => true, 'payment_url' => 'https://pay.example/inv_part'], 200),
            '*/v1/payment/verify' => Http::response(['status' => 'COMPLETED', 'amount' => $sum2 / 2, 'transaction_id' => 'TX10'], 200)]);
api($kernel, 'POST', '/api/v1/payment/zinipay/create', ['order_id' => $ids2[0]], $tok);
[$c, $r] = api($kernel, 'POST', '/api/v1/payment/zinipay/verify', ['order_number' => $d['data']['order_numbers'][0]], $tok);
t('half payment is rejected, nothing marked paid', $c == 422 && Order::whereIn('id', $ids2)->where('payment_status', 'paid')->count() == 0, [$c, $r]);

// ---------- SMS: templates, switches, credentials from Admin settings
Http::swap(new Factory()); Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED', 'msg_id' => 'm1']]], 200)]);
$order = Order::find($ids[0]); $sms = app(App\Services\SmsService::class);
put('shop_name', 'AdyabdShop'); put('sms_gateway', 'ssl_wireless'); put('ssl_wireless_token', 'TOKEN123');
put('sms_on_order_placed', '1'); NotificationTemplate::where('name', 'order_placed')->update(['is_active' => true]);
$n0 = SmsLog::count(); $sms->sendOrderPlaced($order);
$log = SmsLog::latest('id')->first();
t('order-placed SMS uses template + shop name from Admin', SmsLog::count() == $n0 + 1 && str_contains($log->message, $order->order_number) && str_contains($log->message, 'AdyabdShop'), $log->message ?? null);
$tokenSent = false; foreach (Http::recorded() as [$req]) { if (str_contains($req->url(), 'sslwireless') && ($req['api_token'] ?? '') === 'TOKEN123') $tokenSent = true; }
t('SMS gateway token comes from Admin Settings (not only .env)', $tokenSent);
put('sms_on_order_placed', '0'); $n1 = SmsLog::count(); $sms->sendOrderPlaced($order);
t('switch "On Order Placed" OFF -> no SMS', SmsLog::count() == $n1);
put('sms_on_order_placed', '1'); NotificationTemplate::where('name', 'order_placed')->update(['is_active' => false]); $n2 = SmsLog::count(); $sms->sendOrderPlaced($order);
t('template switched off -> no SMS', SmsLog::count() == $n2);
NotificationTemplate::where('name', 'order_placed')->update(['is_active' => true, 'body' => 'CUSTOM {order_number} {customer_name}']); Cache::flush();
$sms->sendOrderPlaced($order); $log = SmsLog::latest('id')->first();
t('edited template text is what gets sent', str_starts_with($log->message, 'CUSTOM ' . $order->order_number), $log->message);
put('sms_on_order_confirmed', '0'); $n3 = SmsLog::count(); $order->updateStatus('confirmed', 't', 'admin');
t('status "confirmed" SMS respects its switch (off)', SmsLog::count() == $n3);
put('sms_on_order_confirmed', '1'); $order->updateStatus('processing', 't', 'admin'); $n4 = SmsLog::count(); Order::find($ids[1])->updateStatus('confirmed', 't', 'admin');
t('status "confirmed" SMS sent when switch ON', SmsLog::count() == $n4 + 1);

// ---------- seller payout per split order + referral bonus + reviews
$o1 = Order::find($ids[0]); $o1->update(['status' => 'shipped']);
$s1 = Seller::find($o1->seller_id); $b0 = (float) $s1->balance; $s2b = (float) Seller::find(Order::find($ids[1])->seller_id)->balance;
$ref = User::where('id', '!=', $user->id)->first(); $wb = (float) $ref->wallet_balance;
User::whereKey($user->id)->update(['referred_by' => $ref->id, 'completed_orders' => 0]);
put('enable_referral', '1'); put('referral_bonus_amount', '75');
$o1->updateStatus('delivered', 't', 'system');
t('delivered split order credits ONLY its own seller (its payout)', abs((float) $s1->fresh()->balance - ($b0 + (float) $o1->seller_payout)) < 0.01 && abs((float) Seller::find(Order::find($ids[1])->seller_id)->balance - $s2b) < 0.01);
t('referrer gets the bonus after first delivered order', abs((float) $ref->fresh()->wallet_balance - ($wb + 75)) < 0.01 && Transaction::where('reference_type', 'referral_bonus')->where('reference_id', $user->id)->count() == 1, $ref->fresh()->wallet_balance);
Order::find($ids[1])->updateStatus('delivered', 't', 'system');
t('bonus paid only once', abs((float) $ref->fresh()->wallet_balance - ($wb + 75)) < 0.01);

put('enable_reviews', '0');
[$c, $r] = api($kernel, 'POST', '/api/v1/reviews', ['product_id' => $p1->id, 'rating' => 5, 'comment' => 'Nice one here'], $tok);
t('reviews switched off -> 403', $c == 403, [$c, $r]);
put('enable_reviews', '1'); put('auto_approve_reviews', '0');
[$c, $r] = api($kernel, 'POST', '/api/v1/reviews', ['product_id' => $p1->id, 'rating' => 5, 'comment' => 'Nice one here'], $tok);
t('delivered buyer can review; waits for approval', $c == 201 && $r['data']['is_approved'] == false, [$c, $r]);
[$c, $r] = api($kernel, 'POST', '/api/v1/reviews', ['product_id' => $p1->id, 'rating' => 4, 'comment' => 'Second try'], $tok);
t('second review of same product -> 422 (no more 500)', $c == 422, [$c, $r]);
Review::where('product_id', $p1->id)->where('user_id', $user->id)->forceDelete();
put('auto_approve_reviews', '1');
[$c, $r] = api($kernel, 'POST', '/api/v1/reviews', ['product_id' => $p1->id, 'rating' => 5, 'comment' => 'Auto approved'], $tok);
t('auto-approve ON -> published immediately', $c == 201 && $r['data']['is_approved'] == true, [$c, $r]);

// ---------- auto-block master switch
$rule = AutoBlockRule::where('trigger', 'cancel_order')->where('is_active', true)->first();
if ($rule) {
    $victim = User::where('id', '>', 3)->where('is_blocked', false)->first();
    $mk = fn() => Order::where('seller_id', 1)->first();
    AutoBlockRule::whereKey($rule->id)->update(['within_days' => null]); // count by the stored counter, not by this victim's real order history
    $o = Order::where('user_id', $user->id)->first(); $o->user_id = $victim->id; $o->cancelled_by = 'user';
    User::whereKey($victim->id)->update(['cancel_order_count' => $rule->threshold - 1]);
    put('auto_block_enabled', '0');
    app(App\Services\BlockingService::class)->handleOrderCancellation($o->setRelation('user', $victim->fresh()));
    t('auto-block master switch OFF -> user not blocked', !$victim->fresh()->is_blocked);
    put('auto_block_enabled', '1');
    User::whereKey($victim->id)->update(['cancel_order_count' => $rule->threshold - 1]);
    app(App\Services\BlockingService::class)->handleOrderCancellation($o->setRelation('user', $victim->fresh()));
    t('auto-block switch ON -> same case blocks the user', (bool) $victim->fresh()->is_blocked);
}
