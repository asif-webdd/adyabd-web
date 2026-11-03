<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

class CourierMerchant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'api_key', 'api_secret', 'api_username', 'api_password',
        'base_url', 'tracking_url', 'config', 'base_charge', 'express_charge',
        'cod_charge_percentage', 'supports_cod', 'supports_same_day', 'is_active',
        'is_default', 'sort_order', 'total_deliveries', 'success_rate',
    ];

    protected $casts = [
        'config'            => 'array',
        'supports_cod'      => 'boolean',
        'supports_same_day' => 'boolean',
        'is_active'         => 'boolean',
        'is_default'        => 'boolean',
        'base_charge'       => 'decimal:2',
        'express_charge'    => 'decimal:2',
        'success_rate'      => 'decimal:2',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    public function getTrackingUrl(string $id): string
    {
        return $this->tracking_url ? str_replace('{tracking_id}', $id, $this->tracking_url) : '#';
    }

    /**
     * Build the full delivery-status webhook URL for this courier merchant,
     * including the secret path segment if one is configured.
     * Returns null if this courier has no registered webhook route.
     */
    public function getWebhookUrl(): ?string
    {
        $routeMap = [
            'steadfast-courier' => 'api.webhooks.steadfast',
            'steadfast'         => 'api.webhooks.steadfast',
        ];

        $routeName = $routeMap[strtolower($this->slug)] ?? null;

        if (!$routeName || !Route::has($routeName)) {
            return null;
        }

        $secret = $this->getWebhookSecret();

        return $secret ? route($routeName, ['secret' => $secret]) : route($routeName);
    }

    /**
     * Get the configured webhook secret (if any).
     */
    public function getWebhookSecret(): ?string
    {
        return $this->config['webhook_secret'] ?? null;
    }
}
