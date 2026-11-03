<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Database\Seeder;

/**
 * Complete Bangladesh geo data: 8 divisions, 64 districts, all upazilas (+ metropolitan thanas).
 *
 * Idempotent and non-destructive: rows are matched by their Bangla name (within the parent), so existing
 * ids that orders / addresses / delivery charges already point at are kept — only missing rows are added.
 * Data lives in database/data/bangladesh_geo.php.
 */
class BangladeshGeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/bangladesh_geo.php');
        $nd = $nu = 0;

        foreach ($data as $divKey => $districts) {
            [$divEn, $divBn] = array_map('trim', explode('|', $divKey));
            $division = Division::where('name_bn', $divBn)->first()
                ?? Division::where('name', $divEn)->first()
                ?? Division::create(['name' => $divEn, 'name_bn' => $divBn]);

            foreach ($districts as $distKey => $upaList) {
                [$disEn, $disBn] = array_map('trim', explode('|', $distKey));
                $district = District::where('division_id', $division->id)->where('name_bn', $disBn)->first();
                if (!$district) {
                    $district = District::create(['division_id' => $division->id, 'name' => $disEn, 'name_bn' => $disBn]);
                    $nd++;
                }

                $have = Upazila::where('district_id', $district->id)->pluck('name_bn')->all();
                foreach (explode(';', $upaList) as $entry) {
                    [$en, $bn] = array_map('trim', explode('|', $entry));
                    if (in_array($bn, $have, true)) continue;
                    Upazila::create(['district_id' => $district->id, 'name' => $en, 'name_bn' => $bn]);
                    $have[] = $bn;
                    $nu++;
                }
            }
        }

        if ($this->command) {
            $this->command->info("Bangladesh geo: +{$nd} districts, +{$nu} upazilas/thanas (now "
                . District::count() . ' districts, ' . Upazila::count() . ' upazilas).');
        }
    }
}
