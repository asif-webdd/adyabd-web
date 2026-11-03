<div class="space-y-3 p-2">
    @forelse($products as $product)
        @php
            $isLowStock = $product->quantity <= ($product->low_stock_threshold ?? 5);
        @endphp
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="flex gap-4">
                {{-- Image --}}
                <div class="flex-shrink-0">
                    @if($product->primaryImage)
                        <img src="{{ $product->primaryImage->full_url_image }}" class="w-16 h-16 rounded-lg object-cover" alt="{{ $product->name }}">
                    @else
                        <div class="w-16 h-16 rounded-lg bg-gray-700 flex items-center justify-center text-gray-500 text-xl"></div>
                    @endif
                </div>

                {{-- Details --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <p class="text-white font-semibold text-sm">{{ $product->name }}</p>
                            @if($product->name_bn)
                                <p class="text-gray-400 text-xs">{{ $product->name_bn }}</p>
                            @endif
                            <p class="text-gray-500 text-xs mt-0.5">SKU: {{ $product->sku ?? '—' }}</p>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            @if($product->category)
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full">{{ $product->category->name }}</span>
                            @endif
                            @if($product->brand)
                                <span class="text-xs bg-blue-900 text-blue-300 px-2 py-0.5 rounded-full">{{ $product->brand->name }}</span>
                            @endif
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $product->is_active ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($product->is_featured)
                                <span class="text-xs bg-yellow-900 text-yellow-300 px-2 py-0.5 rounded-full"> Featured</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs">Price</p>
                            <p class="text-white font-bold">BDT  {{ number_format($product->price, 2) }}</p>
                            @if($product->compare_price)
                                <p class="text-gray-500 text-xs line-through">BDT  {{ number_format($product->compare_price, 2) }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Stock</p>
                            <p class="{{ $isLowStock ? 'text-red-400' : 'text-green-400' }} font-semibold">
                                {{ $product->quantity }} {{ $product->unit ?? '' }}
                            </p>
                            @if($isLowStock)
                                <p class="text-red-500 text-xs"> Low stock</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Units Sold</p>
                            <p class="text-blue-400 font-semibold">{{ $product->total_sales }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">Rating</p>
                            <p class="text-yellow-400">{{ $product->rating ?? '0.0' }} 
                                <span class="text-gray-500 text-xs">({{ $product->total_reviews }})</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-400 py-10">No products found.</div>
    @endforelse
</div>
