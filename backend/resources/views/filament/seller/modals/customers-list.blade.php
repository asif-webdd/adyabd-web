<div class="space-y-3 p-2">
    @forelse($customers as $customer)
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-700 flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($customer->user?->name ?? 'G', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm">{{ $customer->user?->name ?? 'Unknown' }}</p>
                        <p class="text-gray-400 text-xs">{{ $customer->user?->phone ?? $customer->user?->email ?? '—' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center text-sm">
                    <div>
                        <p class="text-gray-400 text-xs">Orders</p>
                        <p class="text-blue-400 font-bold">{{ $customer->total_orders }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Total Spent</p>
                        <p class="text-green-400 font-bold">BDT  {{ number_format($customer->total_spent, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Last Order</p>
                        <p class="text-gray-300 text-xs">{{ \Carbon\Carbon::parse($customer->last_order_at)->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-400 py-10">No customers found.</div>
    @endforelse
</div>
