<div class="space-y-4 p-2">

    {{-- Summary bar --}}
    @php
        $avg = $reviews->avg('rating') ?? 0;
        $total = $reviews->count();
        $approved = $reviews->where('is_approved', true)->count();
        $starCounts = collect([5,4,3,2,1])->mapWithKeys(fn($s) => [$s => $reviews->where('rating', $s)->count()]);
    @endphp

    <div class="rounded-xl border border-gray-700 bg-gray-800 p-4 flex flex-col md:flex-row gap-6 items-center">
        {{-- Big rating --}}
        <div class="text-center flex-shrink-0">
            <p class="text-5xl font-bold text-yellow-400">{{ number_format($avg, 1) }}</p>
            <div class="flex justify-center gap-0.5 mt-1">
                @for($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= round($avg) ? 'text-yellow-400' : 'text-gray-600' }} text-xl"></span>
                @endfor
            </div>
            <p class="text-gray-400 text-xs mt-1">{{ $total }} reviews · {{ $approved }} approved</p>
        </div>

        {{-- Star breakdown --}}
        <div class="flex-1 w-full space-y-1.5">
            @foreach([5,4,3,2,1] as $star)
                @php $count = $starCounts[$star]; $pct = $total > 0 ? ($count / $total) * 100 : 0; @endphp
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-yellow-400 w-8 text-right">{{ $star }} </span>
                    <div class="flex-1 bg-gray-700 rounded-full h-2">
                        <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-gray-400 w-6">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Review cards --}}
    @forelse($reviews as $review)
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4 space-y-3">

            {{-- Top: user + rating + date --}}
            <div class="flex items-start justify-between flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($review->user?->name ?? 'G', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm">{{ $review->user?->name ?? 'Anonymous' }}</p>
                        <p class="text-gray-400 text-xs">{{ $review->user?->phone ?? $review->user?->email ?? '' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="flex gap-0.5 justify-end">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600' }}"></span>
                        @endfor
                    </div>
                    <p class="text-gray-500 text-xs mt-0.5">{{ $review->created_at->format('d M Y') }}</p>
                </div>
            </div>

            {{-- Product --}}
            @if($review->product)
                <div class="flex items-center gap-2 bg-gray-700/50 rounded-lg px-3 py-2">
                    <span class="text-gray-400 text-xs">Product:</span>
                    <span class="text-blue-300 text-sm font-medium">{{ $review->product->name }}</span>
                    @if($review->is_verified)
                        <span class="ml-auto text-xs bg-green-900 text-green-300 px-2 py-0.5 rounded-full"> Verified Purchase</span>
                    @endif
                </div>
            @endif

            {{-- Title + Review text --}}
            @if($review->title)
                <p class="text-white font-semibold text-sm">{{ $review->title }}</p>
            @endif

            @php $text = $review->comment ?? $review->review ?? null; @endphp
            @if($text)
                <p class="text-gray-300 text-sm leading-relaxed">{{ $text }}</p>
            @endif

            {{-- Images --}}
            @if(!empty($review->images))
                <div class="flex gap-2 flex-wrap">
                    @foreach($review->images as $img)
                        <img src="{{ $img }}" class="w-16 h-16 rounded-lg object-cover border border-gray-600" alt="Review image">
                    @endforeach
                </div>
            @endif

            {{-- Badges + Admin reply --}}
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $review->is_approved ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-yellow-300' }}">
                    {{ $review->is_approved ? ' Approved' : '⏳ Pending Approval' }}
                </span>
                @if($review->is_featured)
                    <span class="text-xs bg-purple-900 text-purple-300 px-2 py-0.5 rounded-full"> Featured</span>
                @endif
            </div>

            {{-- Admin reply --}}
            @if($review->admin_reply)
                <div class="border-l-4 border-blue-500 bg-blue-900/20 rounded-r-lg px-4 py-3">
                    <p class="text-blue-300 text-xs font-semibold mb-1">
                         Admin Reply
                        @if($review->admin_replied_at)
                            <span class="text-gray-500 font-normal ml-1">· {{ \Carbon\Carbon::parse($review->admin_replied_at)->format('d M Y') }}</span>
                        @endif
                    </p>
                    <p class="text-gray-300 text-sm">{{ $review->admin_reply }}</p>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center text-gray-400 py-10">No reviews yet.</div>
    @endforelse
</div>
