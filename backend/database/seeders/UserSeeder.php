<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\{User, Address};

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'             => 'রাহেলা বেগম',
                'email'            => 'rahela@example.com',
                'phone'            => '01711000001',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'female',
                'date_of_birth'    => '1992-05-14',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 250.00,
                'total_orders'     => 12,
                'completed_orders' => 10,
                'total_spent'      => 14500.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'করিম উদ্দিন',
                'email'            => 'karim@example.com',
                'phone'            => '01812000002',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1988-11-03',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 5,
                'completed_orders' => 5,
                'total_spent'      => 7200.00,
                'device_type'      => 'ios',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'নাদিয়া ইসলাম',
                'email'            => 'nadia@example.com',
                'phone'            => '01911000003',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'female',
                'date_of_birth'    => '1995-07-22',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 500.00,
                'total_orders'     => 20,
                'completed_orders' => 18,
                'total_spent'      => 32000.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'মোঃ সালাউদ্দিন',
                'email'            => 'salauddin@example.com',
                'phone'            => '01611000004',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1985-02-18',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 150.00,
                'total_orders'     => 8,
                'completed_orders' => 7,
                'total_spent'      => 9800.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'ফারজানা আক্তার',
                'email'            => 'farzana@example.com',
                'phone'            => '01511000005',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'female',
                'date_of_birth'    => '1998-09-30',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 1000.00,
                'total_orders'     => 35,
                'completed_orders' => 33,
                'total_spent'      => 55000.00,
                'device_type'      => 'ios',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],

            // Users with cancellations (auto-block candidates)
            [
                'name'             => 'আবুল হোসেন',
                'email'            => 'abul@example.com',
                'phone'            => '01712000006',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1990-04-12',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 4,
                'completed_orders' => 1,
                'cancel_order_count' => 2,
                'total_spent'      => 800.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'রফিকুল ইসলাম',
                'email'            => 'rafiqul@example.com',
                'phone'            => '01813000007',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1993-08-25',
                'status'           => 'active',
                'is_blocked'       => false,
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 5,
                'completed_orders' => 2,
                'cancel_order_count' => 2,
                'no_answer_count'  => 1,
                'total_spent'      => 1200.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],

            // Blocked users
            [
                'name'             => 'জামাল উদ্দিন',
                'email'            => 'jamal@example.com',
                'phone'            => '01914000008',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1987-01-09',
                'status'           => 'banned',
                'is_blocked'       => true,
                'blocked_reason'   => '৩ বার অর্ডার বাতিল করার কারণে আপনার অ্যাকাউন্ট ৩০ দিনের জন্য স্থগিত করা হয়েছে।',
                'block_type'       => 'auto_cancel',
                'blocked_at'       => now()->subDays(5),
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 6,
                'completed_orders' => 1,
                'cancel_order_count' => 3,
                'total_spent'      => 600.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'সুমাইয়া খানম',
                'email'            => 'sumaiya@example.com',
                'phone'            => '01615000009',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'female',
                'date_of_birth'    => '1996-12-17',
                'status'           => 'banned',
                'is_blocked'       => true,
                'blocked_reason'   => 'ডেলিভারির সময় ৩ বার ফোন না ধরার কারণে অ্যাকাউন্ট স্থগিত।',
                'block_type'       => 'auto_no_answer',
                'blocked_at'       => now()->subDays(10),
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 5,
                'completed_orders' => 1,
                'no_answer_count'  => 3,
                'total_spent'      => 500.00,
                'device_type'      => 'ios',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
            [
                'name'             => 'তারিক হাসান',
                'email'            => 'tariq@example.com',
                'phone'            => '01516000010',
                'password'         => Hash::make('Password@123'),
                'gender'           => 'male',
                'date_of_birth'    => '1991-06-04',
                'status'           => 'banned',
                'is_blocked'       => true,
                'blocked_reason'   => 'সন্দেহজনক কার্যকলাপের কারণে ম্যানুয়াল ব্লক।',
                'block_type'       => 'manual',
                'blocked_at'       => now()->subDays(2),
                'referral_code'    => strtoupper(Str::random(8)),
                'wallet_balance'   => 0.00,
                'total_orders'     => 3,
                'completed_orders' => 0,
                'cancel_order_count' => 1,
                'failed_delivery_count' => 2,
                'total_spent'      => 0.00,
                'device_type'      => 'android',
                'preferred_language' => 'bn',
                'email_verified_at' => now(),
            ],
        ];

        $addresses = [
            '01711000001' => ['name' => 'রাহেলা বেগম', 'phone' => '01711000001', 'type' => 'home',   'address_line' => 'বাড়ি ৫, রোড ৩, ধানমন্ডি',      'division_id' => 1, 'district_id' => 1, 'postal_code' => '1209', 'is_default' => true],
            '01812000002' => ['name' => 'করিম উদ্দিন',  'phone' => '01812000002', 'type' => 'home',   'address_line' => 'বাড়ি ১২, কলোনি রোড, গাজীপুর',  'division_id' => 1, 'district_id' => 2, 'postal_code' => '1700', 'is_default' => true],
            '01911000003' => ['name' => 'নাদিয়া ইসলাম', 'phone' => '01911000003', 'type' => 'home',   'address_line' => 'ফ্ল্যাট ৪বি, গুলশান এভিনিউ',    'division_id' => 1, 'district_id' => 1, 'postal_code' => '1212', 'is_default' => true],
            '01611000004' => ['name' => 'সালাউদ্দিন',   'phone' => '01611000004', 'type' => 'office', 'address_line' => 'মতিঝিল বাণিজ্যিক এলাকা, ঢাকা', 'division_id' => 1, 'district_id' => 1, 'postal_code' => '1000', 'is_default' => true],
            '01511000005' => ['name' => 'ফারজানা আক্তার','phone' => '01511000005', 'type' => 'home',   'address_line' => 'হাউজ ৭, সেক্টর ৬, উত্তরা',     'division_id' => 1, 'district_id' => 1, 'postal_code' => '1230', 'is_default' => true],
        ];

        foreach ($users as $data) {
            $phone = $data['phone'];
            $user  = User::updateOrCreate(['email' => $data['email']], $data);

            if (isset($addresses[$phone])) {
                Address::updateOrCreate(
                    ['user_id' => $user->id, 'is_default' => true],
                    array_merge($addresses[$phone], ['user_id' => $user->id])
                );
            }
        }

        echo "✅ " . count($users) . " users seeded (5 active, 2 high-cancel, 3 blocked)\n";
    }
}
