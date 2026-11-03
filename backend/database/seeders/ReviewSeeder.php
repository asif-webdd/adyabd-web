<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $users = DB::table('users')->pluck('id')->toArray();
        $products = DB::table('products')->where('is_active', true)->pluck('id')->toArray();
        $orders = Order::with('items')->where('status', 'delivered')->get()->toArray();

        if (empty($users) || empty($products)) {
            $this->command->warn('ReviewSeeder: Requires users and products. Seed those first.');
            return;
        }

        $reviews = [
            // 5-star reviews
            [
                'rating'   => 5,
                'title'    => 'Absolutely love it!',
                'review'   => 'The quality exceeded my expectations. Packaging was great and delivery was fast. Will definitely order again!',
                'approved' => true,
                'featured' => true,
                'reply'    => 'Thank you so much for your kind words! We are thrilled you loved it.',
            ],
            [
                'rating'   => 5,
                'title'    => 'Best purchase this year',
                'review'   => 'Exactly as described in the listing. The material feels premium and fits perfectly. Highly recommended!',
                'approved' => true,
                'featured' => true,
                'reply'    => null,
            ],
            [
                'rating'   => 5,
                'title'    => 'দারুণ পণ্য!',
                'review'   => 'পণ্যের মান অসাধারণ। দাম অনুযায়ী একদম পারফেক্ট। আবার অর্ডার করব।',
                'approved' => true,
                'featured' => false,
                'reply'    => 'ধন্যবাদ! আপনার সন্তুষ্টি আমাদের অনুপ্রেরণা।',
            ],
            // 4-star reviews
            [
                'rating'   => 4,
                'title'    => 'Very good, minor packaging issue',
                'review'   => 'The product itself is great — 5 stars for quality. Deducting one star because the outer box was slightly dented on arrival. Customer support was responsive though.',
                'approved' => true,
                'featured' => false,
                'reply'    => 'We apologise for the packaging issue and have noted your feedback to improve our shipping process.',
            ],
            [
                'rating'   => 4,
                'title'    => 'Good value for money',
                'review'   => 'Decent quality for the price. Delivery took 4 days which is acceptable. The colour is slightly different from the photos but still looks good.',
                'approved' => true,
                'featured' => false,
                'reply'    => null,
            ],
            [
                'rating'   => 4,
                'title'    => 'Satisfied overall',
                'review'   => 'Pretty good product. Matches the description well. I would have given 5 stars if the delivery was a bit faster.',
                'approved' => true,
                'featured' => false,
                'reply'    => null,
            ],
            // 3-star reviews
            [
                'rating'   => 3,
                'title'    => 'Average quality',
                'review'   => 'It is an okay product. Nothing special but nothing terrible either. Expected slightly better build quality at this price point.',
                'approved' => true,
                'featured' => false,
                'reply'    => 'Thank you for the honest feedback. We will work on improving the build quality.',
            ],
            [
                'rating'   => 3,
                'title'    => 'Decent but could be better',
                'review'   => 'The product works as described but feels a bit cheap. Customer service was helpful when I had a query.',
                'approved' => true,
                'featured' => false,
                'reply'    => null,
            ],
            // 2-star reviews
            [
                'rating'   => 2,
                'title'    => 'Not as expected',
                'review'   => 'The product looks different from the photos. Quality is below average. Would not recommend at this price.',
                'approved' => true,
                'featured' => false,
                'reply'    => 'We sincerely apologise for the disappointment. Please contact our support team and we will make it right.',
            ],
            // 1-star review (pending approval — not approved)
            [
                'rating'   => 1,
                'title'    => 'Very disappointed',
                'review'   => 'The item arrived damaged and the seller was unresponsive. Very bad experience.',
                'approved' => false,
                'featured' => false,
                'reply'    => null,
            ],
            // Additional approved 5-star
            [
                'rating'   => 5,
                'title'    => 'Fast delivery, great product!',
                'review'   => 'Ordered on Monday, received on Wednesday. The product quality is top-notch. Very happy with this purchase.',
                'approved' => true,
                'featured' => false,
                'reply'    => null,
            ],
            [
                'rating'   => 5,
                'title'    => 'Excellent seller',
                'review'   => 'The seller communicated proactively about the delivery status. Product is exactly as shown. 100% satisfied.',
                'approved' => true,
                'featured' => true,
                'reply'    => 'Your satisfaction means everything to us. Thank you!',
            ],
        ];

        $count = 0;

        foreach ($reviews as $i => $data) {
            $productId = $products[array_rand($products)];
            $userId = $users[array_rand($users)];
            $order = $orders ? $orders[array_rand((array)$orders)] : null;
            $createdAt = $now->copy()->subDays(rand(1, 60))->subHours(rand(0, 23));
            $repliedAt = ($data['reply'] && $data['approved']) ? $createdAt->copy()->addHours(rand(2, 48)) : null;
            foreach ($order['items'] as $item) {
                DB::table('reviews')->insert([
                    'product_id'       => $productId,
                    'user_id'          => $userId,
                    'order_item_id'    => $item['id'],
                    'rating'           => $data['rating'],
                    'title'            => $data['title'],
                    'review'           => $data['review'],
                    'images'           => null,
                    'is_approved'      => $data['approved'],
                    'is_featured'      => $data['featured'],
                    'admin_reply'      => $data['reply'],
                    'admin_replied_at' => $repliedAt,
                    'created_at'       => $createdAt,
                    'updated_at'       => $createdAt,
                ]);
            }
            $count++;
        }

        // Update product rating and total_reviews based on approved reviews
        /*$productIds = DB::table('reviews')->where('is_approved', true)->distinct()->pluck('product_id');

        foreach ($productIds as $pid) {
            //$stats = DB::table('reviews')->where('product_id', $pid)->where('is_approved', true)->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')->first();
            $productModel = Product::withAvg('reviews', 'rating')->withCount('reviews')->where('id', $pid)->first();
            DB::table('products')->where('id', $pid)->update([
                'rating'        => round($productModel->reviews_avg_rating, 2),
                'total_reviews' => $productModel->reviews_count,
                'updated_at'    => $now,
            ]);
        }*/

        $this->command->info("ReviewSeeder: {$count} reviews seeded. Product ratings updated.");
    }
}
