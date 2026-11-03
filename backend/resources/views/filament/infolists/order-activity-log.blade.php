@php
    use App\Models\ActivityLog;
    use App\Models\Order;

    $order = $record ?? (isset($getRecord) && is_callable($getRecord) ? ($getRecord)() : null);
    if (! $order) { return; }

    $logs = ActivityLog::where('subject_type', Order::class)
        ->where('subject_id', $order->id)
        ->latest('created_at')
        ->get();

    $colorMap = [
        'created'  => 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30',
        'updated'  => 'bg-blue-500/20 text-blue-400 ring-1 ring-blue-500/30',
        'deleted'  => 'bg-red-500/20 text-red-400 ring-1 ring-red-500/30',
        'restored' => 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/30',
        'login'    => 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30',
        'logout'   => 'bg-gray-500/20 text-gray-400 ring-1 ring-gray-500/30',
    ];

    $dotColorMap = [
        'created'  => 'text-emerald-400',
        'updated'  => 'text-blue-400',
        'deleted'  => 'text-red-400',
        'restored' => 'text-amber-400',
        'login'    => 'text-emerald-400',
        'logout'   => 'text-gray-500',
        'default'  => 'text-gray-500',
    ];
@endphp

@if ($logs->isEmpty())
    <div class="flex flex-col items-center justify-center gap-3 py-10 text-gray-600">
        <x-heroicon-o-clock class="w-8 h-8 opacity-40" />
        <span class="text-sm">No activity recorded for this order yet.</span>
    </div>
@else
    <div class="relative pl-2">
        {{-- Timeline spine --}}
        <div class="absolute left-6 top-2 bottom-2 w-px bg-white/5"></div>

        <ol class="space-y-1">
            @foreach ($logs as $log)
                @php
                    $color    = $colorMap[$log->event] ?? 'bg-gray-500/20 text-gray-400 ring-1 ring-gray-500/30';
                    $dotColor = $dotColorMap[$log->event] ?? $dotColorMap['default'];
                    $changes  = $log->properties ?? [];
                    $old      = $changes['old'] ?? [];
                    $new      = $changes['attributes'] ?? [];
                @endphp

                <li class="relative flex gap-4 pb-4">
                    {{-- Dot --}}
                    <div class="relative z-10 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-900 ring-1 ring-white/10 mt-1 shadow-lg">
                        @if ($log->event === 'created')
                            <x-heroicon-o-plus-circle class="w-4 h-4 {{ $dotColor }}" />
                        @elseif ($log->event === 'updated')
                            <x-heroicon-o-pencil-square class="w-4 h-4 {{ $dotColor }}" />
                        @elseif ($log->event === 'deleted')
                            <x-heroicon-o-trash class="w-4 h-4 {{ $dotColor }}" />
                        @elseif ($log->event === 'login')
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 {{ $dotColor }}" />
                        @elseif ($log->event === 'logout')
                            <x-heroicon-o-arrow-left-on-rectangle class="w-4 h-4 {{ $dotColor }}" />
                        @else
                            <x-heroicon-o-ellipsis-horizontal-circle class="w-4 h-4 text-gray-500" />
                        @endif
                    </div>

                    {{-- Card --}}
                    <div class="flex-1 min-w-0 rounded-xl bg-white/[0.03] border border-white/[0.07] px-4 py-3 hover:bg-white/[0.05] transition-colors">

                        {{-- Top row: badge + action --}}
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $color }}">
                                {{ ucfirst($log->event) }}
                            </span>
                            <span class="text-sm font-medium text-gray-200">
                                {{ $log->action ?? $log->description }}
                            </span>
                        </div>

                        {{-- Meta: who, when, IP --}}
                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500">
                            @if ($log->causer_name)
                                <span class="flex items-center gap-1.5">
                                    <x-heroicon-m-user class="w-3 h-3 text-gray-600" />
                                    <span class="text-gray-400">{{ $log->causer_name }}</span>
                                </span>
                            @else
                                <span class="flex items-center gap-1.5">
                                    <x-heroicon-m-cog-6-tooth class="w-3 h-3 text-gray-600" />
                                    <span class="text-gray-500">System / API</span>
                                </span>
                            @endif

                            <span class="flex items-center gap-1.5">
                                <x-heroicon-m-clock class="w-3 h-3 text-gray-600" />
                                <span class="text-gray-400">{{ $log->created_at->format('d M Y, h:i:s A') }}</span>
                                <span class="text-gray-600">&middot; {{ $log->created_at->diffForHumans() }}</span>
                            </span>

                            @if ($log->ip_address)
                                <span class="flex items-center gap-1.5">
                                    <x-heroicon-m-globe-alt class="w-3 h-3 text-gray-600" />
                                    <span class="font-mono text-gray-400">{{ $log->ip_address }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Diff: before / after --}}
                        @if ($old || $new)
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                @if ($old)
                                    <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-3">
                                        <p class="font-semibold text-red-400 mb-2 flex items-center gap-1">
                                            <x-heroicon-m-arrow-left class="w-3 h-3" /> Before
                                        </p>
                                        @foreach ($old as $key => $val)
                                            <div class="flex gap-1.5 text-red-300/80 py-0.5 border-b border-red-500/10 last:border-0">
                                                <span class="font-mono text-red-500/60 shrink-0">{{ $key }}:</span>
                                                <span class="break-all">{{ is_array($val) ? json_encode($val) : ($val ?? '—') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($new)
                                    <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3">
                                        <p class="font-semibold text-emerald-400 mb-2 flex items-center gap-1">
                                            <x-heroicon-m-arrow-right class="w-3 h-3" /> After
                                        </p>
                                        @foreach ($new as $key => $val)
                                            <div class="flex gap-1.5 text-emerald-300/80 py-0.5 border-b border-emerald-500/10 last:border-0">
                                                <span class="font-mono text-emerald-500/60 shrink-0">{{ $key }}:</span>
                                                <span class="break-all">{{ is_array($val) ? json_encode($val) : ($val ?? '—') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
