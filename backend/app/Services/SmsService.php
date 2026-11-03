<?php

namespace App\Services;

use Exception;
use App\Models\{SmsLog, User, Order, NotificationTemplate};
use Illuminate\Support\Facades\{Http, Log};

class SmsService
{
    public function sendBulk(array $phones, string $message): array
    {
        $sent = $failed = 0;

        foreach (array_chunk($phones, 50) as $chunk) {
            foreach ($chunk as $phone) {
                $this->send($phone, $message) ? $sent++ : $failed++;
            }

            usleep(200000);
        }

        return [
            'success' => $sent,
            'failed'  => $failed,
        ];
    }

    public function send(string $phone, string $message, mixed $ref = null): bool
    {
        $phone = $this->formatPhone($phone);
        $gateway = setting('sms_gateway', 'ssl_wireless');

        $result = match ($gateway) {
            'ssl_wireless' => $this->sslWireless($phone, $message),
            'bulk_sms_bd'  => $this->bulkSmsBd($phone, $message),
            default        => ['success' => false, 'message' => 'Unknown gateway'],
        };

        SmsLog::create([
            'recipient_phone'    => $phone,
            'user_id'            => $ref instanceof Order ? $ref->user_id : null,
            'message'            => $message,
            'gateway'            => $gateway,
            'status'             => $result['success'] ? 'sent' : 'failed',
            'gateway_message_id' => $result['message_id'] ?? null,
            'gateway_response'   => $result['raw'] ?? null,
            'reference_type'     => $ref ? class_basename($ref) : null,
            'reference_id'       => $ref ? $ref->id : null,
        ]);

        return $result['success'];
    }

    private function formatPhone(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($p, '880')) {
            return $p;
        }

        if (str_starts_with($p, '0')) {
            return '880' . substr($p, 1);
        }

        return '880' . $p;
    }

    private function sslWireless(string $phone, string $message): array
    {
        try {
            $r = Http::post(
                config('services.sms.ssl_wireless.url', 'https://sms.sslwireless.com/api/v3/send-sms'),
                [
                    'api_token' => setting('ssl_wireless_token') ?: config('services.sms.ssl_wireless.token'),
                    'sid'       => setting('ssl_wireless_sid') ?: setting('sms_sender_id') ?: config('services.sms.ssl_wireless.sid'),
                    'sms'       => [
                        [
                            'to'     => $phone,
                            'msg'    => $message,
                            'msg_id' => uniqid('sms_'),
                        ]],
                    'csms_id'   => uniqid('bulk_'),
                ]
            );

            $d = $r->json();

            if ($r->successful() && ($d['data'][0]['status'] ?? '') === 'ACCEPTED') {
                return [
                    'success'    => true,
                    'message_id' => $d['data'][0]['msg_id'] ?? null,
                    'raw'        => $d,
                ];
            }

            return [
                'success' => false,
                'message' => $d['message'] ?? 'SSL Wireless failed',
                'raw'     => $d,
            ];
        } catch (Exception $e) {
            Log::error('SSL Wireless error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function bulkSmsBd(string $phone, string $message): array
    {
        try {
            $r = Http::post(config('services.sms.bulk_sms_bd.url', 'https://bulksmsbd.net/api/smsapi'),
                [
                    'api_key'  => setting('bulk_sms_api_key') ?: config('services.sms.bulk_sms_bd.api_key'),
                    'type'     => 'text',
                    'number'   => $phone,
                    'senderid' => setting('sms_sender_id') ?: config('services.sms.bulk_sms_bd.sender_id', 'BazarBD'),
                    'message'  => $message,
                ]
            );

            $d = $r->json();

            if ($r->successful() && ($d['response_code'] ?? 0) == 202) {
                return [
                    'success'    => true,
                    'message_id' => $d['reference_id'] ?? null,
                    'raw'        => $d,
                ];
            }

            return [
                'success' => false,
                'message' => $d['error_message'] ?? 'BulkSMSBD failed',
                'raw'     => $d,
            ];
        } catch (Exception $e) {
            Log::error('BulkSMSBD error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send an SMS built from an admin-editable template (Admin → Notification Templates).
     *
     *  - $toggle   : Settings → SMS "On …" switch that must be ON (default on)
     *  - template row exists but is switched off  → nothing is sent
     *  - template row missing                      → $fallback text is used
     */
    public function sendTemplate(string $name, ?string $phone, array $vars = [], mixed $ref = null, ?string $fallback = null, ?string $toggle = null): bool
    {
        if (!$phone) {
            return false;
        }
        if ($toggle && !setting($toggle, true)) {
            return false;
        }

        $tpl = NotificationTemplate::where('name', $name)->where('type', 'sms')->first();
        if ($tpl && !$tpl->is_active) {
            return false;
        }

        $vars += [
            'shop_name' => $this->shopName(),
            'helpline'  => setting('shop_phone') ?: '16488',
            'site_url'  => rtrim((string) (setting('shop_url') ?: config('app.frontend_url') ?: config('app.url')), '/'),
        ];

        $pairs = [];
        foreach ($vars as $k => $v) {
            $pairs['{' . $k . '}'] = (string) $v;
        }

        $body = $tpl ? $tpl->parse($vars) : ($fallback !== null ? strtr($fallback, $pairs) : null);
        if (!$body) {
            return false;
        }

        return $this->send($phone, $body, $ref);
    }

    public function shopName(): string
    {
        return (string) (setting('shop_name') ?: config('app.name', 'Shop'));
    }

    public function orderVars(Order $order): array
    {
        return [
            'order_number'  => $order->order_number,
            'customer_name' => $order->shipping_name,
            'total_amount'  => 'BDT ' . number_format((float) $order->total_amount, 0),
            'tracking_id'   => $order->courier_tracking_id ?: 'N/A',
            'tracking_url'  => $order->courier_tracking_url ?: '#',
        ];
    }

    public function sendOtp(string $phone, string $otp): bool
    {
        return $this->sendTemplate(
            'otp',
            $phone,
            ['otp' => $otp],
            null,
            '{shop_name} OTP: {otp}. This code is valid for 5 minutes. Do not share it with anyone.'
        );
    }

    public function sendOrderPlaced(Order $order): bool
    {
        return $this->sendTemplate(
            'order_placed',
            $order->shipping_phone,
            $this->orderVars($order),
            $order,
            '{shop_name}: Your order #{order_number} has been received. Total: {total_amount}.',
            'sms_on_order_placed'
        );
    }

    /**
     * Kept for the admin "courier booked" buttons. Booking already moves the order to
     * "shipped", and that status change sends the order_shipped template (with tracking),
     * so sending here as well would text the customer twice.
     */
    public function sendCourierBooked(Order $order): bool
    {
        return true;
    }
}
