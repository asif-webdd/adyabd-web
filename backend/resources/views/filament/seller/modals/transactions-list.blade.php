<div class="space-y-2 p-2">
    @forelse($transactions as $tx)
        @php
            $isCredit = in_array($tx->type, ['credit', 'sale', 'refund_received']);
            $typeColor = $isCredit ? 'text-green-400' : 'text-red-400';
            $amountPrefix = $isCredit ? '+' : '-';
            $badgeColor = match($tx->status ?? 'completed') {
                'completed' => 'bg-green-900 text-green-300',
                'pending'   => 'bg-yellow-900 text-yellow-300',
                'failed'    => 'bg-red-900 text-red-300',
                default     => 'bg-gray-700 text-gray-300',
            };
        @endphp
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-lg
                        {{ $isCredit ? 'bg-green-900' : 'bg-red-900' }}">
                        {{ $isCredit ? '↓' : '↑' }}
                    </div>
                    <div>
                        <p class="{{ $typeColor }} font-semibold text-sm capitalize">
                            {{ str_replace('_', ' ', $tx->type) }}
                        </p>
                        <p class="text-gray-400 text-xs">{{ $tx->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="{{ $typeColor }} font-bold text-base">
                        {{ $amountPrefix }} BDT {{ number_format($tx->amount, 2) }}
                    </p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeColor }}">
                        {{ ucfirst($tx->status ?? 'completed') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-xs">
                <div>
                    <p class="text-gray-500">Balance Before</p>
                    <p class="text-gray-300">BDT  {{ number_format($tx->balance_before ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Balance After</p>
                    <p class="text-gray-300">BDT  {{ number_format($tx->balance_after ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Method</p>
                    <p class="text-gray-300">{{ $tx->payment_method ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Ref ID</p>
                    <p class="text-gray-300 font-mono">{{ $tx->transaction_id ?? '—' }}</p>
                </div>
            </div>

            @if($tx->description)
                <p class="text-gray-500 text-xs mt-2">{{ $tx->description }}</p>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-400 py-10">No transactions found.</div>
    @endforelse
</div>
