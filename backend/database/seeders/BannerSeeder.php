<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('banners')->truncate();

        $now = now();

        // Unsplash CDN — free, no auth required, stable URLs
        // Format: https://images.unsplash.com/photo-{id}?w=1920&h=600&fit=crop&auto=format&q=80
        // Mobile: ?w=768&h=400&fit=crop

        DB::table('banners')->insert([

            // ── HERO SLIDERS (position=hero, type=slider) ─────────────────

            [
                'title'           => 'Eid Special Sale — Up to 50% Off',
                'title_bn'        => 'ঈদ বিশেষ সেল — ৫০% পর্যন্ত ছাড়',
                'subtitle'        => 'Shop the biggest deals of the year on fashion, clothing and more.',
                'image'           => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1920&h=600&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=768&h=400&fit=crop&auto=format&q=80',
                'link'            => '/sale/eid',
                'position'        => 'hero',
                'type'            => 'slider',
                'slider_duration' => 6,
                'sort_order'      => 1,
                'is_active'       => true,
                'starts_at'       => $now->copy()->subDays(5),
                'ends_at'         => $now->copy()->addDays(25),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'title'           => 'New Fashion Collection 2024',
                'title_bn'        => 'নতুন ফ্যাশন কালেকশন ২০২৪',
                'subtitle'        => 'Discover the latest trends in men\'s and women\'s fashion.',
                'image'           => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&h=600&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=768&h=400&fit=crop&auto=format&q=80',
                'link'            => '/category/fashion',
                'position'        => 'hero',
                'type'            => 'slider',
                'slider_duration' => 5,
                'sort_order'      => 2,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'title'           => 'Premium Electronics — Best Prices',
                'title_bn'        => 'প্রিমিয়াম ইলেকট্রনিক্স — সেরা দাম',
                'subtitle'        => 'Top brands, genuine products, fast delivery across Bangladesh.',
                'image'           => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=1920&h=600&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=768&h=400&fit=crop&auto=format&q=80',
                'link'            => '/category/electronics',
                'position'        => 'hero',
                'type'            => 'slider',
                'slider_duration' => 5,
                'sort_order'      => 3,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'title'           => 'Home & Living — Refresh Your Space',
                'title_bn'        => 'হোম ও লিভিং — নতুন রূপে সাজান',
                'subtitle'        => 'Beautiful furniture, décor and essentials for every room.',
                'image'           => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1920&h=600&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=768&h=400&fit=crop&auto=format&q=80',
                'link'            => '/category/home-living',
                'position'        => 'hero',
                'type'            => 'slider',
                'slider_duration' => 5,
                'sort_order'      => 4,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // ── PROMO TOP BANNERS (position=promo_top, type=banner) ────────

            [
                'title'           => 'New Electronics Arrivals',
                'title_bn'        => 'নতুন ইলেকট্রনিক্স এসেছে',
                'subtitle'        => 'Latest gadgets from top brands at unbeatable prices.',
                'image'           => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=1920&h=400&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=768&h=300&fit=crop&auto=format&q=80',
                'link'            => '/category/electronics',
                'position'        => 'promo_top',
                'type'            => 'banner',
                'slider_duration' => 5,
                'sort_order'      => 1,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'title'           => 'Men\'s Panjabi — Eid Collection',
                'title_bn'        => 'পুরুষদের পাঞ্জাবি — ঈদ কালেকশন',
                'subtitle'        => 'Hand-stitched, premium cotton. Starting from ৳699.',
                'image'           => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1920&h=400&fit=crop&auto=format&q=80',
                'mobile_image'    => null,
                'link'            => '/category/panjabi',
                'position'        => 'promo_top',
                'type'            => 'slider',
                'slider_duration' => 4,
                'sort_order'      => 2,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => $now->copy()->addDays(20),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // ── PROMO BOTTOM BANNER (position=promo_bottom, type=banner) ──

            [
                'title'           => 'Free Shipping on Orders Over ৳999',
                'title_bn'        => '৳৯৯৯-এর উপরে অর্ডারে ফ্রি শিপিং',
                'subtitle'        => 'Limited time offer — order now and save on delivery!',
                'image'           => 'https://images.unsplash.com/photo-1586769852836-bc069f19e1b6?w=1920&h=300&fit=crop&auto=format&q=80',
                'mobile_image'    => 'https://images.unsplash.com/photo-1586769852836-bc069f19e1b6?w=768&h=200&fit=crop&auto=format&q=80',
                'link'            => '/products',
                'position'        => 'promo_bottom',
                'type'            => 'banner',
                'slider_duration' => 5,
                'sort_order'      => 1,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => $now->copy()->addDays(15),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // ── SIDEBAR BANNER (position=sidebar, type=banner) ────────────

            [
                'title'           => 'Flash Sale — 30% Off Today',
                'title_bn'        => 'ফ্ল্যাশ সেল — আজ ৩০% ছাড়',
                'subtitle'        => 'Grab deals before they\'re gone.',
                'image'           => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=600&h=800&fit=crop&auto=format&q=80',
                'mobile_image'    => null,
                'link'            => '/flash-sale',
                'position'        => 'sidebar',
                'type'            => 'banner',
                'slider_duration' => 5,
                'sort_order'      => 1,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => $now->copy()->addDays(1),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // ── POPUP BANNER (position=popup, type=banner) ────────────────

            [
                'title'           => 'Subscribe & Get 10% Off Your First Order',
                'title_bn'        => 'সাবস্ক্রাইব করুন ও প্রথম অর্ডারে ১০% ছাড় পান',
                'subtitle'        => 'Join thousands of happy shoppers. No spam, ever.',
                'image'           => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=800&h=500&fit=crop&auto=format&q=80',
                'mobile_image'    => null,
                'link'            => '/newsletter',
                'position'        => 'popup',
                'type'            => 'banner',
                'slider_duration' => 5,
                'sort_order'      => 1,
                'is_active'       => true,
                'starts_at'       => null,
                'ends_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

        ]);

        $total   = DB::table('banners')->count();
        $sliders = DB::table('banners')->where('type', 'slider')->count();
        $banners = DB::table('banners')->where('type', 'banner')->count();

        $this->command->info("BannerSeeder: {$total} records seeded ({$sliders} sliders, {$banners} banners).");
    }
}
