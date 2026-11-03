<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourierMerchant;

class CourierMerchantsSeeder extends Seeder
{
    public function run(): void
    {
        $couriers = [
            [
                'name'                   => 'SteadFast',
                'slug'                   => 'steadfast',
                'base_url'               => 'https://portal.steadfast.com.bd/public/api',
                'tracking_url'           => 'https://portal.steadfast.com.bd/public/track/{tracking_id}',
                'base_charge'            => 60,
                'express_charge'         => 120,
                'cod_charge_percentage'  => 1.0,
                'supports_cod'           => true,
                'supports_same_day'      => false,
                'is_active'              => true,
                'is_default'             => true,
                'sort_order'             => 1,
            ],
            [
                'name'                   => 'Pathao',
                'slug'                   => 'pathao',
                'base_url'               => 'https://api-hermes.pathao.com',
                'tracking_url'           => 'https://merchant.pathao.com/order/{tracking_id}',
                'base_charge'            => 65,
                'express_charge'         => 130,
                'cod_charge_percentage'  => 1.0,
                'supports_cod'           => true,
                'supports_same_day'      => true,
                'is_active'              => true,
                'is_default'             => false,
                'sort_order'             => 2,
            ],
            [
                'name'                   => 'Redx',
                'slug'                   => 'redx',
                'base_url'               => 'https://openapi.redx.com.bd',
                'tracking_url'           => 'https://redx.com.bd/track-parcel/?trackingId={tracking_id}',
                'base_charge'            => 55,
                'express_charge'         => 110,
                'cod_charge_percentage'  => 1.0,
                'supports_cod'           => true,
                'supports_same_day'      => false,
                'is_active'              => true,
                'is_default'             => false,
                'sort_order'             => 3,
            ],
            [
                'name'                   => 'Paperfly',
                'slug'                   => 'paperfly',
                'base_url'               => 'https://paperfly.com.bd/api',
                'tracking_url'           => 'https://paperfly.com.bd/track?id={tracking_id}',
                'base_charge'            => 70,
                'express_charge'         => 140,
                'cod_charge_percentage'  => 1.0,
                'supports_cod'           => true,
                'supports_same_day'      => false,
                'is_active'              => true,
                'is_default'             => false,
                'sort_order'             => 4,
            ],
            [
                'name'                   => 'Sundarban Courier',
                'slug'                   => 'sundarban',
                'base_url'               => 'https://www.sundarbanexpress.net/api',
                'tracking_url'           => 'https://www.sundarbanexpress.net/track/{tracking_id}',
                'base_charge'            => 80,
                'express_charge'         => 150,
                'cod_charge_percentage'  => 1.0,
                'supports_cod'           => true,
                'supports_same_day'      => false,
                'is_active'              => true,
                'is_default'             => false,
                'sort_order'             => 5,
            ],
        ];

        foreach ($couriers as $c) {
            CourierMerchant::updateOrCreate(['slug' => $c['slug']], $c);
        }
        echo "✅ " . count($couriers) . " courier merchants seeded\n";
    }
}
