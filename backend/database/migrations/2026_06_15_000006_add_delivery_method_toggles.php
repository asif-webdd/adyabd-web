<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Adds admin-editable on/off toggles for each delivery method
 * (Settings → Delivery → Delivery Charges): Standard, Express, Same Day.
 * CheckoutController::config() filters delivery_methods by these.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'enable_standard_shipping' => 'Enable Standard Delivery',
            'enable_express_shipping'  => 'Enable Express Delivery',
            'enable_same_day_shipping' => 'Enable Same Day Delivery',
        ] as $key => $label) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => '1', 'group' => 'delivery', 'type' => 'boolean', 'label' => $label, 'is_public' => true]
            );
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', ['enable_standard_shipping', 'enable_express_shipping', 'enable_same_day_shipping'])->delete();
    }
};
