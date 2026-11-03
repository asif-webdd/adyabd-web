<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CourierMerchant, Order, OrderStatusHistory};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SteadfastWebhookController extends Controller
{
    /**
     * Handle SteadFast delivery status webhook.
     * Docs: https://docs.steadfast.com.bd/docs/delivery-status-webhook
     *
     * Route: POST /api/v1/webhooks/steadfast
     *
     * Authentication is read from HTTP headers only (checked in order):
     *  1. Authorization: Bearer {webhook_secret OR api_key}
     *  2. X-Webhook-Secret: {webhook_secret}
     *
     * Payload example:
     * {
     *   "notification_type": "delivery_status",
     *   "consignment_id": 12345,
     *   "tracking_code": "1A2B3C4D",
     *   "invoice": "ORD-0001",
     *   "delivery_status": "delivered",
     *   "cod_amount": "500.00"
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('SteadFast webhook received', $payload);

        $merchant = $this->resolveMerchant($request);

        if (!$merchant) {
            Log::warning('SteadFast webhook authentication failed', $payload);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $trackingCode   = $payload['tracking_code'] ?? $payload['consignment_id'] ?? null;
        $invoice        = $payload['invoice'] ?? null;
        $deliveryStatus = strtolower($payload['delivery_status'] ?? $payload['status'] ?? 'unknown');

        if (!$trackingCode && !$invoice) {
            return response()->json(['message' => 'Missing tracking_code/consignment_id or invoice'], 422);
        }

        // Find order by courier_tracking_id first, fallback to order_number (invoice)
        $order = null;
        if ($trackingCode) {
            $order = Order::where('courier_tracking_id', $trackingCode)->first();
        }
        if (!$order && $invoice) {
            $order = Order::where('order_number', $invoice)->first();
        }

        if (!$order) {
            Log::warning('SteadFast webhook: order not found', $payload);
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->applyCourierStatus($deliveryStatus, 'webhook', $payload);

        return response()->json(['message' => 'OK']);
    }

    /**
     * Resolve and authenticate the CourierMerchant this webhook belongs to,
     * using HTTP headers only.
     *
     * Checked in order:
     *  1. Authorization: Bearer {token} *     -> matched against CourierMerchant.config['webhook_secret'], *        then against CourierMerchant.api_key
     *  2. X-Webhook-Secret: {token} *     -> matched against CourierMerchant.config['webhook_secret']
     *  3. If the active steadfast-courier merchant has neither *     webhook_secret nor api_key configured, accept unverified.
     *
     * Returns the authenticated CourierMerchant, or null if authentication fails.
     */
    private function resolveMerchant(Request $request): ?CourierMerchant
    {
        $merchant = CourierMerchant::where('slug', 'steadfast-courier')->where('is_active', true)->first();

        if (!$merchant) {
            return null;
        }

        $configuredSecret = $merchant->config['webhook_secret'] ?? null;
        $configuredApiKey = $merchant->api_key ?? null;

        // Nothing configured to verify against -> accept.
        if (!$configuredSecret && !$configuredApiKey) {
            return $merchant;
        }

        // 1. Authorization: Bearer {token}
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            if ($configuredSecret && hash_equals($configuredSecret, (string) $bearerToken)) {
                return $merchant;
            }

            if ($configuredApiKey && hash_equals($configuredApiKey, (string) $bearerToken)) {
                return $merchant;
            }
        }

        // 2. X-Webhook-Secret header
        $headerSecret = $request->header('X-Webhook-Secret');

        if ($configuredSecret && $headerSecret && hash_equals($configuredSecret, (string) $headerSecret)) {
            return $merchant;
        }

        return null;
    }
}
