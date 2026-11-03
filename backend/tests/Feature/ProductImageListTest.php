<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_list_returns_full_url_even_without_primary_flag(): void
    {
        $p = Product::create(['name' => 'Img', 'slug' => 'img-1', 'category_id' => 1, 'seller_id' => Seller::first()->id, 'price' => 10, 'quantity' => 5, 'is_active' => true]);
        ProductImage::create(['product_id' => $p->id, 'image_path' => 'products/a.jpg', 'is_primary' => false, 'sort_order' => 0]);

        $this->assertNotNull(Product::with('primaryImage')->find($p->id)->primaryImage, 'falls back to first image');

        $json = json_encode($this->getJson('/api/v1/products?search=Img')->assertOk()->json());
        $this->assertTrue(str_contains($json, 'storage\/products\/a.jpg'), 'list must expose a full image URL: ' . substr($json, 0, 300));
    }
}
