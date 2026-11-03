<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $sellers = [
            [
                'user_id'         => null,
                'shop_name'       => 'Dhaka Fashion House',
                'shop_name_bn'    => 'ঢাকা ফ্যাশন হাউস',
                'slug'            => 'dhaka-fashion-house',
                'description'     => 'Premium clothing and accessories from the heart of Dhaka.',
                'logo'            => null,
                'banner'          => null,
                'phone'           => '01711111111',
                'email'           => 'dhakafashion@example.com',
                'address'         => 'House 12, Road 5, Dhanmondi, Dhaka-1209',
                'nid_number'      => '1234567890123',
                'trade_license'   => 'TL-2024-001',
                'bkash_number'    => '01711111111',
                'bank_account'    => '1234567890',
                'bank_name'       => 'Dutch Bangla Bank',
                'commission_rate' => 10.00,
                'balance'         => 25000.00,
                'status'          => 'active',
                'is_verified'     => true,
                'is_featured'     => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'user_id'         => null,
                'shop_name'       => 'Tech Zone BD',
                'shop_name_bn'    => 'টেক জোন বিডি',
                'slug'            => 'tech-zone-bd',
                'description'     => 'Authentic electronics and gadgets at competitive prices.',
                'logo'            => null,
                'banner'          => null,
                'phone'           => '01722222222',
                'email'           => 'techzone@example.com',
                'address'         => 'Shop 45, Multiplan Centre, New Elephant Road, Dhaka-1205',
                'nid_number'      => '9876543210123',
                'trade_license'   => 'TL-2024-002',
                'bkash_number'    => '01722222222',
                'bank_account'    => '0987654321',
                'bank_name'       => 'BRAC Bank',
                'commission_rate' => 8.00,
                'balance'         => 87500.00,
                'status'          => 'active',
                'is_verified'     => true,
                'is_featured'     => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'user_id'         => null,
                'shop_name'       => 'Green Organic Shop',
                'shop_name_bn'    => 'গ্রিন অর্গানিক শপ',
                'slug'            => 'green-organic-shop',
                'description'     => 'Certified organic food and herbal products from local farms.',
                'logo'            => null,
                'banner'          => null,
                'phone'           => '01733333333',
                'email'           => 'greenorganic@example.com',
                'address'         => 'Plot 7, Bashundhara R/A, Dhaka-1229',
                'nid_number'      => '1122334455667',
                'trade_license'   => 'TL-2024-003',
                'bkash_number'    => '01733333333',
                'bank_account'    => null,
                'bank_name'       => null,
                'commission_rate' => 12.00,
                'balance'         => 8200.00,
                'status'          => 'active',
                'is_verified'     => true,
                'is_featured'     => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'user_id'         => null,
                'shop_name'       => 'Home Comfort Store',
                'shop_name_bn'    => 'হোম কমফোর্ট স্টোর',
                'slug'            => 'home-comfort-store',
                'description'     => 'Everything you need to make your home beautiful and functional.',
                'logo'            => null,
                'banner'          => null,
                'phone'           => '01744444444',
                'email'           => 'homecomfort@example.com',
                'address'         => '23 Mirpur Road, Kalyanpur, Dhaka-1207',
                'nid_number'      => '5566778899001',
                'trade_license'   => 'TL-2024-004',
                'bkash_number'    => '01744444444',
                'bank_account'    => '1122334455',
                'bank_name'       => 'Islami Bank',
                'commission_rate' => 11.00,
                'balance'         => 14600.00,
                'status'          => 'active',
                'is_verified'     => false,
                'is_featured'     => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'user_id'         => null,
                'shop_name'       => 'Beauty & Glow',
                'shop_name_bn'    => 'বিউটি ও গ্লো',
                'slug'            => 'beauty-and-glow',
                'description'     => 'Premium skincare, makeup and beauty products.',
                'logo'            => null,
                'banner'          => null,
                'phone'           => '01755555555',
                'email'           => 'beautyglow@example.com',
                'address'         => 'Level 3, Jamuna Future Park, Dhaka-1229',
                'nid_number'      => '2233445566778',
                'trade_license'   => 'TL-2024-005',
                'bkash_number'    => '01755555555',
                'bank_account'    => null,
                'bank_name'       => null,
                'commission_rate' => 13.00,
                'balance'         => 5400.00,
                'status'          => 'pending',
                'is_verified'     => false,
                'is_featured'     => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        DB::table('sellers')->insert($sellers);

        // Demo logins so the seller dashboard can be tried: each active seller gets a user
        // (phone = the seller's phone, password Password@123).
        foreach (DB::table('sellers')->where('status', 'active')->whereNull('user_id')->get() as $seller) {
            $uid = DB::table('users')->where('phone', $seller->phone)->value('id') ?? DB::table('users')->insertGetId([
                'name'          => $seller->shop_name . ' (owner)',
                'email'         => $seller->email ?: 'seller' . $seller->id . '@example.com',
                'phone'         => $seller->phone,
                'password'      => \Illuminate\Support\Facades\Hash::make('Password@123'),
                'status'        => 'active',
                'referral_code' => strtoupper(substr(md5('seller' . $seller->id), 0, 8)),
                'created_at'    => $now, 'updated_at' => $now,
            ]);
            DB::table('sellers')->where('id', $seller->id)->update(['user_id' => $uid]);
        }

        $this->command->info('SellerSeeder: ' . count($sellers) . ' sellers seeded.');
    }
}
