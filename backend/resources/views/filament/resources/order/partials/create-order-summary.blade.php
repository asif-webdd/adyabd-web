<div style="font-size:13px">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
        <span style="color:#6b7280">Items</span>
        <strong>{{ $itemCount }}</strong>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
        <span style="color:#6b7280">Subtotal</span>
        <strong>BDT  {{ number_format($subtotal, 2) }}</strong>
    </div>

    @if ($discount > 0)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Discount</span>
            <strong style="color:#dc2626">− BDT  {{ number_format($discount, 2) }}</strong>
        </div>
    @endif

    @if ($couponDiscount > 0)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Coupon @if($couponCode)({{ $couponCode }})@endif</span>
            <strong style="color:#dc2626">− BDT  {{ number_format($couponDiscount, 2) }}</strong>
        </div>
    @elseif ($couponCode)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Coupon ({{ $couponCode }})</span>
            <strong style="color:#dc2626">Invalid</strong>
        </div>
    @endif

    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
        <span style="color:#6b7280">Shipping</span>
        <strong>BDT  {{ number_format($shipping, 2) }}</strong>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0 2px;margin-top:4px;border-top:2px solid #e5e7eb">
        <span style="font-weight:700;font-size:14px;color:#111827">Total</span>
        <strong style="font-size:17px;color:#006A4E">BDT  {{ number_format($total, 2) }}</strong>
    </div>
</div>
