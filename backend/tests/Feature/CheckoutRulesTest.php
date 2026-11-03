<?php

namespace Tests\Feature;

use App\Models\{Coupon, FlashSale, FlashSaleProduct, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StoreFixtures;
use Tests\TestCase;

class CheckoutRulesTest extends TestCase
{
    use RefreshDatabase, StoreFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStore();
    }

    public function test_flash_sale_price_is_charged_limited_and_restored_on_cancel(): void
    {
        $p = $this->productOf(1);
        $fs = FlashSale::create(['title' => 'T', 'title_bn' => 'T', 'discount_percentage' => 50,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'is_active' => true]);
        $fsp = FlashSaleProduct::create(['flash_sale_id' => $fs->id, 'product_id' => $p->id,
            'discount_percentage' => 50, 'special_price' => 100, 'quantity_limit' => 3, 'sold_count' => 0]);

        $res = $this->placeOrder([['product_id' => $p->id, 'quantity' => 2]])->assertStatus(201);
        $this->assertEqualsWithDelta(200, (float) $res->json('data.subtotal'), 0.01);
        $this->assertSame(2, (int) $fsp->fresh()->sold_count);

        $this->placeOrder([['product_id' => $p->id, 'quantity' => 2]])->assertStatus(422);

        $order = Order::find($res->json('data.id'));
        $order->update(['cancelled_by' => 'user']);
        $order->updateStatus('cancelled', 't', 'user');
        $this->assertSame(0, (int) $fsp->fresh()->sold_count);
    }

    public function test_disabled_payment_and_delivery_options_are_refused(): void
    {
        $p = $this->productOf(2);
        $this->setting('enable_zinipay', '0');
        $this->placeOrder([['product_id' => $p->id, 'quantity' => 1]], ['payment_method' => 'zinipay'])->assertStatus(422);

        $this->setting('enable_zinipay', '1');
        $this->setting('enable_express_shipping', '0');
        $this->placeOrder([['product_id' => $p->id, 'quantity' => 1]], ['delivery_type' => 'express'])->assertStatus(422);
    }

    public function test_seller_coupon_discounts_only_that_sellers_items(): void
    {
        $p1 = $this->productOf(1);
        $p2 = $this->productOf(2);
        Coupon::create(['code' => 'S2ONLY', 'title' => 't', 'type' => 'fixed', 'value' => 50, 'min_order_amount' => 0,
            'is_active' => true, 'seller_id' => 2, 'usage_limit_per_user' => 0]);

        $this->placeOrder([['product_id' => $p1->id, 'quantity' => 1]], ['coupon_code' => 'S2ONLY'])->assertStatus(422);

        $res = $this->placeOrder([['product_id' => $p1->id, 'quantity' => 1], ['product_id' => $p2->id, 'quantity' => 1]],
            ['coupon_code' => 'S2ONLY'])->assertStatus(201);
        $orders = collect($res->json('data.orders'))->mapWithKeys(fn($o) => [$o['seller_id'] => Order::find($o['id'])]);
        $this->assertCount(2, $orders, 'one order per seller');
        $this->assertEqualsWithDelta(50, (float) $orders[2]->coupon_discount, 0.01);
        $this->assertEquals(0, (float) $orders[1]->coupon_discount);
    }
}
