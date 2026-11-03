<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Adds the 'enable_zinipay' payment toggle and removes the now-unused
 * bKash / Nagad / Rocket / EMI toggles from existing installs (the
 * checkout no longer offers these payment methods — see CheckoutController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'enable_zinipay'],
            ['value' => '1', 'group' => 'payment', 'type' => 'boolean', 'label' => 'Enable ZiniPay', 'is_public' => true]
        );

        Setting::whereIn('key', ['enable_bkash', 'enable_nagad', 'enable_rocket', 'enable_emi', 'emi_min_amount'])->delete();
    }

    public function down(): void
    {
        Setting::where('key', 'enable_zinipay')->delete();

        foreach ([
            ['key' => 'enable_bkash', 'value' => '1', 'label' => 'Enable bKash'],
            ['key' => 'enable_nagad', 'value' => '1', 'label' => 'Enable Nagad'],
            ['key' => 'enable_rocket', 'value' => '1', 'label' => 'Enable Rocket'],
            ['key' => 'enable_emi', 'value' => '0', 'label' => 'Enable EMI'],
        ] as $s) {
            Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value'], 'group' => 'payment', 'type' => 'boolean', 'label' => $s['label'], 'is_public' => true]);
        }
        Setting::updateOrCreate(['key' => 'emi_min_amount'], ['value' => '5000', 'group' => 'payment', 'type' => 'number', 'label' => 'EMI Min Amount', 'is_public' => true]);
    }
};
