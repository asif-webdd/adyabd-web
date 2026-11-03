<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourierMerchantSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('courier_merchants')->insert([
            [
                'name'                  => 'Pathao Courier',
                'slug'                  => 'pathao-courier',
                'logo'                  => null,
                'api_key'               => 'pathao_api_key_demo',
                'api_secret'            => 'pathao_api_secret_demo',
                'api_username'          => null,
                'api_password'          => null,
                'base_url'              => 'https://courier.pathao.com/aladdin/api/v1',
                'tracking_url'          => 'https://pathao.com/bn/track/?trackingCode={tracking_id}',
                'config'                => json_encode(['sandbox' => false]),
                'base_charge'           => 60.00,
                'express_charge'        => 120.00,
                'cod_charge_percentage' => 1.00,
                'supports_cod'          => true,
                'supports_same_day'     => false,
                'is_active'             => true,
                'is_default'            => true,
                'sort_order'            => 1,
                'total_deliveries'      => 0,
                'success_rate'          => 92.50,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'name'                  => 'Steadfast Courier',
                'slug'                  => 'steadfast-courier',
                'logo'                  => null,
                'api_key'               => 'steadfast_api_key_demo',
                'api_secret'            => 'steadfast_api_secret_demo',
                'api_username'          => null,
                'api_password'          => null,
                'base_url'              => 'https://portal.packzy.com/api/v1',
                'tracking_url'          => 'https://steadfast.com.bd/t/{tracking_id}',
                'config'                => json_encode(['sandbox' => false]),
                'base_charge'           => 110.00,
                'express_charge'        => 150.00,
                'cod_charge_percentage' => 1.00,
                'supports_cod'          => true,
                'supports_same_day'     => false,
                'is_active'             => true,
                'is_default'            => false,
                'sort_order'            => 2,
                'total_deliveries'      => 0,
                'success_rate'          => 89.30,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
            [
                'name'                  => 'Redx',
                'slug'                  => 'redx',
                'logo'                  => null,
                'api_key'               => 'redx_api_key_demo',
                'api_secret'            => null,
                'api_username'          => null,
                'api_password'          => null,
                'base_url'              => 'https://openapi.redx.com.bd/v1.0.0-beta',
                'tracking_url'          => 'https://redx.com.bd/track-package/?trackingId={tracking_id}',
                'config'                => json_encode(['sandbox' => false]),
                'base_charge'           => 60.00,
                'express_charge'        => 100.00,
                'cod_charge_percentage' => 1.50,
                'supports_cod'          => true,
                'supports_same_day'     => true,
                'is_active'             => true,
                'is_default'            => false,
                'sort_order'            => 3,
                'total_deliveries'      => 0,
                'success_rate'          => 87.10,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ]);

        $this->command->info('CourierMerchantSeeder: 3 courier merchants seeded.');
    }
}

