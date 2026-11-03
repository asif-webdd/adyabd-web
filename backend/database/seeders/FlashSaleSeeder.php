<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $flashSaleId = DB::table('flash_sales')->insertGetId([
            'title'               => 'Mega Flash Sale',
            'title_bn'            => 'মেগা ফ্ল্যাশ সেল',
            'discount_percentage' => 30.00,
            'starts_at'           => $now->copy()->addHour(),
            'ends_at'             => $now->copy()->addHours(25),
            'is_active'           => true,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        // Attach active products to the flash sale
        $products = DB::table('products')
            ->where('is_active', true)
            ->whereNotNull('compare_price')
            ->limit(3)
            ->get();

        foreach ($products as $product) {
            $specialPrice = round($product->price * 0.70, 2); // 30% off
            DB::table('flash_sale_products')->insert([
                'flash_sale_id'       => $flashSaleId,
                'product_id'          => $product->id,
                'discount_percentage' => 30.00,
                'special_price'       => $specialPrice,
                'quantity_limit'      => 50,
                'sold_count'          => rand(0, 20),
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        $this->command->info('FlashSaleSeeder: 1 flash sale seeded with ' . $products->count() . ' products.');
    }
}
