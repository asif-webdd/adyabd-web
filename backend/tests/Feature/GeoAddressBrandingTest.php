<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\DeliveryCharge;
use App\Models\District;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Upazila;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class GeoAddressBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_full_geo_data_is_present_and_idempotent(): void
    {
        $this->assertSame(8, \App\Models\Division::count());
        $this->assertSame(64, District::count());
        $this->assertTrue(Upazila::count() >= 490, 'upazila count ' . Upazila::count());

        $before = [District::count(), Upazila::count()];
        (new \Database\Seeders\BangladeshGeoSeeder())->run();
        $this->assertSame($before, [District::count(), Upazila::count()]);

        $ctg = District::where('name_bn', 'চট্টগ্রাম')->firstOrFail();
        $this->assertTrue(Upazila::where('district_id', $ctg->id)->where('name_bn', 'হাটহাজারী')->exists());
        $this->assertTrue(Upazila::where('district_id', $ctg->id)->count() >= 30);
    }

    public function test_geo_api_lists_all_districts_and_their_upazilas(): void
    {
        $districts = $this->getJson('/api/v1/geo/districts')->assertOk()->json('data');
        $this->assertSame(64, count($districts));
        $d = District::where('name_bn', 'কক্সবাজার')->firstOrFail();
        $up = $this->getJson('/api/v1/geo/upazilas?district_id=' . $d->id)->assertOk()->json('data');
        $this->assertSame(8, count($up));
    }

    public function test_address_needs_only_five_fields_and_derives_division(): void
    {
        $user = User::where('phone', '01711000001')->firstOrFail();
        Passport::actingAs($user);
        $d = District::where('name_bn', 'রংপুর')->firstOrFail();
        $u = Upazila::where('district_id', $d->id)->firstOrFail();

        $this->postJson('/api/v1/addresses', ['name' => 'A', 'phone' => '01800000000', 'address_line' => 'x'])->assertStatus(422);

        $r = $this->postJson('/api/v1/addresses', [
            'name' => 'A', 'phone' => '01800000000', 'district_id' => $d->id, 'upazila_id' => $u->id, 'address_line' => 'House 1',
        ])->assertStatus(201);
        $a = Address::findOrFail($r->json('data.id'));
        $this->assertSame($d->division_id, $a->division_id);
        $this->assertSame('home', $a->type);

        // upazila from another district is rejected
        $other = Upazila::where('district_id', '!=', $d->id)->firstOrFail();
        $this->postJson('/api/v1/addresses', [
            'name' => 'A', 'phone' => '01800000000', 'district_id' => $d->id, 'upazila_id' => $other->id, 'address_line' => 'x',
        ])->assertStatus(422);
    }

    public function test_delivery_charge_matches_alternative_spellings(): void
    {
        DeliveryCharge::query()->delete();
        DeliveryCharge::create(['area_name' => 'Chittagong', 'area_type' => 'district', 'charge' => 123, 'is_active' => true, 'sort_order' => 1]);
        $this->assertSame(123.0, DeliveryCharge::resolveCharge('Chattogram'));
    }

    public function test_invoice_shows_store_logo_instead_of_app_name(): void
    {
        @mkdir(storage_path('app/public/settings'), 0777, true);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        file_put_contents(storage_path('app/public/settings/test-logo.png'), $png);
        Setting::set('shop_logo', 'settings/test-logo.png', 'appearance');
        cache()->flush();

        $this->assertTrue(str_starts_with(shop_logo_src(), 'data:image/png;base64,'));
        $order = Order::firstOrFail();
        $html = view('filament.orders.invoice', ['order' => $order->load(['user', 'seller.user', 'items', 'courierMerchant'])])->render();
        $this->assertTrue(str_contains($html, 'class="shop-logo"'));
        @unlink(storage_path('app/public/settings/test-logo.png'));
    }

    public function test_card_header_hook_is_rendered(): void
    {
        $html = view('filament.hooks.card-headers')->render();
        $this->assertTrue(str_contains($html, 'fi-section-header'));
    }
}
