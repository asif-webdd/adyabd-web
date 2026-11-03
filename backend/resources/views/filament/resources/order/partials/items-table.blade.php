@if (!$record)
    <p class="text-sm text-gray-400">No items found.</p>
@else
    <table style="width:100%;border-collapse:collapse">
        <thead>
        <tr style="border-bottom:2px solid #e5e7eb">
            <th style="text-align:left;padding:8px;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Product</th>
            <th style="text-align:right;padding:8px;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Qty</th>
            <th style="text-align:right;padding:8px;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Unit price</th>
            <th style="text-align:right;padding:8px;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($record->items as $item)
            @php
                $variantOptions = $item->variant_options;
                if (is_string($variantOptions)) {
                    $variantOptions = json_decode($variantOptions, true);
                }

                $meta = collect([
                    is_array($variantOptions) && !empty($variantOptions) ? collect($variantOptions)->map(fn($value, $key) => "{$key}: {$value}")->join(' · ') : '',
                ])->filter()->join(' · ');
            @endphp
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #f0f0f0">
                    <p style="margin:0;font-weight:600;font-size:13px">{{ $item->product_name }}</p>
                    @if ($meta)
                        <span style="font-size:11px;color:#9ca3af">{{ $meta }}</span>
                    @endif
                </td>
                <td style="padding:10px 8px;border-bottom:1px solid #f0f0f0;text-align:right;font-size:13px">
                    {{ $item->quantity }}
                </td>
                <td style="padding:10px 8px;border-bottom:1px solid #f0f0f0;text-align:right;font-size:13px">
                    BDT  {{ number_format($item->unit_price, 0) }}
                </td>
                <td style="padding:10px 8px;border-bottom:1px solid #f0f0f0;text-align:right;font-size:13px;font-weight:600">
                    BDT  {{ number_format($item->quantity * $item->unit_price, 0) }}
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3" style="padding:8px;text-align:right;color:#6b7280;font-size:13px">Subtotal</td>
            <td style="padding:8px;text-align:right;font-size:13px">
                BDT  {{ number_format($record->items->sum(fn($i) => $i->quantity * $i->unit_price), 0) }}
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding:6px 8px;text-align:right;color:#6b7280;font-size:13px">Delivery charge</td>
            <td style="padding:6px 8px;text-align:right;font-size:13px">
                BDT  {{ number_format($record->shipping_charge ?? 0, 0) }}
            </td>
        </tr>
        @if (($record->discount_amount ?? 0) > 0)
            <tr>
                <td colspan="3" style="padding:6px 8px;text-align:right;color:#6b7280;font-size:13px">Discount</td>
                <td style="padding:6px 8px;text-align:right;color:#16a34a;font-size:13px">
                    − BDT  {{ number_format($record->discount_amount, 0) }}
                </td>
            </tr>
        @endif
        @if (($record->coupon_discount ?? 0) > 0)
            <tr>
                <td colspan="3" style="padding:6px 8px;text-align:right;color:#6b7280;font-size:13px">
                    Coupon {{ $record->coupon_code ? '(' . $record->coupon_code . ')' : '' }}
                </td>
                <td style="padding:6px 8px;text-align:right;color:#16a34a;font-size:13px">
                    − BDT  {{ number_format($record->coupon_discount, 0) }}
                </td>
            </tr>
        @endif
        <tr style="border-top:2px solid #e5e7eb">
            <td colspan="3" style="padding:10px 8px;text-align:right;font-weight:700;font-size:14px">Total</td>
            <td style="padding:10px 8px;text-align:right;font-weight:700;font-size:14px">
                BDT  {{ number_format($record->total_amount, 0) }}
            </td>
        </tr>
        </tfoot>
    </table>
@endif
