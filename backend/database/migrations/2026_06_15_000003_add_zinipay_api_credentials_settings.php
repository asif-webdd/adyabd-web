<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Makes the ZiniPay API key and base URL admin-editable (Settings → Payment)
 * so they can be changed without touching .env or redeploying.
 *
 * PaymentService reads these settings first and falls back to the .env
 * values (services.zinipay.api_key / base_url) if they are empty — so any
 * existing ZINIPAY_API_KEY in .env keeps working until the admin sets one
 * here, and is used as the initial value below if present.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'zinipay_api_key'],
            [
                'value'     => config('services.zinipay.api_key', ''),
                'group'     => 'payment',
                'type'      => 'text',
                'label'     => 'ZiniPay API Key',
                'is_public' => false,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'zinipay_base_url'],
            [
                'value'     => config('services.zinipay.base_url', 'https://api.zinipay.com'),
                'group'     => 'payment',
                'type'      => 'text',
                'label'     => 'ZiniPay API Base URL',
                'is_public' => false,
            ]
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['zinipay_api_key', 'zinipay_base_url'])->delete();
    }
};
