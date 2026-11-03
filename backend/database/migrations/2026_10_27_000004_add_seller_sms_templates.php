<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;

// Adds the seller SMS templates to existing installs. Existing rows (possibly edited by the admin) are never touched.
return new class extends Migration {
    public function up(): void
    {
        $rows = [
            ['seller_new_order', 'সেলার: নতুন অর্ডার', '{seller_shop}: নতুন অর্ডার #{order_number} ({total_amount})। আপনার অংশ {payout}। ড্যাশবোর্ড থেকে নিশ্চিত করুন।'],
            ['seller_order_cancelled', 'সেলার: অর্ডার বাতিল', '{seller_shop}: অর্ডার #{order_number} বাতিল হয়েছে। পাঠাবেন না।'],
            ['seller_withdrawal_paid', 'সেলার: উইথড্র পরিশোধ', '{seller_shop}: {amount} পাঠানো হয়েছে ({account})। রেফারেন্স: {reference}'],
            ['seller_withdrawal_rejected', 'সেলার: উইথড্র বাতিল', '{seller_shop}: {amount} উইথড্র বাতিল ({reason})। টাকা ব্যালেন্সে ফেরত গেছে।'],
            ['account_unblocked', 'অ্যাকাউন্ট সচল', '{shop_name}: আপনার অ্যাকাউন্ট আবার সচল করা হয়েছে।'],
            ['order_delivery_failed', 'ডেলিভারি ব্যর্থ', '{shop_name}: অর্ডার #{order_number} ডেলিভারি করা যায়নি। অনুগ্রহ করে কল করুন: {helpline}'],
        ];
        foreach ($rows as [$name, $title, $body]) {
            NotificationTemplate::firstOrCreate(['name' => $name], ['title' => $title, 'type' => 'sms', 'body' => $body, 'is_system' => true, 'is_active' => true]);
        }
    }

    public function down(): void
    {
    }
};
