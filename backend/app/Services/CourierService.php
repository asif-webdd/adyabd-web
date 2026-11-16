<?php

namespace App\Services;

use App\Models\{CourierMerchant, Order};
use Exception;
use Illuminate\Support\Facades\{Http, Log};

class CourierService
{
    public function book(CourierMerchant $merchant, Order $order): array
    {
        //dd($merchant->toArray(), $order->toArray());
        return match (strtolower($merchant->slug)) {
            'steadfast', 'steadfast-courier' => $this->steadFast($merchant, $order),
            'pathao'            => $this->pathao($merchant, $order),
            'redx'              => $this->redx($merchant, $order),
            'paperfly'          => $this->paperfly($merchant, $order),
            'sundarban'         => $this->sundarban($merchant, $order),
            default             => $this->generic($merchant, $order),
        };
    }

    /**
     * Current delivery status of an already-booked parcel (SteadFast only for now).
     * Returns the courier's own status string, or null when it cannot be determined.
     */
    public function fetchStatus(CourierMerchant $merchant, Order $order): ?string
    {
        if (!in_array(strtolower($merchant->slug), ['steadfast', 'steadfast-courier'], true) || !$order->courier_tracking_id) {
            return null;
        }
        try {
            $r = Http::timeout(15)->withHeaders([
                'Api-Key'    => $merchant->api_key,
                'Secret-Key' => $merchant->api_secret,
            ])->get(rtrim($merchant->base_url, '/') . '/status_by_trackingcode/' . $order->courier_tracking_id);

            return $r->successful() ? ($r->json('delivery_status') ?: null) : null;
        } catch (Exception $e) {
            Log::warning('SteadFast status check failed: ' . $e->getMessage());
            return null;
        }
    }

