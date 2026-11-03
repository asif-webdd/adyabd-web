<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AutoBlockRule;

class AutoBlockRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name'            => 'বেশি অর্ডার বাতিলকারী',
                'trigger'         => 'cancel_order',
                'threshold'       => 3,
                'within_days'     => 30,
                'block_duration'  => '30 days',
                'block_ip_also'   => true,
                'reason_template' => '{count} বার অর্ডার বাতিল করার কারণে আপনার অ্যাকাউন্ট ৩০ দিনের জন্য স্থগিত করা হয়েছে।',
                'is_active'       => true,
            ],
            [
                'name'            => 'ফোন না ধরা গ্রাহক',
                'trigger'         => 'no_answer',
                'threshold'       => 3,
                'within_days'     => 60,
                'block_duration'  => '30 days',
                'block_ip_also'   => true,
                'reason_template' => 'ডেলিভারির সময় {count} বার ফোন না ধরার কারণে অ্যাকাউন্ট স্থগিত।',
                'is_active'       => true,
            ],
            [
                'name'            => 'ব্যর্থ ডেলিভারি',
                'trigger'         => 'failed_delivery',
                'threshold'       => 3,
                'within_days'     => 90,
                'block_duration'  => '90 days',
                'block_ip_also'   => true,
                'reason_template' => '{count} বার ডেলিভারি ব্যর্থ হওয়ার কারণে অ্যাকাউন্ট স্থগিত।',
                'is_active'       => true,
            ],
        ];

        foreach ($rules as $r) {
            AutoBlockRule::updateOrCreate(['trigger' => $r['trigger']], $r);
        }
        echo "✅ " . count($rules) . " auto-block rules seeded\n";
    }
}
