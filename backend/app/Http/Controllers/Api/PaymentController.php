<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * POST /api/v1/payment/zinipay/create  (auth)
     *
     * Creates a hosted ZiniPay invoice for the given order and returns the
     * secure payment URL the frontend should redirect the customer to.
     */
    public function zinipayCreate(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$order) return $this->error('Order not found.', 404);
        if ($order->payment_method !== 'zinipay') {
            return $this->error('This order is not a ZiniPay order.', 422);
        }
        if ($order->payment_status === 'paid') {
            return $this->error('This order has already been paid.', 422);
        }
        if (in_array($order->status, ['cancelled', 'returned'], true)) {
            return $this->error('This order can no longer be paid.', 422);
        }

        // Orders from one checkout (one per seller) are paid with ONE invoice.
        $group  = $this->payableGroup($order);
        $amount = (float) $group->sum('total_amount');

        $frontendUrl = rtrim(setting('zinipay_frontend_url') ?: config('app.frontend_url'), '/');

        $result = app(PaymentService::class)->zinipayCreateInvoice(
            amount: $amount, // server-side amount only; never trust the client
            redirectUrl: $frontendUrl . '/order/payment-callback?order=' . $order->order_number,
            cancelUrl: $frontendUrl . '/order/payment-callback?order=' . $order->order_number . '&cancelled=1',
            custName: $order->shipping_name,
            custEmail: $request->user()->email,
            metadata: ['order_id' => $order->id, 'order_number' => $order->order_number, 'order_ids' => $group->pluck('id')->all()],
        );

        if ($result['success']) {
            Order::whereIn('id', $group->pluck('id'))->update(['payment_transaction_id' => $result['invoice_id']]);
            return $this->success(['payment_url' => $result['payment_url'], 'invoice_id' => $result['invoice_id']]);
        }

        return $this->error($result['message'] ?? 'ZiniPay payment could not be initiated.');
    }

    /**
     * POST /api/v1/payment/zinipay/verify  (auth)
     *
     * Called by the frontend after the customer returns from the ZiniPay
     * hosted payment page, to confirm payment status before showing the
     * order success screen.
     */
    public function zinipayVerify(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'order_number' => 'required|exists:orders,order_number',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $order = Order::where('order_number', $request->order_number)
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$order) return $this->error('Order not found.', 404);

        if ($order->payment_status === 'paid') {
            return $this->success(['status' => 'COMPLETED', 'order' => $order], 'Payment completed.');
        }

        if (!$order->payment_transaction_id) {
            return $this->error('No invoice found for this order.');
        }

        $result = app(PaymentService::class)->zinipayVerifyInvoice($order->payment_transaction_id);

        if ($result['success']) {
            $group = Order::where('payment_transaction_id', $order->payment_transaction_id)
                ->where('user_id', $order->user_id)->get();
            if ($group->isEmpty()) $group = collect([$order]);

            if (!$this->amountMatches((float) $group->sum('total_amount'), $result)) {
                \Log::warning('ZiniPay amount mismatch on verify', ['order' => $order->order_number, 'result' => $result['amount'] ?? null]);
                return $this->error('Paid amount does not match the order total. Please contact support.', 422);
            }
            $this->markGroupPaid($group, $result['transaction_id'] ?? $order->payment_transaction_id);
            return $this->success(['status' => 'COMPLETED', 'order' => $order->fresh()], 'Payment successful!');
        }

        return $this->success(['status' => $result['status'] ?? 'PENDING', 'order' => $order], 'Payment processing.');
    }

    /**
     * Any /api/v1/payment/zinipay/webhook  (no auth)
     *
     * Receives ZiniPay's webhook callback after a payment update, verifies
     * the invoice from the backend, and marks the matching order as paid.
     */
    public function zinipayWebhook(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');
        if (!$invoiceId) return $this->error('Missing invoice_id.');

        $group = Order::where('payment_transaction_id', $invoiceId)->get();
        if ($group->isEmpty()) return $this->error('Order not found for this invoice.', [], 404);

        if ($group->every(fn($o) => $o->payment_status === 'paid')) {
            return $this->success([], 'Already processed.');
        }

        $result = app(PaymentService::class)->zinipayVerifyInvoice($invoiceId);

        if ($result['success']) {
            if (!$this->amountMatches((float) $group->sum('total_amount'), $result)) {
                \Log::warning('ZiniPay amount mismatch on webhook', ['invoice' => $invoiceId, 'result' => $result['amount'] ?? null]);
                return $this->success([], 'Callback received.');
            }
            $this->markGroupPaid($group, $result['transaction_id'] ?? $invoiceId);
        }

        return $this->success([], 'Callback received.');
    }

    /** Unpaid, still-open orders that were created by the same checkout as $order. */
    private function payableGroup(Order $order)
    {
        if (!$order->checkout_group) {
            return collect([$order]);
        }
        return Order::where('checkout_group', $order->checkout_group)
            ->where('user_id', $order->user_id)
            ->where('payment_method', 'zinipay')
            ->where('payment_status', '!=', 'paid')
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->get();
    }

    private function markGroupPaid($orders, string $transactionId): void
    {
        foreach ($orders as $o) {
            $o->update(['payment_status' => 'paid', 'payment_transaction_id' => $transactionId]);
            $o->fresh()->autoConfirmIfEnabled();
        }
    }

    /** The amount confirmed by ZiniPay must cover the total of the paid orders (1 BDT tolerance for rounding). */
    private function amountMatches(float $expected, array $result): bool
    {
        if (!isset($result['amount'])) return false;
        return (float) $result['amount'] + 1.0 >= $expected;
    }
}
