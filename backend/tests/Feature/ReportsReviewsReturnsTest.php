<?php

namespace Tests\Feature;

use App\Filament\Pages\ReportPage;
use App\Filament\Resources\{ProductResource\Pages\ListProducts, ReturnRequestResource\Pages\ManageReturnRequests, ReviewResource\Pages\ListReviews, OrderResource\Pages\ListOrders};
use App\Models\{AdminUser, Order, Product, Review, ReturnRequest, Seller, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsReviewsReturnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_review_stats_and_notifications(): void
    {
        $user = User::where('phone', '01711000001')->firstOrFail();
        $p = Product::where('is_active', true)->firstOrFail();
        Review::where('product_id', $p->id)->forceDelete();
        $r1 = Review::create(['user_id' => $user->id, 'product_id' => $p->id, 'rating' => 5, 'review' => 'great', 'is_approved' => false]);
        $this->assertSame(0, (int) $p->fresh()->total_reviews, 'unapproved reviews do not count');

        $this->actingAs(AdminUser::first(), 'admin');
        Livewire::test(ListReviews::class)->callTableAction('approve', $r1);
        $p->refresh();
        $this->assertSame(1, (int) $p->total_reviews);
        $this->assertEqualsWithDelta(5.0, (float) $p->rating, 0.001);
        $this->assertSame(1, $user->notifications()->where('data->kind', 'review_approved')->count());

        $r1->update(['admin_reply' => 'Thanks!']);
        $this->assertSame(1, $user->notifications()->where('data->kind', 'review_reply')->count());

        Livewire::test(ListReviews::class)->callTableAction('reject', $r1->fresh());
        $this->assertFalse((bool) $r1->fresh()->is_approved);
        $this->assertSame(0, (int) $p->fresh()->total_reviews);
    }

    public function test_sales_counter_follows_delivered_orders(): void
    {
        $order = Order::where('status', '!=', 'delivered')->whereHas('items')->with('items')->firstOrFail();
        $order->update(['status' => 'shipped']);
        $pid = $order->items->first()->product_id;
        $before = (int) Product::find($pid)->total_sales;
        $order->updateStatus('delivered', 't', 'system');
        $this->assertTrue((int) Product::find($pid)->total_sales >= $before + (int) $order->items->where('product_id', $pid)->sum('quantity'));
    }

    public function test_storefront_sorting_by_rating_and_popularity_does_not_crash(): void
    {
        $this->getJson('/api/v1/products?sort=rating')->assertOk();
        $this->getJson('/api/v1/products?sort=popular')->assertOk();
    }

    public function test_admin_tables_sort_by_rating_and_sales(): void
    {
        $this->actingAs(AdminUser::first(), 'admin');
        Livewire::test(ListProducts::class)->sortTable('rating')->assertSuccessful();
        Livewire::test(ListProducts::class)->sortTable('total_sales', 'desc')->assertSuccessful();
    }

    public function test_return_flow_notifies_the_customer(): void
    {
        $order = Order::whereNotNull('user_id')->firstOrFail();
        $order->update(['status' => 'delivered', 'payment_status' => 'paid']);
        $rr = ReturnRequest::create(['user_id' => $order->user_id, 'order_id' => $order->id, 'type' => 'return', 'reason' => 'damaged', 'description' => 'broken', 'refund_method' => 'wallet', 'status' => 'pending']);
        $this->actingAs(AdminUser::first(), 'admin');
        Livewire::test(ManageReturnRequests::class)->callTableAction('approve', $rr, ['admin_note' => 'ok']);
        $this->assertSame('approved', $rr->fresh()->status);
        $before = (float) User::find($order->user_id)->wallet_balance;
        Livewire::test(ManageReturnRequests::class)->callTableAction('refund', $rr->fresh());
        $this->assertSame('refunded', $rr->fresh()->status);
        $this->assertEqualsWithDelta($before + (float) $order->total_amount, (float) User::find($order->user_id)->wallet_balance, 0.01);
        $u = User::find($order->user_id);
        $this->assertTrue($u->notifications()->where('data->kind', 'return_approved')->exists());
        $this->assertTrue($u->notifications()->where('data->kind', 'return_refunded')->exists());
    }

    public function test_report_filter_changes_the_numbers_and_exports_csv(): void
    {
        $this->actingAs(AdminUser::first(), 'admin');
        $all = Livewire::test(ReportPage::class)->set('date_from', '2000-01-01')->set('date_to', now()->toDateString());
        $none = Livewire::test(ReportPage::class)->set('date_from', '2000-01-01')->set('date_to', '2000-01-02');
        $this->assertTrue($all->instance()->getStats()['totalOrders'] > 0);
        $this->assertSame(0, $none->instance()->getStats()['totalOrders']);
        $all->call('exportProducts')->assertFileDownloaded();
        $all->call('exportSummary')->assertFileDownloaded();
    }
}
