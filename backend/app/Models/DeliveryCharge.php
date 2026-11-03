<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCharge extends Model
{
    protected $fillable = ['area_name', 'area_type', 'charge', 'is_active', 'sort_order'];

    protected $casts = [
        'charge'     => 'decimal:2',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Resolve the standard delivery charge for a given address.
     *
     * Source 1: the `delivery_charges` table (Admin → Settings → Delivery Charges).
     * Source 2 (legacy): the `city_delivery_charges` JSON setting in System Settings.
     * Priority: upazila match > district match > division match > global default.
     *
     * @param  string|null  $district
     * @param  string|null  $division
     * @param  string|null  $upazila
     * @return float
     */
    /** Canonical lower-case key for an area name so alternative English spellings match. */
    public static function canon(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        $n = preg_replace("/[^a-z0-9\p{L}]+/u", '', $n);
        $alias = [
            'chittagong' => 'chattogram', 'comilla' => 'cumilla', 'jessore' => 'jashore', 'bogra' => 'bogura',
            'barisal' => 'barishal', 'coxsbazar' => 'coxsbazar', 'chapainawabganj' => 'chapainawabganj',
            'chapaiNawabganj' => 'chapainawabganj', 'maulvibazar' => 'moulvibazar', 'jhalakathi' => 'jhalokati',
            'jhalokathi' => 'jhalokati', 'netrakona' => 'netrokona', 'khagrachari' => 'khagrachhari',
            'brahmanbaria' => 'brahmanbaria', 'nawabganj' => 'nawabganj',
        ];
        return $alias[$n] ?? $n;
    }

    public static function resolveCharge(
        ?string $district,
        ?string $division = null,
        ?string $upazila  = null,
    ): float {
        // 1. Admin-managed table (active rows only), most specific area wins.
        //    Names are compared in a spelling-tolerant way (Chittagong = Chattogram, Barisal = Barishal …).
        foreach ([['upazila', $upazila], ['district', $district], ['division', $division]] as [$type, $name]) {
            if (!$name) continue;
            $want = static::canon($name);
            $row = static::query()
                ->where('is_active', true)
                ->where('area_type', $type)
                ->orderBy('sort_order')
                ->get()
                ->first(fn ($r) => static::canon($r->area_name) === $want);
            if ($row) return (float) $row->charge;
        }

        // 2. Legacy JSON setting (kept for backward compatibility).
        $charges = Setting::get('city_delivery_charges', []);

        if (!is_array($charges) || empty($charges)) {
            return (float) setting('standard_shipping_charge', 60);
        }

        // 1. Upazila match (most specific)
        if ($upazila) {
            foreach ($charges as $row) {
                if (
                    ($row['area_type'] ?? '') === 'upazila' &&
                    strtolower(trim($row['area_name'] ?? '')) === strtolower(trim($upazila))
                ) {
                    return (float) ($row['charge'] ?? 0);
                }
            }
        }

        // 2. District match
        if ($district) {
            foreach ($charges as $row) {
                if (
                    ($row['area_type'] ?? '') === 'district' &&
                    strtolower(trim($row['area_name'] ?? '')) === strtolower(trim($district))
                ) {
                    return (float) ($row['charge'] ?? 0);
                }
            }
        }

        // 3. Division match
        if ($division) {
            foreach ($charges as $row) {
                if (
                    ($row['area_type'] ?? '') === 'division' &&
                    strtolower(trim($row['area_name'] ?? '')) === strtolower(trim($division))
                ) {
                    return (float) ($row['charge'] ?? 0);
                }
            }
        }

        // 4. Fallback to global standard shipping charge
        return (float) setting('standard_shipping_charge', 60);
    }
}
