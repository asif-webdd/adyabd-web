<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'shop_name', 'group' => 'general', 'value' => 'বাজার', 'type' => 'text', 'label' => 'Shop Name', 'is_public' => true],
            ['key' => 'invoice_prefix', 'group' => 'general', 'value' => 'BD', 'type' => 'text', 'label' => 'Invoice Prefix', 'is_public' => false],
            ['key' => 'invoice_pattern', 'group' => 'general', 'value' => 'PREFIX-YEAR-SEQ', 'type' => 'text', 'label' => 'Invoice Pattern', 'is_public' => false],
            ['key' => 'invoice_sequence_length', 'group' => 'general', 'value' => '5', 'type' => 'number', 'label' => 'Invoice Sequence Digits', 'is_public' => false],
            ['key' => 'shop_name_bn', 'group' => 'general', 'value' => 'বাজার', 'type' => 'text', 'label' => 'Shop Name BN', 'is_public' => true],
            ['key' => 'shop_email', 'group' => 'general', 'value' => 'support@bazar.com.bd', 'type' => 'text', 'label' => 'Email', 'is_public' => true],
            ['key' => 'shop_phone', 'group' => 'general', 'value' => '16488', 'type' => 'text', 'label' => 'Helpline', 'is_public' => true],
            ['key' => 'shop_url', 'group' => 'general', 'value' => 'https://bazar.com.bd', 'type' => 'text', 'label' => 'Website', 'is_public' => true],
            ['key' => 'enable_standard_shipping', 'group' => 'delivery', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Standard Delivery', 'is_public' => true],
            ['key' => 'standard_shipping_charge', 'group' => 'delivery', 'value' => '60', 'type' => 'number', 'label' => 'Standard Shipping', 'is_public' => true],
            ['key' => 'enable_express_shipping', 'group' => 'delivery', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Express Delivery', 'is_public' => true],
            ['key' => 'express_shipping_charge', 'group' => 'delivery', 'value' => '120', 'type' => 'number', 'label' => 'Express Shipping', 'is_public' => true],
            ['key' => 'enable_same_day_shipping', 'group' => 'delivery', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Same Day Delivery', 'is_public' => true],
            ['key' => 'same_day_shipping_charge', 'group' => 'delivery', 'value' => '150', 'type' => 'number', 'label' => 'Same Day Shipping', 'is_public' => true],
            ['key' => 'free_shipping_min_order', 'group' => 'delivery', 'value' => '500', 'type' => 'number', 'label' => 'Free Shipping Min', 'is_public' => true],
            ['key' => 'auto_book_courier', 'group' => 'delivery', 'value' => '0', 'type' => 'boolean', 'label' => 'Auto Send Courier', 'is_public' => false],
            ['key' => 'auto_confirm_order', 'group' => 'delivery', 'value' => '0', 'type' => 'boolean', 'label' => 'Auto Confirm Order', 'is_public' => false],
            ['key' => 'enable_cod', 'group' => 'payment', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable COD', 'is_public' => true],
            ['key' => 'enable_zinipay', 'group' => 'payment', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable ZiniPay', 'is_public' => true],
            ['key' => 'zinipay_api_key', 'group' => 'payment', 'value' => '', 'type' => 'text', 'label' => 'ZiniPay API Key', 'is_public' => false],
            ['key' => 'zinipay_base_url', 'group' => 'payment', 'value' => 'https://api.zinipay.com', 'type' => 'text', 'label' => 'ZiniPay API Base URL', 'is_public' => false],
            ['key' => 'zinipay_frontend_url', 'group' => 'payment', 'value' => rtrim(config('app.frontend_url'), '/'), 'type' => 'text', 'label' => 'ZiniPay Frontend URL', 'is_public' => false],
            ['key' => 'zinipay_webhook_url', 'group' => 'payment', 'value' => rtrim(config('app.url'), '/') . '/api/v1/payment/zinipay/webhook', 'type' => 'text', 'label' => 'ZiniPay Webhook URL', 'is_public' => false],
            ['key' => 'enable_wallet', 'group' => 'payment', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Wallet', 'is_public' => true],
            ['key' => 'sms_gateway', 'group' => 'sms', 'value' => 'ssl_wireless', 'type' => 'text', 'label' => 'SMS Gateway', 'is_public' => false],
            ['key' => 'sms_sender_id', 'group' => 'sms', 'value' => 'BazarBD', 'type' => 'text', 'label' => 'Sender ID', 'is_public' => false],
            ['key' => 'sms_on_order_placed', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Order', 'is_public' => false],
            ['key' => 'sms_on_order_confirmed', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Confirmed', 'is_public' => false],
            ['key' => 'sms_on_order_shipped', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Shipped', 'is_public' => false],
            ['key' => 'sms_on_order_delivered', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Delivered', 'is_public' => false],
            ['key' => 'sms_on_order_cancelled', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Cancelled', 'is_public' => false],
            ['key' => 'sms_on_user_blocked', 'group' => 'sms', 'value' => '1', 'type' => 'boolean', 'label' => 'SMS on Block', 'is_public' => false],
            ['key' => 'products_per_page', 'group' => 'ecommerce', 'value' => '20', 'type' => 'number', 'label' => 'Products Per Page', 'is_public' => true],
            ['key' => 'platform_commission', 'group' => 'ecommerce', 'value' => '10', 'type' => 'number', 'label' => 'Commission %', 'is_public' => false],
            ['key' => 'enable_reviews', 'group' => 'ecommerce', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Reviews', 'is_public' => true],
            ['key' => 'auto_approve_reviews', 'group' => 'ecommerce', 'value' => '0', 'type' => 'boolean', 'label' => 'Auto Approve Reviews', 'is_public' => false],
            ['key' => 'enable_referral', 'group' => 'ecommerce', 'value' => '1', 'type' => 'boolean', 'label' => 'Enable Referral', 'is_public' => true],
            ['key' => 'referral_bonus_amount', 'group' => 'ecommerce', 'value' => '50', 'type' => 'number', 'label' => 'Referral Bonus', 'is_public' => true],
            ['key' => 'auto_block_enabled', 'group' => 'blocking', 'value' => '1', 'type' => 'boolean', 'label' => 'Auto Block', 'is_public' => false],
            ['key' => 'auto_block_ip_enabled', 'group' => 'blocking', 'value' => '1', 'type' => 'boolean', 'label' => 'Auto Block IP', 'is_public' => false],
            ['key' => 'facebook_url', 'group' => 'social', 'value' => '', 'type' => 'text', 'label' => 'Facebook URL', 'is_public' => true],
            ['key' => 'instagram_url', 'group' => 'social', 'value' => '', 'type' => 'text', 'label' => 'Instagram URL', 'is_public' => true],
            ['key' => 'youtube_url', 'group' => 'social', 'value' => '', 'type' => 'text', 'label' => 'YouTube URL', 'is_public' => true],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
        echo "✅ " . count($settings) . " settings seeded\n";
    }
}
