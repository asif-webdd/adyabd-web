<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Pull existing data we need for FK references
        $users = DB::table('users')->pluck('id')->toArray();
        $sellers = DB::table('sellers')->where('status', 'active')->pluck('id')->toArray();
        $products = Product::with('primaryImage')->where('is_active', true)->get()->keyBy('id');
        $coupon = DB::table('coupons')->where('code', 'EID50')->first();
        $courier = DB::table('courier_merchants')->where('is_default', true)->first();

        if (empty($users)) {
            $this->command->warn('OrderSeeder: No users found. Seed users first.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->warn('OrderSeeder: No active products found. Seed products first.');
            return;
        }

        $divisions = DB::table('divisions')->pluck('name', 'id');
        $districts = DB::table('districts')->pluck('name', 'id');

        $statuses = [
            'pending', 'confirmed', 'processing', 'shipped',
            'out_for_delivery', 'delivered', 'cancelled', 'returned',
        ];

        $paymentMethods = ['cod', 'bkash', 'nagad', 'card'];
        $paymentStatuses = ['pending', 'paid', 'failed', 'refunded'];
        $deliveryTypes = ['standard', 'express', 'same_day'];
        $shippingDivisions = ['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi'];
        $shippingDistricts = ['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi'];
        $shippingUpazilas = ['Dhanmondi', 'Mirpur', 'Gulshan', 'Uttara', 'Motijheel'];

        $ordersData = [
            // 1 — delivered, bkash paid, with coupon
            [
                'status'         => 'delivered',
                'payment_method' => 'bkash',
                'payment_status' => 'paid',
                'delivery_type'  => 'standard',
                'use_coupon'     => true,
                'days_ago'       => 20,
                'item_count'     => 2,
            ],
            // 2 — delivered, COD paid
            [
                'status'         => 'delivered',
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'delivery_type'  => 'standard',
                'use_coupon'     => false,
                'days_ago'       => 15,
                'item_count'     => 1,
            ],
            // 3 — shipped
            [
                'status'         => 'shipped',
                'payment_method' => 'nagad',
                'payment_status' => 'paid',
                'delivery_type'  => 'express',
                'use_coupon'     => false,
                'days_ago'       => 5,
                'item_count'     => 2,
            ],
            // 4 — processing
            [
                'status'         => 'processing',
                'payment_method' => 'bkash',
                'payment_status' => 'paid',
                'delivery_type'  => 'standard',
                'use_coupon'     => false,
                'days_ago'       => 3,
                'item_count'     => 1,
            ],
            // 5 — pending, COD
            [
                'status'         => 'pending',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'delivery_type'  => 'standard',
                'use_coupon'     => false,
                'days_ago'       => 1,
                'item_count'     => 3,
            ],
            // 6 — cancelled by user
            [
                'status'         => 'cancelled',
                'payment_method' => 'bkash',
                'payment_status' => 'refunded',
                'delivery_type'  => 'standard',
                'use_coupon'     => false,
                'days_ago'       => 10,
                'item_count'     => 1,
            ],
            // 7 — out for delivery
            [
                'status'         => 'out_for_delivery',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'delivery_type'  => 'same_day',
                'use_coupon'     => false,
                'days_ago'       => 0,
                'item_count'     => 2,
            ],
            // 8 — confirmed, card paid
            [
                'status'         => 'confirmed',
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'delivery_type'  => 'express',
                'use_coupon'     => true,
                'days_ago'       => 2,
                'item_count'     => 1,
            ],
        ];

        $productList = $products->values()->all();
        $orderCount = 0;

        foreach ($ordersData as $i => $blueprint) {
            $userId = $users[array_rand($users)];
            $sellerId = $sellers ? $sellers[array_rand($sellers)] : null;
            $createdAt = $now->copy()->subDays($blueprint['days_ago'])->subHours(rand(0, 23));

            // Pick random products for this order
            shuffle($productList);
            $selectedProducts = array_slice($productList, 0, min($blueprint['item_count'], count($productList)));

            // Build order items and calculate totals
            $items = [];
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $qty = rand(1, 3);
                $unitPrice = (float)$product->price;
                $cmpPrice = $product->compare_price ? (float)$product->compare_price : null;
                $discount = $cmpPrice && $cmpPrice > $unitPrice ? round(($cmpPrice - $unitPrice) * $qty, 2) : 0;
                $totalPrice = round($unitPrice * $qty, 2);
                $subtotal += $totalPrice;

                $items[] = [
                    'user_id'         => $userId,
                    'product_id'      => $product->id,
                    'variant_id'      => null,
                    'product_name'    => $product->name,
                    'product_image'   => $product->primaryImage->image_path,
                    'variant_options' => null,
                    'quantity'        => $qty,
                    'unit_price'      => $unitPrice,
                    'compare_price'   => $cmpPrice,
                    'discount_amount' => $discount,
                    'total_price'     => $totalPrice,
                ];
            }

            $shippingCharge = $blueprint['delivery_type'] === 'express' ? 120.00 : ($blueprint['delivery_type'] === 'same_day' ? 150.00 : 60.00);
            $couponDiscount = 0;
            $couponId = null;
            $couponCode = null;

            if ($blueprint['use_coupon'] && $coupon && $subtotal >= (float)$coupon->min_order_amount) {
                $couponDiscount = $coupon->type === 'fixed'
                    ? (float)$coupon->value
                    : round($subtotal * ((float)$coupon->value / 100), 2);

                if ($coupon->max_discount_amount) {
                    $couponDiscount = min($couponDiscount, (float)$coupon->max_discount_amount);
                }

                $couponId = $coupon->id;
                $couponCode = $coupon->code;
            }

            $totalAmount = max(0, $subtotal - $couponDiscount + $shippingCharge);
            $commissionRate = $sellerId ? (float)(DB::table('sellers')->where('id', $sellerId)->value('commission_rate') ?? 10) : 10;
            $commissionAmount = round($subtotal * ($commissionRate / 100), 2);
            $sellerPayout = round($subtotal - $commissionAmount, 2);

            $isCancelled = $blueprint['status'] === 'cancelled';
            $isDelivered = $blueprint['status'] === 'delivered';
            $courierStatus = match ($blueprint['status']) {
                'delivered'        => 'delivered',
                'shipped',
                'out_for_delivery' => 'in_transit',
                'cancelled',
                'returned'         => 'returned',
                default            => 'pending',
            };

            $orderId = DB::table('orders')->insertGetId([
                'order_number'           => 'ORD-' . strtoupper(Str::random(8)),
                'user_id'                => $userId,
                'seller_id'              => $sellerId,
                'coupon_id'              => $couponId,
                'coupon_code'            => $couponCode,
                'status'                 => $blueprint['status'],
                'payment_method'         => $blueprint['payment_method'],
                'payment_status'         => $blueprint['payment_status'],
                'payment_transaction_id' => in_array($blueprint['payment_method'], ['bkash', 'nagad', 'card']) ? 'TXN' . strtoupper(Str::random(10)) : null,
                'subtotal'               => $subtotal,
                'discount_amount'        => 0,
                'coupon_discount'        => $couponDiscount,
                'shipping_charge'        => $shippingCharge,
                'total_amount'           => $totalAmount,
                'commission_amount'      => $commissionAmount,
                'seller_payout'          => $sellerPayout,
                'shipping_name'          => 'Test Customer ' . ($i + 1),
                'shipping_phone'         => '017' . rand(10000000, 99999999),
                'shipping_address'       => 'House ' . rand(1, 99) . ', Road ' . rand(1, 20) . ', Block ' . chr(rand(65, 70)),
                'shipping_division'      => $shippingDivisions[array_rand($shippingDivisions)],
                'shipping_district'      => $shippingDistricts[array_rand($shippingDistricts)],
                'shipping_upazila'       => $shippingUpazilas[array_rand($shippingUpazilas)],
                'shipping_postal_code'   => (string)rand(1000, 9999),
                'delivery_type'          => $blueprint['delivery_type'],
                'estimated_delivery_at'  => $createdAt->copy()->addDays($blueprint['delivery_type'] === 'same_day' ? 1 : ($blueprint['delivery_type'] === 'express' ? 2 : 5)),
                'delivered_at'           => $isDelivered ? $createdAt->copy()->addDays(rand(3, 7)) : null,
                'cancelled_at'           => $isCancelled ? $createdAt->copy()->addHours(rand(1, 12)) : null,
                'cancellation_reason'    => $isCancelled ? 'Customer requested cancellation' : null,
                'cancelled_by'           => $isCancelled ? 'user' : null,
                'courier_merchant_id'    => $courier?->id,
                'courier_tracking_id'    => 'TRK' . strtoupper(Str::random(8)),
                'courier_tracking_url'   => $courier ? str_replace('{tracking_id}', 'TRK' . strtoupper(Str::random(8)), $courier->tracking_url ?? '') : null,
                'courier_status'         => $courierStatus,
                'courier_response'       => null,
                'courier_booked_at'      => in_array($blueprint['status'], ['shipped', 'out_for_delivery', 'delivered']) ? $createdAt->copy()->addHours(rand(2, 10)) : null,
                'customer_note'          => rand(0, 1) ? 'Please deliver between 10am–6pm.' : null,
                'admin_note'             => null,
                'user_ip'                => '103.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255),
                'user_device'            => 'Mozilla/5.0 (Android)',
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            // Insert order items
            foreach ($items as &$item) {
                $item['order_id'] = $orderId;
                $item['created_at'] = $createdAt;
                $item['updated_at'] = $createdAt;
            }
            DB::table('order_items')->insert($items);

            // Seed order status history
            $this->seedStatusHistory($orderId, $blueprint['status'], $createdAt);

            $orderCount++;
        }

        $this->command->info("OrderSeeder: {$orderCount} orders seeded with items and status histories.");
    }

    private function seedStatusHistory(int $orderId, string $finalStatus, Carbon $createdAt): void
    {
        $flow = [
            'pending'          => ['pending'],
            'confirmed'        => ['pending', 'confirmed'],
            'processing'       => ['pending', 'confirmed', 'processing'],
            'shipped'          => ['pending', 'confirmed', 'processing', 'shipped'],
            'out_for_delivery' => ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery'],
            'delivered'        => ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'],
            'cancelled'        => ['pending', 'cancelled'],
            'returned'         => ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'returned'],
        ];

        $steps = $flow[$finalStatus] ?? ['pending'];

        foreach ($steps as $i => $status) {
            DB::table('order_status_histories')->insert([
                'order_id'      => $orderId,
                'status'        => $status,
                'note'          => $this->statusNote($status),
                'changed_by'    => $status === 'cancelled' ? 'user' : 'system',
                'changed_by_id' => null,
                'created_at'    => $createdAt->copy()->addHours($i * 2),
            ]);
        }
    }

    private function statusNote(string $status): string
    {
        return match ($status) {
            'pending'          => 'Order placed successfully.',
            'confirmed'        => 'Order confirmed by seller.',
            'processing'       => 'Order is being prepared.',
            'shipped'          => 'Order handed to courier.',
            'out_for_delivery' => 'Out for delivery.',
            'delivered'        => 'Order delivered successfully.',
            'cancelled'        => 'Order cancelled.',
            'returned'         => 'Order returned by customer.',
            default            => 'Status updated.',
        };
    }
}
