<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes, Factories\HasFactory};

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'checkout_group', 'seller_credited_at', 'seller_payout_reversed',
        'order_number', 'user_id', 'seller_id', 'coupon_id', 'coupon_code', 'status',
        'payment_method', 'payment_status', 'payment_transaction_id', 'subtotal',
        'discount_amount', 'coupon_discount', 'shipping_charge', 'total_amount',
        'commission_amount', 'seller_payout', 'shipping_name', 'shipping_phone',
        'shipping_address', 'shipping_division', 'shipping_district', 'shipping_upazila',
        'shipping_postal_code', 'delivery_type', 'estimated_delivery_at', 'delivered_at',
        'cancelled_at', 'cancellation_reason', 'cancelled_by', 'courier_merchant_id',
        'courier_tracking_id', 'courier_tracking_url', 'courier_status', 'courier_response',
        'courier_booked_at', 'customer_note', 'admin_note', 'user_ip', 'user_device',
    ];

    protected $casts = [
        'estimated_delivery_at' => 'datetime',
        'delivered_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
        'seller_credited_at'    => 'datetime',
        'courier_booked_at'     => 'datetime',
        'courier_response'      => 'array',
        'subtotal'              => 'decimal:2',
        'total_amount'          => 'decimal:2',
        'shipping_charge'       => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function courierMerchant(): BelongsTo
    {
        return $this->belongsTo(CourierMerchant::class);
    }

    public function scopeToday($q)
    {
        return $q->whereDate('created_at', today());
    }

    public function scopeDelivered($q)
    {
        return $q->where('status', 'delivered');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($o) => $o->order_number = static::generateOrderNumber());
    }

    /**
     * Generates the next order/invoice number using the admin-configurable
     * prefix, pattern, and sequence length (Admin → Settings → General →
     * Invoice Numbering). Falls back to sane defaults if unset.
     */
    public static function generateOrderNumber(): string
    {
        $prefix  = setting('invoice_prefix', 'BD');
        $pattern = setting('invoice_pattern', 'PREFIX-YEAR-SEQ');
        $digits  = (int) setting('invoice_sequence_length', 5);

        // withTrashed(): soft-deleted orders still own their number in the unique index.
        $seq = match ($pattern) {
            'PREFIX-SEQ'            => static::withTrashed()->count() + 1,
            'PREFIX-YEARMONTH-SEQ',
            'PREFIX-YEAR-MONTH-SEQ' => static::withTrashed()->whereYear('created_at', date('Y'))->whereMonth('created_at', date('n'))->count() + 1,
            default                 => static::withTrashed()->whereYear('created_at', date('Y'))->count() + 1, // PREFIX-YEAR-SEQ
        };

        // Concurrent requests may compute the same sequence: walk forward to a free number.
        do {
            $number = static::formatOrderNumber($prefix, $pattern, $digits, $seq++);
        } while (static::withTrashed()->where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Builds a sample order number for the given settings — used to render
     * a live preview in the admin Settings form without touching the DB.
     */
    public static function previewOrderNumber(string $prefix, string $pattern, int $digits): string
    {
        return static::formatOrderNumber($prefix, $pattern, $digits, 1);
    }

    protected static function formatOrderNumber(string $prefix, string $pattern, int $digits, int $seq): string
    {
        $prefix = $prefix !== '' ? $prefix : 'BD';
        $digits = max(3, min(10, $digits ?: 5));
        $seqStr = str_pad((string) $seq, $digits, '0', STR_PAD_LEFT);

        return match ($pattern) {
            'PREFIX-YEARMONTH-SEQ'  => "{$prefix}-" . date('Ym') . "-{$seqStr}",
            'PREFIX-SEQ'            => "{$prefix}-{$seqStr}",
            'PREFIX-YEAR-MONTH-SEQ' => "{$prefix}-" . date('Y') . '-' . date('m') . "-{$seqStr}",
            default                 => "{$prefix}-" . date('Y') . "-{$seqStr}", // PREFIX-YEAR-SEQ
        };
    }

    public function updateStatus(string $status, string $note = '', string $changedBy = 'admin', ?int $changedById = null): void
    {
        $previous = $this->status;
        $this->update(['status' => $status]);

        OrderStatusHistory::create([
            'order_id'       => $this->id,
            'status'         => $status,
            'note'           => $note,
            'changed_by'     => $changedBy,
            'changed_by_id'  => $changedById,
            'created_at'     => now(),
        ]);

        match ($status) {
            'delivered' => $this->handleDelivered(),
            'cancelled' => $previous !== 'cancelled' ? $this->handleCancelled() : null,
            'returned'  => $this->handleReturned(),
            'refunded'  => $this->clawbackSeller('Order refunded'),
            default     => null,
        };

        if (in_array($status, ['delivered', 'returned', 'cancelled'], true) || in_array($previous, ['delivered'], true)) {
            $this->refreshProductStats();
        }

        $this->sendStatusSms($status);
    }

    /** Keeps products.total_sales / sellers.total_sales in step with delivered orders. */
    public function refreshProductStats(): void
    {
        try {
            $this->items()->with('product:id,seller_id')->get()->pluck('product')->filter()->unique('id')->each->refreshStats();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function handleReturned(): void
    {
        $this->user?->incrementFailedDelivery();
        $this->clawbackSeller('Order returned');
    }

    private function handleDelivered(): void
    {
        $this->update(['delivered_at' => now()]);
        $this->user?->increment('completed_orders');
        $this->payReferralBonus();

        $this->creditSeller();
    }

    /** Pay the seller's share when the order is delivered – exactly once, with a ledger entry. */
    public function creditSeller(): void
    {
        $amount = (float) $this->seller_payout;
        if (!$this->seller_id || $amount <= 0 || $this->seller_credited_at) {
            return;
        }

        DB::transaction(function () use ($amount) {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            if ($order->seller_credited_at) {
                return; // another request already paid it
            }
            $seller = Seller::whereKey($order->seller_id)->lockForUpdate()->first();
            if (!$seller) {
                return;
            }
            $before = (float) $seller->balance;
            $seller->increment('balance', $amount);
            Transaction::create([
                'seller_id'      => $seller->id,
                'reference_id'   => $order->id,
                'reference_type' => 'order_earning',
                'type'           => 'credit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $before + $amount,
                'description'    => "Earning – order #{$order->order_number}",
                'status'         => 'completed',
            ]);
            $order->update(['seller_credited_at' => now()]);
            $this->seller_credited_at = $order->seller_credited_at;
        });
    }

    /**
     * Take back the seller's share when a DELIVERED order is refunded/returned.
     * Never more than was credited, never twice. The balance may go negative (seller owes the platform).
     */
    public function clawbackSeller(string $reason = 'Order returned'): void
    {
        if (!$this->seller_id || !$this->seller_credited_at) {
            return;
        }

        $amount = round((float) $this->seller_payout - (float) $this->seller_payout_reversed, 2);
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount, $reason) {
            $order  = static::whereKey($this->id)->lockForUpdate()->first();
            $amount = round((float) $order->seller_payout - (float) $order->seller_payout_reversed, 2);
            if ($amount <= 0) {
                return;
            }
            $seller = Seller::whereKey($order->seller_id)->lockForUpdate()->first();
            if (!$seller) {
                return;
            }
            $before = (float) $seller->balance;
            $seller->decrement('balance', $amount);
            Transaction::create([
                'seller_id'      => $seller->id,
                'reference_id'   => $order->id,
                'reference_type' => 'order_payout_reversal',
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $before - $amount,
                'description'    => "{$reason} – payout reversed for order #{$order->order_number}",
                'status'         => 'completed',
            ]);
            $order->update(['seller_payout_reversed' => (float) $order->seller_payout_reversed + $amount]);

            $seller->notifyEvent('payout_reversed', 'Payout reversed', "BDT " . number_format($amount, 0) . " was deducted: {$reason} (order #{$order->order_number}).", ['order_id' => $order->id]);
        });
    }

    /** New order / cancellation messages for the seller (in-app + SMS template). */
    public function notifySeller(string $event): void
    {
        if (!$this->seller_id) {
            return;
        }
        $seller = Seller::find($this->seller_id);
        if (!$seller) {
            return;
        }

        $vars = [
            'order_number' => $this->order_number,
            'total_amount' => 'BDT ' . number_format((float) $this->total_amount, 0),
            'payout'       => 'BDT ' . number_format((float) $this->seller_payout, 0),
        ];

        if ($event === 'new') {
            $seller->notifyEvent(
                'new_order', 'New order', "#{$this->order_number} – {$vars['total_amount']} (your share {$vars['payout']})",
                ['order_id' => $this->id, 'url' => '/seller/dashboard'],
                'seller_new_order', $vars, 'sms_on_seller_new_order',
                '{seller_shop}: new order #{order_number} ({total_amount}). Please confirm it from your seller dashboard.'
            );
        } elseif ($event === 'cancelled') {
            $seller->notifyEvent(
                'order_cancelled', 'Order cancelled', "#{$this->order_number} was cancelled.",
                ['order_id' => $this->id, 'url' => '/seller/dashboard'],
                'seller_order_cancelled', $vars, 'sms_on_seller_new_order',
                '{seller_shop}: order #{order_number} was cancelled. Do not ship it.'
            );
        }
    }

    /** Settings → E-Commerce → Referral: the referrer earns the bonus when the new customer's FIRST order is delivered. */
    private function payReferralBonus(): void
    {
        $user = $this->user;
        $bonus = (float) setting('referral_bonus_amount', 50);
        if (!$user || !$user->referred_by || $bonus <= 0 || !setting('enable_referral', true)) {
            return;
        }

        try {
            DB::transaction(function () use ($user, $bonus) {
                $already = Transaction::where('reference_type', 'referral_bonus')->where('reference_id', $user->id)->exists();
                if ($already || $user->fresh()->completed_orders > 1) {
                    return;
                }
                $referrer = User::whereKey($user->referred_by)->lockForUpdate()->first();
                if (!$referrer || $referrer->id === $user->id) {
                    return;
                }
                $before = (float) $referrer->wallet_balance;
                $referrer->increment('wallet_balance', $bonus);
                Transaction::create([
                    'user_id'        => $referrer->id,
                    'reference_id'   => $user->id,
                    'reference_type' => 'referral_bonus',
                    'type'           => 'credit',
                    'amount'         => $bonus,
                    'balance_before' => $before,
                    'balance_after'  => $before + $bonus,
                    'description'    => "Referral bonus – {$user->name} completed a first order",
                    'status'         => 'completed',
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('Referral bonus failed: ' . $e->getMessage());
        }
    }

    private function handleCancelled(): void
    {
        $this->update(['cancelled_at' => now()]);

        if ($this->cancelled_by !== 'seller') {
            $this->notifySeller('cancelled');
        }

        if ($this->cancelled_by === 'user') {
            $this->user?->incrementCancelCount();
        }

        $this->restoreStockAndCoupon();

        if ($this->payment_method === 'wallet' && $this->payment_status === 'paid') {
            $this->processRefund();
        }
    }

    /** Give reserved stock and the coupon use back when an order is cancelled. */
    private function restoreStockAndCoupon(): void
    {
        foreach ($this->items as $item) {
            if ($item->flash_sale_product_id) {
                FlashSaleProduct::whereKey($item->flash_sale_product_id)->where('sold_count', '>=', $item->quantity)->decrement('sold_count', $item->quantity);
            }
            if ($item->variant_id) {
                ProductVariant::whereKey($item->variant_id)->increment('quantity', $item->quantity);
            } else {
                Product::whereKey($item->product_id)->increment('quantity', $item->quantity);
            }
        }

        if ($this->coupon_id) {
            Coupon::whereKey($this->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
        }
    }

    public function processRefund(): void
    {
        if ($this->payment_status === 'refunded') return; // never refund twice

        $user   = User::whereKey($this->user_id)->lockForUpdate()->first();
        $before = (float) ($user?->wallet_balance ?? 0);
        $user?->increment('wallet_balance', $this->total_amount);
        $this->update(['payment_status' => 'refunded']);

        Transaction::create([
            'balance_before' => $before,
            'balance_after'  => $before + (float) $this->total_amount,
            'user_id'        => $this->user_id,
            'reference_id'   => $this->id,
            'reference_type' => 'order_refund',
            'type'           => 'credit',
            'amount'         => $this->total_amount,
            'description'    => "Order #{$this->order_number} Refund",
            'status'         => 'completed',
        ]);
    }

    /**
     * Status-change SMS through the admin-editable template + the Settings → SMS toggles.
     * "pending" (order placed) is sent by the controller AFTER the checkout commits,
     * never from inside the transaction.
     */
    /** Customer email for order events (placed / confirmed / shipped / delivered / cancelled). Never throws; needs the customer's email + working mail settings. */
    public function emailCustomer(string $status): void
    {
        $toggle = $status === 'placed' ? 'email_on_order_placed' : 'email_on_order_status';
        $text = [
            'placed'    => 'আপনার অর্ডারটি গ্রহণ করা হয়েছে।',
            'confirmed' => 'আপনার অর্ডারটি নিশ্চিত করা হয়েছে।',
            'shipped'   => 'আপনার অর্ডারটি পাঠানো হয়েছে।' . ($this->courier_tracking_id ? " ট্র্যাকিং নম্বর: {$this->courier_tracking_id}" : ''),
            'delivered' => 'আপনার অর্ডারটি ডেলিভারি হয়েছে। কেনাকাটার জন্য ধন্যবাদ!',
            'cancelled' => 'আপনার অর্ডারটি বাতিল করা হয়েছে।' . ($this->cancellation_reason ? " কারণ: {$this->cancellation_reason}" : ''),
        ][$status] ?? null;
        $user = $this->user;
        if (!$text || !$user || !$user->email || !setting($toggle, true)) {
            return;
        }
        try {
            $site = rtrim((string) (setting('shop_url') ?: config('app.frontend_url') ?: config('app.url')), '/');
            $user->notify(new \App\Notifications\CustomerMailNotification(
                "অর্ডার #{$this->order_number}",
                [$text, "অর্ডার নম্বর: {$this->order_number}", 'মোট: ৳' . number_format((float) $this->total_amount, 0)],
                'অর্ডার দেখুন', $site . '/order/track?id=' . urlencode($this->order_number),
            ));
        } catch (\Throwable $e) {
            Log::warning('Order email failed: ' . $e->getMessage());
        }
    }

    private function sendStatusSms(string $status): void
    {
        if ($status !== 'pending') {
            $this->emailCustomer($status);
        }
        if ($status === 'pending') {
            return;
        }

        $toggle = [
            'confirmed' => 'sms_on_order_confirmed',
            'shipped'   => 'sms_on_order_shipped',
            'delivered' => 'sms_on_order_delivered',
            'cancelled' => 'sms_on_order_cancelled',
        ][$status] ?? null;

        try {
            $sms = app(\App\Services\SmsService::class);
            $sms->sendTemplate("order_{$status}", $this->shipping_phone, $sms->orderVars($this), $this, null, $toggle);
        } catch (\Exception $e) {
            Log::warning('Order SMS failed: ' . $e->getMessage());
        }
    }

    /** Settings → Delivery → "Automatic Order Confirmation": COD, or online/wallet orders already paid. */
    public function autoConfirmIfEnabled(): void
    {
        if (
            $this->status === 'pending'
            && setting('auto_confirm_order', false)
            && ($this->payment_method === 'cod' || $this->payment_status === 'paid')
        ) {
            $this->updateStatus('confirmed', 'Auto-confirmed', 'system');
        }
    }

    /** SteadFast delivery_status → [courier_status (DB enum), order status to move to]. */
    public const COURIER_STATUS_MAP = [
        'pending'           => ['courier_status' => 'pending',           'order_status' => null],
        'in_review'         => ['courier_status' => 'in_transit',        'order_status' => null],
        'hold'              => ['courier_status' => 'in_transit',        'order_status' => null],
        'delivered'         => ['courier_status' => 'delivered',         'order_status' => 'delivered'],
        'partial_delivered' => ['courier_status' => 'partial_delivered', 'order_status' => 'delivered'],
        'cancelled'         => ['courier_status' => 'cancelled',         'order_status' => null],
        'unknown'           => ['courier_status' => 'unknown',           'order_status' => null],
    ];

    /**
     * Apply a courier delivery status (from a webhook or the polling command).
     * Goes through updateStatus() so delivery credits the seller, updates the customer
     * counters, writes history and sends the SMS, exactly like an admin change would.
     */
    public function applyCourierStatus(string $deliveryStatus, string $source = 'courier', array $raw = []): void
    {
        $deliveryStatus = strtolower($deliveryStatus);
        $map = self::COURIER_STATUS_MAP[$deliveryStatus]
            ?? ['courier_status' => 'unknown', 'order_status' => null];

        $courierChanged = $this->courier_status !== $map['courier_status'];
        $this->update([
            'courier_status'   => $map['courier_status'],
            'courier_response' => array_merge($this->courier_response ?? [], ['last_' . $source => $raw]),
        ]);

        if ($map['order_status'] && $this->status !== $map['order_status']
            && !in_array($this->status, ['cancelled', 'returned'], true)) {
            $this->updateStatus($map['order_status'], "{$source}: {$deliveryStatus}", 'courier');
            return;
        }

        if ($map['courier_status'] === 'cancelled' && !$this->cancelled_at) {
            $this->update([
                'cancelled_at'        => now(),
                'cancellation_reason' => $raw['note'] ?? 'Cancelled by courier',
                'cancelled_by'        => 'courier',
            ]);
        }

        if ($courierChanged) {
            OrderStatusHistory::create([
                'order_id'      => $this->id,
                'status'        => 'courier_' . $map['courier_status'],
                'note'          => "{$source}: {$deliveryStatus}",
                'changed_by'    => 'courier',
                'changed_by_id' => null,
                'created_at'    => now(),
            ]);
        }
    }

    public function bookCourier(?int $merchantId = null): array
    {
        $merchant = $merchantId ? CourierMerchant::findOrFail($merchantId) : CourierMerchant::where('is_default', true)->where('is_active', true)->firstOrFail();
        $result = app(\App\Services\CourierService::class)->book($merchant, $this);

        if ($result['success']) {
            $this->update([
                'courier_merchant_id' => $merchant->id,
                'courier_tracking_id' => $result['tracking_id'],
                'courier_tracking_url'=> $result['tracking_url'] ?? null,
                'courier_status'      => 'booked',
                'courier_response'    => $result,
                'courier_booked_at'   => now(),
            ]);

            $this->updateStatus('shipped', "Courier Booked: {$merchant->name} - {$result['tracking_id']}");
            $merchant->increment('total_deliveries');
        }

        return $result;
    }

    public function canBookCourier(): bool
    {
        //dd($this->status, $this->courier_tracking_id);
        return in_array($this->status, ['confirmed', 'processing']) && empty($this->courier_tracking_id);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function getStatusLabelBnAttribute(): string
    {
        return [
            'pending'         => 'Pending',
            'confirmed'       => 'Confirmed',
            'processing'      => 'Processing',
            'shipped'         => 'Shipped',
            'out_for_delivery'=> 'Out for Delivery',
            'delivered'       => 'Delivered',
            'cancelled'       => 'Cancelled',
            'returned'        => 'Returned',
            'refunded'        => 'Refunded',
        ][$this->status] ?? $this->status;
    }
}
