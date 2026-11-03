<?php


namespace App\Services;

use Illuminate\Support\Facades\{Http, Log};

class PaymentService
{
    /**
     * ZiniPay API key — read from admin Settings first (live-editable,
     * no deploy/restart needed) and fall back to the .env value.
     */
    protected function zinipayApiKey(): ?string
    {
        return setting('zinipay_api_key') ?: config('services.zinipay.api_key');
    }

    /**
     * ZiniPay API base URL — same dynamic-with-fallback pattern as the API key.
     */
    protected function zinipayBaseUrl(): string
    {
        return rtrim(setting('zinipay_base_url') ?: config('services.zinipay.base_url', 'https://api.zinipay.com'), '/');
    }

    /**
     * Webhook URL ZiniPay calls after a payment update — read from admin
     * Settings first, falling back to the .env-derived value.
     */
    protected function zinipayWebhookUrl(): ?string
    {
        return setting('zinipay_webhook_url') ?: config('services.zinipay.webhook_url');
    }

    /**
     * Create a hosted ZiniPay invoice and return the secure payment URL.
     *
     * @param  float       $amount      Invoice amount (BDT).
     * @param  string      $redirectUrl Where the customer is sent after a successful payment.
     * @param  string|null $cancelUrl   Where the customer is sent if they cancel.
     * @param  string|null $custName    Customer name (optional).
     * @param  string|null $custEmail   Customer email (optional).
     * @param  array       $metadata    Arbitrary metadata (kept under 1 KB), e.g. order id.
     */
    public function zinipayCreateInvoice(
        float $amount,
        string $redirectUrl,
        ?string $cancelUrl = null,
        ?string $custName = null,
        ?string $custEmail = null,
        array $metadata = []
    ): array {
        $apiKey = $this->zinipayApiKey();
        if (!$apiKey) {
            return ['success' => false, 'message' => 'ZiniPay API key is not configured. Set it in Admin → Settings → Payment.'];
        }

        try {
            $payload = array_filter([
                'cus_name'     => $custName,
                'cus_email'    => $custEmail,
                'amount'       => $amount,
                'metadata'     => !empty($metadata) ? $metadata : null,
                'redirect_url' => $redirectUrl,
                'cancel_url'   => $cancelUrl,
                'webhook_url'  => $this->zinipayWebhookUrl(),
            ], fn($v) => $v !== null);

            $r = Http::withHeaders([
                'Content-Type' => 'application/json',
                'zini-api-key' => $apiKey,
            ])->post($this->zinipayBaseUrl() . '/v1/payment/create', $payload);

            $d = $r->json();
            $paymentUrl = $d['payment_url'] ?? null;
            $invoiceId  = $paymentUrl ? basename(rtrim($paymentUrl, '/')) : null;

            return [
                'success'     => (bool)($d['status'] ?? false) && $paymentUrl,
                'payment_url' => $paymentUrl,
                'invoice_id'  => $invoiceId,
                'message'     => $d['message'] ?? null,
                'raw'         => $d,
            ];
        } catch (\Exception $e) {
            Log::error('ZiniPay create invoice error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify the latest payment status for an invoice from the backend.
     */
    public function zinipayVerifyInvoice(string $invoiceId): array
    {
        $apiKey = $this->zinipayApiKey();
        if (!$apiKey) {
            return ['success' => false, 'message' => 'ZiniPay API key is not configured. Set it in Admin → Settings → Payment.'];
        }

        try {
            $r = Http::withHeaders([
                'Content-Type' => 'application/json',
                'zini-api-key' => $apiKey,
            ])->post($this->zinipayBaseUrl() . '/v1/payment/verify', [
                'invoice_id' => $invoiceId,
            ]);

            $d = $r->json();
            $status = $d['status'] ?? null;

            return [
                'success'        => $status === 'COMPLETED',
                'status'         => $status,
                'transaction_id' => $d['transaction_id'] ?? null,
                'amount'         => $d['amount'] ?? null,
                'payment_method' => $d['payment_method'] ?? null,
                'raw'            => $d,
            ];
        } catch (\Exception $e) {
            Log::error('ZiniPay verify invoice error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
