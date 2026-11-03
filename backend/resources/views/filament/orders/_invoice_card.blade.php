@php
    $statusColor = match($order->status) {
        'delivered'                   => 'bdg-green',
        'pending'                     => 'bdg-yellow',
        'confirmed','processing'      => 'bdg-blue',
        'shipped','out_for_delivery'  => 'bdg-blue',
        default                       => 'bdg-red',
    };
    $statusLabel = match($order->status) {
        'pending'          => 'Pending',
        'confirmed'        => 'Confirmed',
        'processing'       => 'Processing',
        'shipped'          => 'Shipped',
        'out_for_delivery' => 'Out for Delivery',
        'delivered'        => 'Delivered',
        'cancelled'        => 'Cancelled',
        'returned'         => 'Returned',
        'refunded'         => 'Refunded',
        default            => ucfirst($order->status),
    };
    $payColor = match($order->payment_status) {
        'paid'    => 'bdg-green',
        'pending' => 'bdg-yellow',
        default   => 'bdg-red',
    };
@endphp

{{-- Header --}}
<div class="inv-head">
    <div>
        @php $__logo = shop_logo_src(); @endphp
        @if($__logo)
            <img src="{{ $__logo }}" alt="{{ shop_display_name() }}" class="shop-logo">
        @else
            <div class="shop-name">{{ shop_display_name() }}</div>
        @endif
        <div class="shop-sub">Order Invoice &amp; Receipt</div>
    </div>
    <div class="inv-title">
        <h2>Invoice</h2>
        <div class="inv-num">#{{ $order->order_number }}</div>
        <div class="inv-date">{{ $order->created_at->format('d M Y, h:i A') }}</div>
    </div>
</div>

{{-- Status badges --}}
<div class="badges">
    <span class="bdg {{ $statusColor }}">{{ $statusLabel }}</span>
    <span class="bdg {{ $payColor }}">{{ ucfirst($order->payment_status) }}</span>
    <span class="bdg bdg-slate">{{ strtoupper($order->payment_method) }}</span>
    @if($order->delivery_type)
        <span class="bdg bdg-blue">{{ ucfirst($order->delivery_type) }}</span>
    @endif
    @if($order->payment_transaction_id)
        <span style="font-size:10px;color:#64748b;margin-left:4px;">TXN: {{ $order->payment_transaction_id }}</span>
    @endif
</div>

{{-- Customer & Delivery --}}
<div class="info-row">
    <div class="info-col">
        <h4>Customer</h4>
        <div class="info-line"><span class="k">Name</span><span class="v">{{ $order->user->name ?? '—' }}</span></div>
        <div class="info-line"><span class="k">Phone</span><span class="v">{{ $order->user->phone ?? '—' }}</span></div>
        <div class="info-line"><span class="k">Email</span><span class="v">{{ $order->user->email ?? '—' }}</span></div>
        @if($order->seller)
            <div class="info-line"><span class="k">Sold By</span><span class="v">{{ $order->seller->shop_name ?? $order->seller->user->name ?? '—' }}</span></div>
        @endif
    </div>
    <div class="info-col">
        <h4>Delivery Address</h4>
        <div class="info-line"><span class="k">Recipient</span><span class="v">{{ $order->shipping_name }}</span></div>
        <div class="info-line"><span class="k">Phone</span><span class="v">{{ $order->shipping_phone }}</span></div>
        <div class="info-line"><span class="k">Address</span><span class="v">{{ $order->shipping_address }}</span></div>
        @php $area = collect([$order->shipping_upazila, $order->shipping_district, $order->shipping_division])->filter()->implode(', '); @endphp
        @if($area)<div class="info-line"><span class="k">Area</span><span class="v">{{ $area }}</span></div>@endif
        @if($order->courier_tracking_id)
            <div class="info-line"><span class="k">Tracking</span><span class="v">{{ $order->courier_tracking_id }}</span></div>
        @endif
    </div>
</div>

{{-- Items --}}
<div class="items-wrap">
    <h4>Order Items</h4>
    <table class="items">
        <thead>
        <tr>
            <th>#</th>
            <th style="text-align:left">Product</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $i => $item)
            <tr>
                <td style="color:#cbd5e1;width:20px">{{ $i+1 }}</td>
                <td>
                    <div class="pname">{{ $item->product_name }}</div>
                    @if(!empty($item->variant_options) && is_array($item->variant_options) && count($item->variant_options))
                        <div class="pvar">{{ collect($item->variant_options)->map(fn($v,$k)=>"$k: $v")->implode(' · ') }}</div>
                    @endif
                </td>
                <td>BDT {{ number_format($item->unit_price,2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td style="font-weight:700;color:#0f766e">BDT {{ number_format($item->total_price,2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="totals-wrap">
    <div class="totals">
        <div class="t-row"><span class="k">Subtotal</span><span>BDT {{ number_format($order->subtotal,2) }}</span></div>
        <div class="t-row"><span class="k">Shipping</span><span>BDT {{ number_format($order->shipping_charge,2) }}</span></div>
        @if($order->discount_amount > 0)
            <div class="t-row disc"><span class="k">Discount</span><span>− BDT {{ number_format($order->discount_amount,2) }}</span></div>
        @endif
        @if($order->coupon_discount > 0)
            <div class="t-row disc"><span class="k">Coupon @if($order->coupon_code)({{ $order->coupon_code }})@endif</span><span>− BDT {{ number_format($order->coupon_discount,2) }}</span></div>
        @endif
        <div class="t-row grand"><span>Grand Total</span><span>BDT {{ number_format($order->total_amount,2) }}</span></div>
    </div>
</div>

{{-- Footer --}}
<div class="inv-foot">
    @if($order->customer_note)
        <div class="thank">Note: {{ $order->customer_note }}</div>
    @else
        <div class="thank">Thank you for your order!</div>
    @endif
    <div class="gen">Printed: {{ now()->format('d M Y, h:i A') }}</div>
</div>
