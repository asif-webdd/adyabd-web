<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('coupons')->insert([
            [
                'code'                 => 'WELCOME20',
                'title'                => '20% off for new users',
                'description'          => 'Valid on your first order above ৳500.',
                'type'                 => 'percentage',
                'value'                => 20.00,
                'min_order_amount'     => 500.00,
                'max_discount_amount'  => 300.00,
                'usage_limit'          => null,
                'usage_limit_per_user' => 1,
                'used_count'           => 142,
                'seller_id'            => null,
                'is_active'            => true,
                'is_for_new_users'     => true,
                'starts_at'            => $now->copy()->subMonths(3),
                'expires_at'           => $now->copy()->addMonths(6),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'code'                 => 'EID50',
                'title'                => '৳50 off on orders above ৳1000',
                'description'          => 'Eid special flat discount coupon.',
                'type'                 => 'fixed',
                'value'                => 50.00,
                'min_order_amount'     => 1000.00,
                'max_discount_amount'  => null,
                'usage_limit'          => 500,
                'usage_limit_per_user' => 1,
                'used_count'           => 278,
                'seller_id'            => null,
                'is_active'            => true,
                'is_for_new_users'     => false,
                'starts_at'            => $now->copy()->subDays(10),
                'expires_at'           => $now->copy()->addDays(20),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'code'                 => 'TECH10',
                'title'                => '10% off on electronics',
                'description'          => 'Discount on all electronics. No minimum order.',
                'type'                 => 'percentage',
                'value'                => 10.00,
                'min_order_amount'     => 0.00,
                'max_discount_amount'  => 500.00,
                'usage_limit'          => 200,
                'usage_limit_per_user' => 2,
                'used_count'           => 56,
                'seller_id'            => null,
                'is_active'            => true,
                'is_for_new_users'     => false,
                'starts_at'            => $now->copy()->subMonth(),
                'expires_at'           => $now->copy()->addMonth(),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'code'                 => 'EXPIRED30',
                'title'                => '30% Summer Sale (expired)',
                'description'          => 'Previous summer sale coupon — now inactive.',
                'type'                 => 'percentage',
                'value'                => 30.00,
                'min_order_amount'     => 800.00,
                'max_discount_amount'  => 400.00,
                'usage_limit'          => 300,
                'usage_limit_per_user' => 1,
                'used_count'           => 300,
                'seller_id'            => null,
                'is_active'            => false,
                'is_for_new_users'     => false,
                'starts_at'            => $now->copy()->subMonths(4),
                'expires_at'           => $now->copy()->subMonths(1),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ]);

        $this->command->info('CouponSeeder: 4 coupons seeded.');
    }
}

