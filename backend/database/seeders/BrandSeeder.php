<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $brands = [
            ['name' => 'Aarong',      'slug' => 'aarong',      'description' => 'Bangladesh\'s leading lifestyle brand.',          'website' => 'https://www.aarong.com',    'is_active' => true, 'sort_order' => 1],
            ['name' => 'Bata',        'slug' => 'bata',        'description' => 'Global footwear brand with strong BD presence.',  'website' => 'https://www.bata.com.bd',  'is_active' => true, 'sort_order' => 2],
            ['name' => 'Walton',      'slug' => 'walton',      'description' => 'Leading Bangladeshi electronics manufacturer.',   'website' => 'https://waltonbd.com',     'is_active' => true, 'sort_order' => 3],
            ['name' => 'Samsung',     'slug' => 'samsung',     'description' => 'Global electronics and technology leader.',       'website' => 'https://www.samsung.com',  'is_active' => true, 'sort_order' => 4],
            ['name' => 'Sony',        'slug' => 'sony',        'description' => 'World-renowned audio and electronics brand.',     'website' => 'https://www.sony.com',     'is_active' => true, 'sort_order' => 5],
            ['name' => 'Pran',        'slug' => 'pran',        'description' => 'Popular Bangladeshi food and beverage brand.',    'website' => 'https://www.pranfoods.net','is_active' => true, 'sort_order' => 6],
            ['name' => 'Square',      'slug' => 'square',      'description' => 'Bangladeshi pharmaceutical and consumer goods.',  'website' => 'https://www.squaregroup.com','is_active' => true,'sort_order' => 7],
            ['name' => 'Unilever BD', 'slug' => 'unilever-bd', 'description' => 'Personal care and food brands in Bangladesh.',   'website' => 'https://www.unilever.com.bd','is_active' => true,'sort_order' => 8],
        ];

        foreach ($brands as &$brand) {
            $brand['logo']       = null;
            $brand['created_at'] = $now;
            $brand['updated_at'] = $now;
        }

        DB::table('brands')->insert($brands);

        $this->command->info('BrandSeeder: ' . count($brands) . ' brands seeded.');
    }
}
