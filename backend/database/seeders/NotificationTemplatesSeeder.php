<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'      => 'order_placed',
                'title'     => 'অর্ডার দেওয়া হয়েছে',
                'type'      => 'sms',
                'body'      => '{shop_name}: আপনার অর্ডার #{order_number} গ্রহণ করা হয়েছে। মোট: {total_amount}। ট্র্যাক: {site_url}/track/{order_number}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'order_confirmed',
                'title'     => 'অর্ডার নিশ্চিত',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} নিশ্চিত হয়েছে। শীঘ্রই প্রক্রিয়া শুরু হবে।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'order_shipped',
                'title'     => 'কুরিয়ারে পাঠানো',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} কুরিয়ারে পাঠানো হয়েছে। ট্র্যাকিং: {tracking_id}। URL: {tracking_url}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'order_delivered',
                'title'     => 'ডেলিভারি সম্পন্ন',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} ডেলিভারি সম্পন্ন। ধন্যবাদ! রিভিউ দিন: {site_url}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'order_cancelled',
                'title'     => 'অর্ডার বাতিল',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} বাতিল হয়েছে। সাহায্য: {helpline}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'account_blocked',
                'title'     => 'অ্যাকাউন্ট স্থগিত',
                'type'      => 'sms',
                'body'      => '{shop_name}: আপনার অ্যাকাউন্ট স্থগিত। কারণ: {reason}। সাহায্য: {helpline}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'account_unblocked',
                'title'     => 'অ্যাকাউন্ট সচল',
                'type'      => 'sms',
                'body'      => '{shop_name}: আপনার অ্যাকাউন্ট আবার সচল করা হয়েছে।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'order_delivery_failed',
                'title'     => 'ডেলিভারি ব্যর্থ',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} ডেলিভারি করা যায়নি। অনুগ্রহ করে কল করুন: {helpline}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'seller_new_order',
                'title'     => 'সেলার: নতুন অর্ডার',
                'type'      => 'sms',
                'body'      => '{seller_shop}: নতুন অর্ডার #{order_number} ({total_amount})। আপনার অংশ {payout}। ড্যাশবোর্ড থেকে নিশ্চিত করুন।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'seller_order_cancelled',
                'title'     => 'সেলার: অর্ডার বাতিল',
                'type'      => 'sms',
                'body'      => '{seller_shop}: অর্ডার #{order_number} বাতিল হয়েছে। পাঠাবেন না।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'seller_withdrawal_paid',
                'title'     => 'সেলার: উইথড্র পরিশোধ',
                'type'      => 'sms',
                'body'      => '{seller_shop}: {amount} পাঠানো হয়েছে ({account})। রেফারেন্স: {reference}',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'seller_withdrawal_rejected',
                'title'     => 'সেলার: উইথড্র বাতিল',
                'type'      => 'sms',
                'body'      => '{seller_shop}: {amount} উইথড্র বাতিল ({reason})। টাকা ব্যালেন্সে ফেরত গেছে।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'otp',
                'title'     => 'OTP যাচাইকরণ',
                'type'      => 'sms',
                'body'      => '{shop_name} OTP: {otp}। কোডটি ৫ মিনিটের জন্য বৈধ। কারও সাথে শেয়ার করবেন না।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'payment_success',
                'title'     => 'পেমেন্ট সফল',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} এর {total_amount} পেমেন্ট সফল।',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name'      => 'refund_processed',
                'title'     => 'রিফান্ড সম্পন্ন',
                'type'      => 'sms',
                'body'      => '{shop_name}: অর্ডার #{order_number} এর {total_amount} রিফান্ড ওয়ালেটে যোগ হয়েছে।',
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $t) {
            NotificationTemplate::updateOrCreate(['name' => $t['name']], $t);
        }
        echo "✅ " . count($templates) . " notification templates seeded\n";
    }
}
