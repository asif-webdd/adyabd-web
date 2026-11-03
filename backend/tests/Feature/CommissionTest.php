<?php

namespace Tests\Feature;

use App\Filament\Pages\ReportPage;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\{Order, Seller};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StoreFixtures;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase, StoreFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStore();
    }

    private function order(): Order
    {
        $p = $this->productOf(1);
        $id = $this->placeOrder([['product_id' => $p->id, 'quantity' => 1]])->assertStatus(201)->json('data.orders.0.id');
        return Order::findOrFail($id);
    }

    public function test_blank_seller_rate_follows_the_global_rate_and_own_rate_wins(): void
    {
        $seller = Seller::findOrFail(1);

        $seller->update(['commission_rate' => null]);
        $this->setting('platform_commission', 20);
        $o = $this->order();
        $this->assertEqualsWithDelta(round(((float) $o->subtotal - (float) $o->discount_amount - (float) $o->coupon_discount) * 0.20, 2), (float) $o->commission_amount, 0.02);

        $seller->update(['commission_rate' => 5]);
        $o2 = $this->order();
        $this->assertEqualsWithDelta(round(((float) $o2->subtotal - (float) $o2->discount_amount - (float) $o2->coupon_discount) * 0.05, 2), (float) $o2->commission_amount, 0.02);

        // accessor: blank shows the effective (global) rate, raw value stays NULL
        $seller->update(['commission_rate' => null]);
        $seller = $seller->fresh();
        $this->assertSame(20.0, $seller->commission_rate);
        $this->assertNull($seller->getRawOriginal('commission_rate'));
    }

    public function test_setting_status_refunded_takes_the_payout_back_once(): void
    {
        $order = $this->order();
        $seller = Seller::find($order->seller_id);
        $before = (float) $seller->balance;

        $order->update(['status' => 'shipped']);
        $order->updateStatus('delivered', 't', 'system');
        $this->assertEqualsWithDelta($before + (float) $order->seller_payout, (float) $seller->fresh()->balance, 0.01);

        $order->refresh()->updateStatus('refunded', 't', 'admin');
        $this->assertEqualsWithDelta($before, (float) $seller->fresh()->balance, 0.01);
        $order->refresh()->updateStatus('refunded', 't', 'admin');
        $this->assertEqualsWithDelta($before, (float) $seller->fresh()->balance, 0.01, 'never twice');
    }

    public function test_report_and_dashboard_count_only_delivered_non_refunded_commission(): void
    {
        $pending = $this->order();                       // not delivered
        $done = $this->order();
        $done->update(['status' => 'shipped']);
        $done->updateStatus('delivered', 't', 'system');
        $refunded = $this->order();
        $refunded->update(['status' => 'shipped']);
        $refunded->updateStatus('delivered', 't', 'system');
        $refunded->refresh()->update(['payment_status' => 'refunded']);

        $page = new ReportPage();
        $page->date_from = now()->subDay()->toDateString();
        $page->date_to = now()->addDay()->toDateString();
        $page->seller_id = null;
        $row = $page->getTopSellers()->firstWhere('name', Seller::find(1)->shop_name);
        $this->assertEqualsWithDelta((float) $done->fresh()->commission_amount, (float) $row['commission'], 0.01);
        $this->assertEqualsWithDelta((float) $done->fresh()->seller_payout, (float) $row['payout'], 0.01);
        $this->assertGreaterThan(0, $pending->commission_amount);

        $w = new StatsOverviewWidget();
        $m = new \ReflectionMethod($w, 'getStats');
        $m->setAccessible(true);
        $labels = collect($m->invoke($w))->map(fn ($s) => (string) $s->getLabel())->all();
        $this->assertTrue(in_array('Commission Earned', $labels, true), implode(',', $labels));
    }
}