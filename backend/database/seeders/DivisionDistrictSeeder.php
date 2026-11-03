<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Kept for backwards compatibility — the full dataset now lives in BangladeshGeoSeeder. */
class DivisionDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BangladeshGeoSeeder::class);
    }
}