    private function steadFast(CourierMerchant $merchant, Order $order): array
    {
        try {
            // SteadFast (Packzy) wants an 11-digit local number: 01XXXXXXXXX
            $phone = preg_replace('/\D+/', '', (string) $order->shipping_phone);
            $phone = preg_replace('/^(?:00)?880/', '0', $phone);
            if (strlen($phone) === 10 && $phone[0] === '1') {
                $phone = '0' . $phone;
            }

            $payload = [
                'invoice'           => preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order->order_number),
                'recipient_name'    => mb_substr((string) $order->shipping_name, 0, 100),
                'recipient_phone'   => $phone,
                'recipient_address' => mb_substr(trim($order->shipping_address . ', ' . $order->shipping_district, ', '), 0, 250),
                'cod_amount'        => $order->payment_method === 'cod' ? (int) round($order->total_amount) : 0,
                'note'              => mb_substr((string) ($order->customer_note ?? ''), 0, 250),
                'item_description'  => mb_substr($order->items->pluck('product_name')->implode(', '), 0, 250),
                'total_lot'         => (int) $order->items->sum('quantity'),
                'delivery_type'     => 0, // 0 = home delivery, 1 = point delivery
            ];
            $email = $order->user->email ?? null;
            if ($email) {
                $payload['recipient_email'] = $email;
            }

            $response = Http::acceptJson()->withHeaders([
                'Api-Key'    => trim((string) $merchant->api_key),
                'Secret-Key' => trim((string) $merchant->api_secret),
            ])->post(rtrim($merchant->base_url, '/') . '/create_order', $payload);

            $decodeResponse = $response->json();
            if ($response->successful() && isset($decodeResponse['consignment']['tracking_code'])) {
                return [
                    'success'      => true,
                    'tracking_id'  => $decodeResponse['consignment']['tracking_code'],
                    'tracking_url' => 'https://steadfast.com.bd/t/' . $decodeResponse['consignment']['tracking_code'],
                    'courier_name' => 'SteadFast',
                    'raw'          => $decodeResponse,
                ];
            }

            // show the real reason (field-level validation errors included)
            $msg = $decodeResponse['message'] ?? 'SteadFast booking failed';
            if (!empty($decodeResponse['errors']) && is_array($decodeResponse['errors'])) {
                $msg .= ': ' . collect($decodeResponse['errors'])->flatten()->implode(', ');
            }
            Log::warning('SteadFast booking failed', ['status' => $response->status(), 'payload' => $payload, 'response' => $decodeResponse]);

            return ['success' => false, 'message' => $msg, 'raw' => $decodeResponse];
        } catch (Exception $e) {
            Log::error('SteadFast error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function pathao(CourierMerchant $m, Order $o): array
    {
        try {
            $tokenRes = Http::post($m->base_url . '/aladdin/api/v1/issue-token', ['client_id' => $m->api_key, 'client_secret' => $m->api_secret, 'username' => $m->api_username, 'password' => $m->api_password, 'grant_type' => 'password']);
            $token = $tokenRes->json('access_token');
            if (!$token) return ['success' => false, 'message' => 'Pathao token failed'];

            $config = $m->config ?? [];
            $r = Http::withToken($token)->post($m->base_url . '/aladdin/api/v1/orders', [
                'store_id'          => $config['store_id'] ?? null,
                'merchant_order_id' => $o->order_number,
                'recipient_name'    => $o->shipping_name,
                'recipient_phone'   => $o->shipping_phone,
                'recipient_address' => $o->shipping_address,
                'recipient_city'    => $config['default_city'] ?? 1,
                'recipient_zone'    => $config['default_zone'] ?? 1,
                'delivery_type'     => $o->delivery_type === 'express' ? 2 : 48,
                'item_type'         => 2,
                'item_quantity'     => $o->items->sum('quantity'),
                'item_weight'       => 0.5,
                'amount_to_collect' => $o->payment_method === 'cod' ? $o->total_amount : 0,
                'item_description'  => $o->items->pluck('product_name')->implode(', '),
            ]);
            $d = $r->json();
            if ($r->successful() && isset($d['data']['consignment_id'])) {
                return ['success' => true, 'tracking_id' => $d['data']['consignment_id'], 'tracking_url' => 'https://merchant.pathao.com/order/' . $d['data']['consignment_id'], 'courier_name' => 'Pathao', 'raw' => $d];
            }
            return ['success' => false, 'message' => $d['message'] ?? 'Pathao booking failed', 'raw' => $d];
        } catch (Exception $e) {
            Log::error('Pathao error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function redx(CourierMerchant $m, Order $o): array
    {
        try {
            $r = Http::withHeaders(['API-ACCESS-TOKEN' => 'Bearer ' . $m->api_key])->post($m->base_url . '/v1.0.0-beta/parcel', [
                'customer_name'          => $o->shipping_name,
                'customer_phone'         => $o->shipping_phone,
                'delivery_area'          => $o->shipping_district,
                'delivery_area_id'       => $this->redxAreaId($o->shipping_district),
                'customer_address'       => $o->shipping_address,
                'merchant_invoice_id'    => $o->order_number,
                'cash_collection_amount' => $o->payment_method === 'cod' ? $o->total_amount : 0,
                'parcel_weight'          => 500,
                'value'                  => $o->total_amount,
            ]);
            $d = $r->json();
            if ($r->successful() && isset($d['tracking_id'])) {
                return ['success' => true, 'tracking_id' => $d['tracking_id'], 'tracking_url' => 'https://redx.com.bd/track-parcel/?trackingId=' . $d['tracking_id'], 'courier_name' => 'Redx', 'raw' => $d];
            }
            return ['success' => false, 'message' => $d['message'] ?? 'Redx failed', 'raw' => $d];
        } catch (Exception $e) {
            Log::error('Redx error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function redxAreaId(string $district): int
    {
        return ['Dhaka' => 1, 'Chittagong' => 2, 'Sylhet' => 6, 'Rajshahi' => 8, 'Khulna' => 4, 'Barisal' => 5][$district] ?? 1;
    }

    private function paperfly(CourierMerchant $m, Order $o): array
    {
        try {
            $r = Http::withHeaders(['X-AUTH-KEY' => $m->api_key])
                ->post($m->base_url . '/merchantOrder', [
                    'order_id'        => $o->order_number,
                    'rec_name'        => $o->shipping_name,
                    'rec_address'     => $o->shipping_address,
                    'rec_area'        => $o->shipping_district,
                    'rec_phone'       => $o->shipping_phone,
                    'coll_amount'     => $o->payment_method === 'cod' ? $o->total_amount : 0,
                    'note'            => $o->customer_note ?? '',
                    'parcel_weight'   => 1,
                    'parcel_quantity' => $o->items->sum('quantity'),
                ]);
            $d = $r->json();
            $id = $d['PaperFly_OrderID'] ?? $d['tracking_id'] ?? null;
            return ['success' => (bool)$id, 'tracking_id' => $id, 'tracking_url' => 'https://paperfly.com.bd/track?id=' . $id, 'courier_name' => 'Paperfly', 'raw' => $d];
        } catch (Exception $e) {
            Log::error('Paperfly error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function sundarban(CourierMerchant $m, Order $o): array
    {
        try {
            $r = Http::withHeaders(['Authorization' => 'Bearer ' . $m->api_key])
                ->post($m->base_url . '/create-order', [
                    'OrderId'         => $o->order_number,
                    'CustomerName'    => $o->shipping_name,
                    'CustomerPhone'   => $o->shipping_phone,
                    'CustomerAddress' => $o->shipping_address . ', ' . $o->shipping_district,
                    'CODAmount'       => $o->payment_method === 'cod' ? $o->total_amount : 0,
                ]);
            $d = $r->json();
            $id = $d['TrackingId'] ?? $d['tracking_id'] ?? null;
            return ['success' => (bool)$id, 'tracking_id' => $id, 'tracking_url' => 'https://www.sundarbanexpress.net/track/' . $id, 'courier_name' => 'Sundarban', 'raw' => $d];
        } catch (Exception $e) {
            Log::error('Sundarban error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generic(CourierMerchant $m, Order $o): array
    {
        try {
            $r = Http::withHeaders(['Authorization' => 'Bearer ' . $m->api_key])->post($m->base_url . '/create-order', ['order_id' => $o->order_number, 'name' => $o->shipping_name, 'phone' => $o->shipping_phone, 'address' => $o->shipping_address, 'cod_amount' => $o->payment_method === 'cod' ? $o->total_amount : 0]);
            $d = $r->json();
            $id = $d['tracking_id'] ?? $d['id'] ?? null;
            return ['success' => (bool)$id, 'tracking_id' => $id, 'tracking_url' => $m->getTrackingUrl($id ?? ''), 'courier_name' => $m->name, 'raw' => $d];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
