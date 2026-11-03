<div class="space-y-3 p-2">
    @forelse($orders as $order)
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4 space-y-3">
            {{-- Header row --}}
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="font-mono font-bold text-white text-sm">{{ $order->order_number }}</span>
                    <span class="ml-3 text-gray-400 text-xs">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @php
                        $statusColor = match($order->status) {
                            'delivered'        => 'bg-green-600',
                            'pending'          => 'bg-yellow-600',
                            'confirmed',
                            'processing'       => 'bg-blue-600',
                            'shipped',
                            'out_for_delivery' => 'bg-indigo-600',
                            default            => 'bg-red-600',
                        };
                        $statusLabel = match($order->status) {
                            'pending'          => '⏳ Pending',
                            'confirmed'        => ' Confirmed',
                            'processing'       => ' Processing',
                            'shipped'          => ' Shipped',
                            'out_for_delivery' => ' Out for Delivery',
                            'delivered'        => ' Delivered',
                            'cancelled'        => ' Cancelled',
                            'returned'         => '↩ Returned',
                            'refunded'         => ' Refunded',
                            default            => ucfirst($order->status),
                        };
                        $payColor = match($order->payment_status ?? 'pending') {
                            'paid'     => 'bg-green-700',
                            'failed'   => 'bg-red-700',
                            'refunded' => 'bg-gray-600',
                            default    => 'bg-yellow-700',
                        };
                    @endphp
                    <span class="text-xs px-2 py-1 rounded-full text-white {{ $statusColor }}">{{ $statusLabel }}</span>
                    <span class="text-xs px-2 py-1 rounded-full text-white {{ $payColor }}">
                        {{ ucfirst($order->payment_status ?? 'pending') }}
                    </span>
                </div>
            </div>

            {{-- Customer + financials --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div>
                    <p class="text-gray-400 text-xs">Customer</p>
                    <p class="text-white font-medium">{{ $order->user?->name ?? 'Guest' }}</p>
                    <p class="text-gray-400 text-xs">{{ $order->shipping_phone }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Total</p>
                    <p class="text-white font-bold">BDT  {{ number_format($order->total_amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Seller Payout</p>
                    <p class="text-green-400 font-semibold">BDT  {{ number_format($order->seller_payout, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Commission</p>
                    <p class="text-yellow-400">BDT  {{ number_format($order->commission_amount, 2) }}</p>
                </div>
            </div>

            {{-- Items --}}
            @if($order->items->count())
                <div class="border-t border-gray-700 pt-2 space-y-1">
                    @foreach($order->items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-300">{{ $item->product_name }} <span class="text-gray-500">×{{ $item->quantity }}</span></span>
                            <span class="text-white">BDT  {{ number_format($item->total_price, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Courier --}}
            @if($order->courier_tracking_id)
                <div class="text-xs text-gray-400">
                     {{ $order->courierMerchant?->name ?? 'Courier' }} —
                    <span class="font-mono text-gray-300">{{ $order->courier_tracking_id }}</span>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-400 py-10">No orders found.</div>
    @endforelse
</div>
