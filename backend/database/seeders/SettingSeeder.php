<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now      = now();
        $settings = [
            // General
            ['key' => 'site_name',         'group' => 'general', 'value' => 'BazarBD',              'type' => 'text',    'label' => 'Site Name',          'is_public' => true],
            ['key' => 'site_name_bn',      'group' => 'general', 'value' => 'বাজারবিডি',           'type' => 'text',    'label' => 'Site Name (Bangla)', 'is_public' => true],
            ['key' => 'site_tagline',      'group' => 'general', 'value' => 'Your Trusted Marketplace','type'=>'text',   'label' => 'Tagline',            'is_public' => true],
            ['key' => 'site_email',        'group' => 'general', 'value' => 'support@bazarbd.com',  'type' => 'text',    'label' => 'Support Email',      'is_public' => true],
            ['key' => 'site_phone',        'group' => 'general', 'value' => '01700000000',          'type' => 'text',    'label' => 'Support Phone',      'is_public' => true],
            ['key' => 'site_address',      'group' => 'general', 'value' => 'Dhaka, Bangladesh',    'type' => 'textarea','label' => 'Address',            'is_public' => true],
            ['key' => 'currency',          'group' => 'general', 'value' => 'BDT',                  'type' => 'text',    'label' => 'Currency Code',      'is_public' => true],
            ['key' => 'currency_symbol',   'group' => 'general', 'value' => '৳',                   'type' => 'text',    'label' => 'Currency Symbol',    'is_public' => true],
            // Orders
            ['key' => 'min_order_amount',  'group' => 'orders',  'value' => '100',                  'type' => 'number',  'label' => 'Minimum Order (৳)',  'is_public' => false],
            ['key' => 'free_shipping_above','group'=> 'orders',  'value' => '999',                  'type' => 'number',  'label' => 'Free Shipping Above','is_public' => true],
            ['key' => 'standard_shipping', 'group' => 'orders',  'value' => '60',                   'type' => 'number',  'label' => 'Standard Shipping',  'is_public' => true],
            ['key' => 'express_shipping',  'group' => 'orders',  'value' => '120',                  'type' => 'number',  'label' => 'Express Shipping',   'is_public' => true],
            // SEO
            ['key' => 'meta_title',        'group' => 'seo',     'value' => 'BazarBD — Shop Smart', 'type' => 'text',    'label' => 'Meta Title',         'is_public' => true],
            ['key' => 'meta_description',  'group' => 'seo',     'value' => 'Bangladesh\'s trusted online marketplace for fashion, electronics, food and more.', 'type' => 'textarea', 'label' => 'Meta Description', 'is_public' => true],
            // Social
            ['key' => 'facebook_url',      'group' => 'social',  'value' => 'https://facebook.com/bazarbd',  'type' => 'text', 'label' => 'Facebook', 'is_public' => true],
            ['key' => 'instagram_url',     'group' => 'social',  'value' => 'https://instagram.com/bazarbd', 'type' => 'text', 'label' => 'Instagram','is_public' => true],
            // Maintenance
            ['key' => 'maintenance_mode',  'group' => 'system',  'value' => '0',                    'type' => 'boolean', 'label' => 'Maintenance Mode',  'is_public' => false],
            ['key' => 'registration_open', 'group' => 'system',  'value' => '1',                    'type' => 'boolean', 'label' => 'Registration Open', 'is_public' => false],
        ];

        foreach ($settings as &$s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
        }

        DB::table('settings')->insert($settings);

        $this->command->info('SettingSeeder: ' . count($settings) . ' settings seeded.');
    }
}
