<?php

namespace Tests\Concerns;

use App\Models\{Address, Product, Setting, User};
use Illuminate\Support\Facades\{Cache, Http};
use Laravel\Passport\Passport;

/** Seeds the demo store and gives tests a logged-in customer + a few purchasable products. */
trait StoreFixtures
{
    protected User $customer;
    protected int $addressId;

    protected function bootStore(): void
    {
        $this->seed();
        Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED']]], 200)]);

        $this->customer = User::where('phone', '01711000001')->firstOrFail();
        $this->addressId = Address::where('user_id', $this->customer->id)->value('id')
            ?? Address::create([
                'user_id' => $this->customer->id, 'name' => 'T', 'phone' => '01711000001',
                'address_line' => 'x', 'division_id' => 1, 'district_id' => 1, 'type' => 'home',
            ])->id;
    }

    protected function productOf(int $sellerId): Product
    {
        return Product::where('seller_id', $sellerId)->where('is_active', true)->where('quantity', '>', 20)->firstOrFail();
    }

    protected function setting(string $key, $value): void
    {
        Setting::set($key, $value);
        Cache::flush();
    }

    protected function placeOrder(array $items, array $extra = [])
    {
        Passport::actingAs($this->customer);
        return $this->postJson('/api/v1/orders', array_merge([
            'address_id' => $this->addressId, 'payment_method' => 'cod', 'items' => $items,
        ], $extra));
    }
}
