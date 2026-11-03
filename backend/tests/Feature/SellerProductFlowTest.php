<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\{AdminUser, Product, ProductImage, Seller, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\TestCase;

class SellerProductFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_add_to_cart_twice_accumulates_quantity(): void
    {
        $user = User::where('phone', '01711000001')->firstOrFail();
        $p = Product::where('is_active', true)->where('quantity', '>', 5)->firstOrFail();
        Passport::actingAs($user);
        $this->postJson('/api/v1/cart', ['product_id' => $p->id, 'quantity' => 1])->assertStatus(200);
        $this->postJson('/api/v1/cart', ['product_id' => $p->id, 'quantity' => 2])->assertStatus(200);
        $this->assertSame(3, (int) \App\Models\Cart::where('user_id', $user->id)->where('product_id', $p->id)->value('quantity'));
        $this->postJson('/api/v1/cart', ['product_id' => $p->id, 'quantity' => $p->quantity])->assertStatus(422);
    }

    public function test_product_without_unit_gets_default(): void
    {
        $p = Product::create(['name' => 'X', 'slug' => 'x-unit', 'category_id' => 1, 'seller_id' => Seller::first()->id, 'price' => 10, 'quantity' => 1, 'unit' => null]);
        $this->assertSame('piece', $p->fresh()->unit);
    }

    public function test_seller_gallery_admin_alert_and_approval(): void
    {
        Storage::fake('public');
        $seller = Seller::whereNotNull('user_id')->where('status', 'active')->firstOrFail();
        Passport::actingAs($seller->user);

        $res = $this->post('/api/v1/seller/products', [
            'name' => 'Kettle', 'category_id' => 1, 'price' => 1049, 'quantity' => 10,
            'image' => UploadedFile::fake()->image('main.jpg'),
            'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ], ['Accept' => 'application/json'])->assertStatus(201);
        $id = $res->json('data.id');
        $product = Product::findOrFail($id);
        $this->assertFalse((bool) $product->is_active);
        $this->assertSame(3, $product->images()->count());
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());

        // admin gets a bell notification
        $admin = AdminUser::first();
        $this->assertSame(1, $admin->notifications()->where('data->title', 'Seller product waiting for approval')->count());

        // add + delete gallery images (only own product)
        $this->post("/api/v1/seller/products/{$id}/images", ['gallery' => [UploadedFile::fake()->image('c.jpg')]], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(4, $product->images()->count());
        $primary = $product->images()->where('is_primary', true)->first();
        $this->deleteJson("/api/v1/seller/products/{$id}/images/{$primary->id}")->assertOk();
        $this->assertSame(1, $product->images()->where('is_primary', true)->count(), 'a new primary is chosen');

        // withdrawal alert
        $seller->update(['balance' => 5000]);
        $this->postJson('/api/v1/seller/withdrawals', ['amount' => 1000, 'method' => 'bkash', 'account' => '01711000000'])->assertStatus(201);
        $this->assertSame(1, $admin->notifications()->where('data->title', 'Seller withdrawal request')->count());
    }

    public function test_admin_can_approve_a_pending_seller_product(): void
    {
        $seller = Seller::whereNotNull('user_id')->where('status', 'active')->firstOrFail();
        $p = Product::create(['name' => 'Pending', 'slug' => 'pending-1', 'category_id' => 1, 'seller_id' => $seller->id, 'price' => 50, 'quantity' => 3, 'is_active' => false]);
        $this->actingAs(AdminUser::first(), 'admin');
        Livewire::test(ListProducts::class)->callTableAction('approve', $p);
        $this->assertTrue((bool) $p->fresh()->is_active);
        $this->assertSame(1, $seller->user->notifications()->where('data->kind', 'product_approved')->count());
    }

    public function test_storage_route_serves_uploaded_files_without_symlink(): void
    {
        $dir = storage_path('app/public/testfiles');
        @mkdir($dir, 0777, true);
        file_put_contents($dir . '/hello.txt', 'hi');
        $this->get('/storage/testfiles/hello.txt')->assertOk();
        $this->get('/storage/../../.env')->assertStatus(404);
        $this->get('/storage/testfiles/nope.txt')->assertStatus(404);
        @unlink($dir . '/hello.txt'); @rmdir($dir);
    }

    public function test_seller_can_edit_delete_products_manage_variants_and_profile(): void
    {
        Storage::fake('public');
        $seller = Seller::whereNotNull('user_id')->where('status', 'active')->firstOrFail();
        Passport::actingAs($seller->user);
        $id = $this->postJson('/api/v1/seller/products', ['name' => 'Mug', 'category_id' => 1, 'price' => 200, 'quantity' => 5])->assertStatus(201)->json('data.id');

        // edit
        $this->putJson("/api/v1/seller/products/{$id}", ['price' => 250, 'quantity' => 9, 'short_description' => 'Nice', 'unit' => 'pcs'])->assertOk();
        $p = Product::find($id);
        $this->assertSame(9, (int) $p->quantity);
        $this->assertSame('pcs', $p->unit);
        $this->assertFalse((bool) $p->is_active, 'a seller still cannot self-approve');
        $this->getJson("/api/v1/seller/products/{$id}")->assertOk()->assertJsonPath('data.name', 'Mug');

        // variants: stock becomes the sum, validation applies, other sellers cannot touch it
        $attrs = [['attribute' => 'Color', 'value' => 'Red']];
        $this->postJson("/api/v1/seller/products/{$id}/variants", ['price' => 260, 'quantity' => 3, 'attribute_values' => $attrs])->assertStatus(201);
        $vid = $this->postJson("/api/v1/seller/products/{$id}/variants", ['price' => 270, 'quantity' => 4, 'attribute_values' => [['attribute' => 'Color', 'value' => 'Blue']]])->assertStatus(201)->json('data.id');
        $this->postJson("/api/v1/seller/products/{$id}/variants", ['price' => 270, 'quantity' => 4])->assertStatus(422);
        $p->refresh();
        $this->assertTrue((bool) $p->has_variants);
        $this->assertSame(7, (int) $p->quantity);
        $this->putJson("/api/v1/seller/products/{$id}/variants/{$vid}", ['price' => 275, 'quantity' => 10, 'attribute_values' => $attrs])->assertOk();
        $this->assertSame(13, (int) $p->fresh()->quantity);
        $this->deleteJson("/api/v1/seller/products/{$id}/variants/{$vid}")->assertOk();
        $this->assertSame(3, (int) $p->fresh()->quantity);

        $other = Seller::whereNotNull('user_id')->where('id', '!=', $seller->id)->first();
        if ($other) {
            Passport::actingAs($other->user);
            $this->putJson("/api/v1/seller/products/{$id}", ['price' => 1])->assertStatus(404);
            $this->deleteJson("/api/v1/seller/products/{$id}")->assertStatus(404);
            Passport::actingAs($seller->user);
        }

        // delete
        $this->deleteJson("/api/v1/seller/products/{$id}")->assertOk();
        $this->assertNull(Product::find($id));

        // shop profile: editable fields change, admin-controlled fields do not
        $before = $seller->fresh();
        $this->post('/api/v1/seller/profile', [
            'description' => 'We sell mugs', 'phone' => '01711111111', 'shop_name' => 'HACKED', 'commission_rate' => 0, 'status' => 'active',
            'logo' => UploadedFile::fake()->image('l.png'), 'banner' => UploadedFile::fake()->image('b.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $after = $seller->fresh();
        $this->assertSame('We sell mugs', $after->description);
        $this->assertSame($before->shop_name, $after->shop_name);
        $this->assertSame((string) $before->commission_rate, (string) $after->commission_rate);
        $this->assertNotNull($after->logo);
        Storage::disk('public')->assertExists($after->logo);
        $this->getJson('/api/v1/seller/profile')->assertOk()->assertJsonPath('data.description', 'We sell mugs');
    }
}
