<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $parents = [
            [
                'parent_id'        => null,
                'name'             => 'Fashion',
                'name_bn'          => 'ফ্যাশন',
                'slug'             => 'fashion',
                'description'      => 'Clothing, footwear and accessories for men, women and kids.',
                'image'            => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=600&h=400&fit=crop',
                'icon'             => 'checkroom',
                'color'            => '#E91E63',
                'sort_order'       => 1,
                'is_active'        => true,
                'show_in_homepage' => true,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],

            [
                'parent_id'        => null,
                'name'             => 'Electronics',
                'name_bn'          => 'ইলেকট্রনিক্স',
                'slug'             => 'electronics',
                'description'      => 'Smartphones, laptops, audio, and other electronic gadgets.',
                'image'            => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=600&h=400&fit=crop',
                'icon'             => 'devices',
                'color'            => '#1565C0',
                'sort_order'       => 2,
                'is_active'        => true,
                'show_in_homepage' => true,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],

            [
                'parent_id'        => null,
                'name'             => 'Home & Living',
                'name_bn'          => 'হোম ও লিভিং',
                'slug'             => 'home-living',
                'description'      => 'Furniture, kitchen essentials, bedding and home decor.',
                'image'            => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&h=400&fit=crop',
                'icon'             => 'chair',
                'color'            => '#E65100',
                'sort_order'       => 3,
                'is_active'        => true,
                'show_in_homepage' => true,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],

            [
                'parent_id'        => null,
                'name'             => 'Health & Beauty',
                'name_bn'          => 'স্বাস্থ্য ও সৌন্দর্য',
                'slug'             => 'health-beauty',
                'description'      => 'Skincare, haircare, vitamins and personal care products.',
                'image'            => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&h=400&fit=crop',
                'icon'             => 'spa',
                'color'            => '#6A1B9A',
                'sort_order'       => 4,
                'is_active'        => true,
                'show_in_homepage' => false,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],

            [
                'parent_id'        => null,
                'name'             => 'Food & Grocery',
                'name_bn'          => 'খাদ্য ও মুদিখানা',
                'slug'             => 'food-grocery',
                'description'      => 'Fresh produce, packaged foods, beverages and organic products.',
                'image'            => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&h=400&fit=crop',
                'icon'             => 'shopping_basket',
                'color'            => '#2E7D32',
                'sort_order'       => 5,
                'is_active'        => true,
                'show_in_homepage' => true,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        DB::table('categories')->insert($parents);

        $fashionId     = DB::table('categories')->where('slug', 'fashion')->value('id');
        $electronicsId = DB::table('categories')->where('slug', 'electronics')->value('id');
        $homeId        = DB::table('categories')->where('slug', 'home-living')->value('id');
        $healthId      = DB::table('categories')->where('slug', 'health-beauty')->value('id');
        $foodId        = DB::table('categories')->where('slug', 'food-grocery')->value('id');

        $children = [
            // Fashion
            [
                'parent_id'  => $fashionId,
                'name'       => "Men's Clothing",
                'name_bn'    => 'পুরুষদের পোশাক',
                'slug'       => 'mens-clothing',
                'icon'       => 'man',
                'color'      => '#1976D2',
                'image'      => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=400&h=300&fit=crop',
                'sort_order' => 1,
            ],
            [
                'parent_id'  => $fashionId,
                'name'       => "Women's Clothing",
                'name_bn'    => 'মহিলাদের পোশাক',
                'slug'       => 'womens-clothing',
                'icon'       => 'woman',
                'color'      => '#C2185B',
                'image'      => 'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=400&h=300&fit=crop',
                'sort_order' => 2,
            ],
            [
                'parent_id'  => $fashionId,
                'name'       => 'Bags & Accessories',
                'name_bn'    => 'ব্যাগ ও আনুষাঙ্গিক',
                'slug'       => 'bags-accessories',
                'icon'       => 'shopping_bag',
                'color'      => '#7B1FA2',
                'image'      => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&h=300&fit=crop',
                'sort_order' => 3,
            ],

            // Electronics
            [
                'parent_id'  => $electronicsId,
                'name'       => 'Smartphones',
                'name_bn'    => 'স্মার্টফোন',
                'slug'       => 'smartphones',
                'icon'       => 'smartphone',
                'color'      => '#0277BD',
                'image'      => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=300&fit=crop',
                'sort_order' => 1,
            ],
            [
                'parent_id'  => $electronicsId,
                'name'       => 'Audio',
                'name_bn'    => 'অডিও',
                'slug'       => 'audio',
                'icon'       => 'headphones',
                'color'      => '#00838F',
                'image'      => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
                'sort_order' => 2,
            ],
            [
                'parent_id'  => $electronicsId,
                'name'       => 'Laptops',
                'name_bn'    => 'ল্যাপটপ',
                'slug'       => 'laptops',
                'icon'       => 'laptop_mac',
                'color'      => '#283593',
                'image'      => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop',
                'sort_order' => 3,
            ],

            // Home & Living
            [
                'parent_id'  => $homeId,
                'name'       => 'Kitchen',
                'name_bn'    => 'রান্নাঘর',
                'slug'       => 'kitchen',
                'icon'       => 'kitchen',
                'color'      => '#BF360C',
                'image'      => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400&h=300&fit=crop',
                'sort_order' => 1,
            ],
            [
                'parent_id'  => $homeId,
                'name'       => 'Bedding',
                'name_bn'    => 'বিছানাপত্র',
                'slug'       => 'bedding',
                'icon'       => 'bed',
                'color'      => '#4527A0',
                'image'      => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=400&h=300&fit=crop',
                'sort_order' => 2,
            ],

            // Health & Beauty
            [
                'parent_id'  => $healthId,
                'name'       => 'Skincare',
                'name_bn'    => 'স্কিনকেয়ার',
                'slug'       => 'skincare',
                'icon'       => 'face',
                'color'      => '#AD1457',
                'image'      => 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=400&h=300&fit=crop',
                'sort_order' => 1,
            ],
            [
                'parent_id'  => $healthId,
                'name'       => 'Vitamins',
                'name_bn'    => 'ভিটামিন',
                'slug'       => 'vitamins',
                'icon'       => 'medication',
                'color'      => '#558B2F',
                'image'      => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400&h=300&fit=crop',
                'sort_order' => 2,
            ],

            // Food & Grocery
            [
                'parent_id'  => $foodId,
                'name'       => 'Beverages',
                'name_bn'    => 'পানীয়',
                'slug'       => 'beverages',
                'icon'       => 'local_drink',
                'color'      => '#00695C',
                'image'      => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&h=300&fit=crop',
                'sort_order' => 1,
            ],
            [
                'parent_id'  => $foodId,
                'name'       => 'Organic Products',
                'name_bn'    => 'জৈব পণ্য',
                'slug'       => 'organic-products',
                'icon'       => 'eco',
                'color'      => '#1B5E20',
                'image'      => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop',
                'sort_order' => 2,
            ],
        ];

        foreach ($children as &$child) {
            $child += [
                'description'      => null,
                'is_active'        => true,
                'show_in_homepage' => false,
                'show_in_menu'     => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        DB::table('categories')->insert($children);

        $this->command->info('CategorySeeder: ' . (count($parents) + count($children)) . ' categories seeded successfully.');
    }
}
