<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SellerWithdrawal extends Model
{
    protected $fillable = [
        'seller_id', 'amount', 'method', 'account', 'status', 'reference',
        'admin_note', 'processed_by', 'processed_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'processed_at' => 'datetime'];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** Admin sent the money. Returns false if it was already processed. */
    public function markPaid(string $reference, ?int $adminId = null): bool
    {
        $done = DB::transaction(function () use ($reference, $adminId) {
            $w = static::whereKey($this->id)->lockForUpdate()->first();
            if ($w->status !== 'pending') {
                return false;
            }
            $w->update(['status' => 'paid', 'reference' => $reference, 'processed_by' => $adminId, 'processed_at' => now()]);
            Transaction::where('reference_type', 'seller_withdrawal')->where('reference_id', $w->id)
                ->update(['status' => 'completed', 'transaction_id' => $reference]);
            return true;
        });

        if ($done) {
            $this->refresh();
            $amount = 'BDT ' . number_format((float) $this->amount, 0);
            $this->seller?->notifyEvent(
                'withdrawal_paid', 'Withdrawal paid', "{$amount} was sent to {$this->account}. Ref: {$this->reference}",
                [], 'seller_withdrawal_paid', ['amount' => $amount, 'reference' => $this->reference, 'account' => $this->account],
                'sms_on_seller_new_order', '{seller_shop}: {amount} was sent to {account}. Ref: {reference}'
            );
        }
        return $done;
    }

    /** Admin refused: the held amount goes back to the seller's balance. */
    public function reject(string $note, ?int $adminId = null): bool
    {
        $done = DB::transaction(function () use ($note, $adminId) {
            $w = static::whereKey($this->id)->lockForUpdate()->first();
            if ($w->status !== 'pending') {
                return false;
            }
            $seller = Seller::whereKey($w->seller_id)->lockForUpdate()->first();
            $before = (float) $seller->balance;
            $seller->increment('balance', $w->amount);

            $w->update(['status' => 'rejected', 'admin_note' => $note, 'processed_by' => $adminId, 'processed_at' => now()]);
            Transaction::where('reference_type', 'seller_withdrawal')->where('reference_id', $w->id)->update(['status' => 'failed']);
            Transaction::create([
                'seller_id' => $seller->id, 'reference_id' => $w->id, 'reference_type' => 'seller_withdrawal_refund',
                'type' => 'refund', 'amount' => $w->amount, 'balance_before' => $before, 'balance_after' => $before + (float) $w->amount,
                'description' => "Withdrawal #{$w->id} rejected – amount returned", 'status' => 'completed',
            ]);
            return true;
        });

        if ($done) {
            $this->refresh();
            $amount = 'BDT ' . number_format((float) $this->amount, 0);
            $this->seller?->notifyEvent(
                'withdrawal_rejected', 'Withdrawal rejected', "{$amount} returned to your balance. Reason: {$this->admin_note}",
                [], 'seller_withdrawal_rejected', ['amount' => $amount, 'reason' => $this->admin_note],
                'sms_on_seller_new_order', '{seller_shop}: your withdrawal of {amount} was rejected ({reason}). The amount is back in your balance.'
            );
        }
        return $done;
    }
}
