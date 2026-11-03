<div class="flex gap-1">
    @forelse((array) ($getState() ?? []) as $url)
        <img src="{{ $url }}" class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-gray-800" onerror="this.style.display='none'" />
    @empty
        <span class="text-gray-400 text-sm">—</span>
    @endforelse
</div>
