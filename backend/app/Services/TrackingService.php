<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    /**
     * Nuxt থেকে আসা tracking তথ্য (fbp, fbc, client_id, session_id, url) পরিষ্কার করে,
     * সাথে ক্রেতার IP ও ব্রাউজার যোগ করে।
     */
    public function contextFromRequest(Request $request): array
    {
        $in = $request->input('tracking');
        $in = is_array($in) ? $in : [];

        $clean = [];
        foreach (['fbp', 'fbc', 'client_id', 'session_id', 'url'] as $k) {
            if (isset($in[$k]) && is_scalar($in[$k]) && $in[$k] !== '') {
                $clean[$k] = mb_substr((string) $in[$k], 0, 500);
            }
        }

        // লোকাল/প্রাইভেট IP (127.0.0.1 ইত্যাদি) Meta-তে পাঠানো অর্থহীন, তাই বাদ
        $ip = $request->ip();
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $clean['ip'] = $ip;
        }
        $ua = (string) $request->userAgent();
        if ($ua !== '') {
            $clean['user_agent'] = mb_substr($ua, 0, 500);
        }

        return $clean;
    }

    /**
     * এক checkout-এ একাধিক অর্ডার (সেলার ভাগ) তৈরি হলে সবকটা মিলিয়ে একটাই Purchase বানায়।
     * transaction_id = প্রথম অর্ডারের নম্বর, value = সব অর্ডারের total_amount-এর যোগফল।
     * (Nuxt-এর ব্রাউজার purchase ইভেন্টের সাথে এটা মিলতে হবে)
     */
    public function purchaseFromOrders(iterable $orders, $user, array $context): array
    {
        $orders = collect($orders)->values();
        $first  = $orders->first();

        $items = [];
        foreach ($orders as $o) {
            foreach ($o->items as $i) {
                $items[] = [
                    'id'       => $i->product_id,
                    'name'     => $i->product_name,
                    'price'    => (float) $i->unit_price,
                    'quantity' => (int) $i->quantity,
                ];
            }
        }

        $parts     = preg_split('/\s+/u', trim((string) $first->shipping_name)) ?: [];
        $lastName  = count($parts) > 1 ? array_pop($parts) : null;
        $firstName = implode(' ', $parts) ?: null;

        return [
            'order_number' => $first->order_number,
            'total'        => (float) $orders->sum('total_amount'),
            'shipping'     => (float) $orders->sum('shipping_charge'),
            'tax'          => 0,
            'currency'     => 'BDT',
            'placed_at'    => optional($first->created_at)->timestamp ?? time(),
            'items'        => $items,
            'customer'     => [
                'email'      => $user->email ?? null,
                'phone'      => $first->shipping_phone ?: ($user->phone ?? null),
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'city'       => $first->shipping_district,
                'user_id'    => $user->id,
            ],
            'tracking'     => $context,
        ];
    }

    /**
     * অর্ডারের Purchase ইভেন্ট Cloudflare Worker-এ পাঠায়।
     * $order-এর আকার purchaseFromOrders()-এর রিটার্ন ভ্যালুর মতো।
     */
    public function sendPurchase(array $order): void
    {
        $url = config('services.tracking.url');
        $key = config('services.tracking.key');
        if (! $url || ! $key) {
            return;
        }

        $t = $order['tracking'] ?? [];
        $c = $order['customer'] ?? [];

        $payload = array_filter([
            'event'             => 'purchase',
            // ব্রাউজারের purchase ইভেন্টের event_id-র সাথে হুবহু এক (dedup এর জন্য)
            'event_id'          => 'purchase_' . $order['order_number'],
            'event_time'        => $order['placed_at'] ?? time(),
            'url'               => $t['url'] ?? null,
            'client_id'         => $t['client_id'] ?? null,
            'session_id'        => $t['session_id'] ?? null,
            'fbp'               => $t['fbp'] ?? null,
            'fbc'               => $t['fbc'] ?? null,
            'client_ip'         => $t['ip'] ?? null,
            'client_user_agent' => $t['user_agent'] ?? null,
            'email'             => $c['email'] ?? null,
            'phone'             => $c['phone'] ?? null,
            'first_name'        => $c['first_name'] ?? null,
            'last_name'         => $c['last_name'] ?? null,
            'city'              => $c['city'] ?? null,
            'country'           => 'bd',
            'external_id'       => isset($c['user_id']) ? (string) $c['user_id'] : null,
            'ecommerce'         => [
                'transaction_id' => (string) $order['order_number'],
                'currency'       => $order['currency'] ?? 'BDT',
                'value'          => (float) $order['total'],
                'shipping'       => (float) ($order['shipping'] ?? 0),
                'tax'            => (float) ($order['tax'] ?? 0),
                'items'          => array_map(fn ($i) => [
                    'item_id'   => (string) $i['id'],
                    'item_name' => $i['name'],
                    'price'     => (float) $i['price'],
                    'quantity'  => (int) $i['quantity'],
                ], $order['items'] ?? []),
            ],
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $res = Http::withHeaders(['X-Server-Key' => $key])
                ->acceptJson()
                ->timeout(10)
                ->post($url, $payload);

            if ($res->failed()) {
                Log::warning('Tracking purchase failed', [
                    'order'  => $order['order_number'],
                    'status' => $res->status(),
                ]);
            }
        } catch (\Throwable $e) {
            // ট্র্যাকিং ব্যর্থ হলেও অর্ডার প্রক্রিয়া যেন না থামে
            Log::warning('Tracking purchase exception', [
                'order' => $order['order_number'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
