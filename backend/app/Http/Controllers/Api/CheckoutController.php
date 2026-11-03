<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\DeliveryCharge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * GET /api/v1/checkout/config?address_id=X  (public — no auth required)
     *
     * Returns everything the checkout page needs to render dynamically:
     *  - delivery methods with live prices (city-based for standard)
     *  - payment methods filtered by enabled/disabled toggles in settings
     *  - free shipping threshold
     *
     * When address_id is provided, standard delivery charge is resolved
     * from the delivery_charges table based on the address's district/division.
     */
    public function config(Request $request): JsonResponse
    {
        // ── Resolve city-based standard charge ───────────────────────────────
        $district = null;
        $division = null;
        $upazila  = null;

        if ($request->address_id) {
            $address  = Address::with(['district', 'division', 'upazila'])
                               ->find($request->address_id);
            $district = $address?->district?->name ?? $address?->district;
            $division = $address?->division?->name ?? $address?->division;
            $upazila  = $address?->upazila?->name  ?? $address?->upazila;
        }

        $standardCharge = DeliveryCharge::resolveCharge($district, $division, $upazila);
        $expressCharge  = (float) setting('express_shipping_charge', 120);
        $sameDayCharge  = (float) setting('same_day_shipping_charge', 150);
        $freeMinOrder   = (float) setting('free_shipping_min_order', 500);

        // ── Build delivery methods list ───────────────────────────────────────
        $deliveryMethods = [
            [
                'id'       => 'standard',
                'name'     => 'Standard Delivery',
                'desc'     => '2–3 business days (nationwide)',
                'icon'     => 'local_shipping',
                'color'    => 'text-primary',
                'price'    => $standardCharge,
                'area'     => $district ?? $division ?? null, // for display in frontend
            ],
            [
                'id'       => 'express',
                'name'     => 'Express Delivery',
                'desc'     => '1 business day (Dhaka & Chittagong)',
                'icon'     => 'bolt',
                'color'    => 'text-yellow-500',
                'price'    => $expressCharge,
                'area'     => null,
            ],
            [
                'id'       => 'same_day',
                'name'     => 'Same Day Delivery',
                'desc'     => 'Delivered today (Dhaka only)',
                'icon'     => 'directions_run',
                'color'    => 'text-accent',
                'price'    => $sameDayCharge,
                'area'     => null,
            ],
        ];

        $deliveryToggles = [
            'standard' => 'enable_standard_shipping',
            'express'  => 'enable_express_shipping',
            'same_day' => 'enable_same_day_shipping',
        ];

        $deliveryMethods = array_values(array_filter(
            $deliveryMethods,
            function ($method) use ($deliveryToggles) {
                $key = $deliveryToggles[$method['id']] ?? null;
                if (!$key) return true;
                return (bool) setting($key, true);
            }
        ));

        // ── Payment methods ───────────────────────────────────────────────────
        $allPaymentMethods = [
            [
                'id'    => 'cod',
                'name'  => 'Cash on Delivery',
                'sub'   => 'Pay when you receive the item',
                'icon'  => 'payments',
                'color' => 'text-green-600',
                'bg'    => 'bg-green-50',
                'badge' => 'Easy',
            ],
            [
                'id'    => 'zinipay',
                'name'  => 'ZiniPay',
                'sub'   => 'Pay with bKash, Nagad, Rocket & more',
                'icon'  => 'qr_code_scanner',
                'color' => 'text-violet-600',
                'bg'    => 'bg-violet-50',
                'badge' => null,
            ],
            [
                'id'    => 'wallet',
                'name'  => config('app.name') . ' Wallet',
                'sub'   => 'Instant payment from balance',
                'icon'  => 'account_balance_wallet',
                'color' => 'text-primary',
                'bg'    => 'bg-primary/10',
                'badge' => null,
            ],
        ];

        $paymentToggles = [
            'cod'     => 'enable_cod',
            'zinipay' => 'enable_zinipay',
            'wallet'  => 'enable_wallet',
        ];

        $paymentMethods = array_values(array_filter(
            $allPaymentMethods,
            function ($method) use ($paymentToggles) {
                $key = $paymentToggles[$method['id']] ?? null;
                if (!$key) return true;
                return (bool) setting($key, true);
            }
        ));

        return $this->success([
            'delivery_methods'        => $deliveryMethods,
            'payment_methods'         => $paymentMethods,
            'free_shipping_min_order' => $freeMinOrder,
            'resolved_district'       => $district,  // for frontend label
        ]);
    }
}
