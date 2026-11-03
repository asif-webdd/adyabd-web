<?php

namespace Tests\Feature;

use App\Models\{Order, Seller, SellerWithdrawal, Transaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\StoreFixtures;
use Tests\TestCase;

class SellerPayoutTest extends TestCase
{
    use RefreshDatabase, StoreFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStore();
    }

    private function deliveredOrder(): Order
    {
        $p = $this->productOf(1);
        $id = $this->placeOrder([['product_id' => $p->id, 'quantity' => 1]])->assertStatus(201)->json('data.orders.0.id')
            ?? null;
        return Order::findOrFail($id);
    }

    public function test_payout_is_credited_once_and_clawed_back_once(): void
    {
        $order = $this->deliveredOrder();
        $seller = Seller::find($order->seller_id);
        $before = (float) $seller->balance;

        $order->update(['status' => 'shipped']);
        $order->updateStatus('delivered', 't', 'system');
        $order->updateStatus('delivered', 't', 'system');
        $this->assertEqualsWithDelta($before + (float) $order->seller_payout, (float) $seller->fresh()->balance, 0.01);
        $this->assertSame(1, Transaction::where('reference_type', 'order_earning')->where('reference_id', $order->id)->count());

        $order->refresh();
        $order->clawbackSeller('Return refunded');
        $order->clawbackSeller('again');
        $this->assertEqualsWithDelta($before, (float) $seller->fresh()->balance, 0.01);
        $this->assertSame(1, Transaction::where('reference_type', 'order_payout_reversal')->where('reference_id', $order->id)->count());

        $order->updateStatus('returned', 't', 'admin');
        $this->assertEqualsWithDelta($before, (float) $seller->fresh()->balance, 0.01, 'no double reversal');
    }

    public function test_withdrawal_rules_and_admin_processing(): void
    {
        $seller = Seller::whereNotNull('user_id')->where('status', 'active')->firstOrFail();
        $seller->update(['balance' => 5000]);
        Passport::actingAs($seller->user);
        $body = fn($amount, $m = 'bkash') => ['amount' => $amount, 'method' => $m, 'account' => '01711000000'];

        $this->postJson('/api/v1/seller/withdrawals', $body(100))->assertStatus(422);
        $this->postJson('/api/v1/seller/withdrawals', $body(999999))->assertStatus(422);
        $res = $this->postJson('/api/v1/seller/withdrawals', $body(1000))->assertStatus(201);
        $this->assertEqualsWithDelta(4000, (float) $seller->fresh()->balance, 0.01, 'amount is held at once');

        $this->postJson('/api/v1/seller/withdrawals', $body(600, 'nagad'))->assertStatus(201);
        $this->postJson('/api/v1/seller/withdrawals', $body(600, 'bank'))->assertStatus(201);
        $this->postJson('/api/v1/seller/withdrawals', $body(600))->assertStatus(422); // 4th pending

        $paid = SellerWithdrawal::findOrFail($res->json('data.id'));
        $this->assertTrue($paid->markPaid('TRX123', 1));
        $this->assertFalse($paid->markPaid('TRX999', 1), 'cannot be paid twice');

        $rej = SellerWithdrawal::where('seller_id', $seller->id)->where('amount', 600)->first();
        $bal = (float) $seller->fresh()->balance;
        $this->assertTrue($rej->reject('Wrong number', 1));
        $this->assertEqualsWithDelta($bal + 600, (float) $seller->fresh()->balance, 0.01, 'rejection returns the money');
        $this->assertFalse($rej->reject('x', 1));

        $this->getJson('/api/v1/seller/notifications')->assertOk();
        $this->postJson('/api/v1/seller/notifications/read')->assertOk();
        $this->assertSame(0, (int) $this->getJson('/api/v1/seller/dashboard')->json('data.unread_notifications'));
    }
}
