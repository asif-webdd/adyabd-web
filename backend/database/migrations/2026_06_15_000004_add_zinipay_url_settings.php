<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Makes the ZiniPay redirect (frontend) URL and webhook URL admin-editable
 * (Settings → Payment), seeded from the current .env-derived values so
 * nothing changes until an admin updates them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'zinipay_frontend_url'],
            [
                'value'     => rtrim(config('app.frontend_url'), '/'),
                'group'     => 'payment',
                'type'      => 'text',
                'label'     => 'ZiniPay Frontend URL',
                'is_public' => false,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'zinipay_webhook_url'],
            [
                'value'     => rtrim(config('app.url'), '/') . '/api/v1/payment/zinipay/webhook',
                'group'     => 'payment',
                'type'      => 'text',
                'label'     => 'ZiniPay Webhook URL',
                'is_public' => false,
            ]
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['zinipay_frontend_url', 'zinipay_webhook_url'])->delete();
    }
};
