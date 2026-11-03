@php
    $t = $a['totals'];
    $g = \App\Support\TrackingAnalytics::geometry($a);
    $money = fn ($n) => '৳' . number_format((float) $n);
    $badge = function ($pct) {
        if ($pct === null) {
            return null;
        }

        return ['text' => ($pct > 0 ? '+' : '') . number_format($pct, 1) . '%', 'up' => $pct >= 0];
    };
    $evChange = $badge($a['change']['events']);
    $revChange = $badge($a['change']['revenue']);
    $rangeText = $a['start']->format('d M Y') . ' – ' . $a['end']->format('d M Y');
    $hasData = $t['events'] > 0;
    $maxDist = max(array_merge([1], array_column($a['distribution'], 'count')));
@endphp

<style>
    .tkl-filters { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
    .tkl-filters > div { min-width: 200px; flex: 0 1 260px; }
    .tkl-label { display: block; margin-bottom: .35rem; font-size: .8rem; font-weight: 600; }
    .tkl-hint { font-size: .75rem; opacity: .6; align-self: center; }
    .tkl-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; }
    .tkl-k { font-size: .8rem; opacity: .65; }
    .tkl-v { font-size: 1.7rem; font-weight: 700; line-height: 1.2; margin-top: .25rem; }
    .tkl-sub { font-size: .75rem; margin-top: .35rem; opacity: .75; }
    .tkl-sub.up { color: #16a34a; opacity: 1; }
    .tkl-sub.down { color: #dc2626; opacity: 1; }
    .tkl-chart { width: 100%; height: auto; display: block; color: inherit; }
    .tkl-legend { display: flex; flex-wrap: wrap; gap: .5rem 1.1rem; justify-content: center; margin-top: .75rem; font-size: .75rem; }
    .tkl-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .tkl-dot { width: .7rem; height: .7rem; border-radius: 999px; display: inline-block; }
    .tkl-two { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 1.5rem; }
    @media (max-width: 900px) { .tkl-two { grid-template-columns: minmax(0, 1fr); } }
    .tkl-toggle { display: inline-flex; border-radius: .5rem; overflow: hidden; border: 1px solid rgba(127,127,127,.35); }
    .tkl-toggle button { padding: .3rem .8rem; font-size: .8rem; font-weight: 600; opacity: .65; }
    .tkl-toggle button.on { background: rgba(127,127,127,.25); opacity: 1; }
    .tkl-frow { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center; min-height: 64px; }
    .tkl-fbar { margin: 0 auto; height: 52px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: .9rem; min-width: 64px; text-shadow: 0 1px 2px rgba(0,0,0,.35); }
    .tkl-fname { font-weight: 600; font-size: .9rem; }
    .tkl-fsub { font-size: .75rem; opacity: .65; }
    .tkl-drow { display: grid; grid-template-columns: 130px 1fr 110px; gap: .75rem; align-items: center; padding: .4rem 0; font-size: .85rem; }
    .tkl-track { height: 12px; border-radius: 999px; background: rgba(127,127,127,.2); overflow: hidden; }
    .tkl-fill { height: 100%; border-radius: 999px; }
    .tkl-top { display: flex; justify-content: space-between; padding: .55rem 0; font-size: .9rem; border-bottom: 1px solid rgba(127,127,127,.15); }
    .tkl-top.total { border-bottom: 0; border-top: 1px solid rgba(127,127,127,.35); margin-top: .35rem; font-weight: 700; }
    .tkl-empty { text-align: center; padding: 2.5rem 1rem; opacity: .6; font-size: .9rem; }
    .tkl-chartwrap { position: relative; }
    .tkl-tip { position: absolute; z-index: 30; pointer-events: none; min-width: 200px; max-width: 280px; padding: .65rem .8rem; border-radius: .6rem;
               background: rgba(15, 23, 42, .96); color: #f1f5f9; font-size: .78rem; line-height: 1.5;
               box-shadow: 0 10px 25px -5px rgba(0,0,0,.45); border: 1px solid rgba(148,163,184,.25); }
    .tkl-tip-title { font-weight: 700; font-size: .85rem; margin-bottom: .35rem; }
    .tkl-tip-row { display: flex; align-items: center; gap: .5rem; white-space: nowrap; }
    .tkl-tip-sw { width: .7rem; height: .7rem; border-radius: 2px; flex: 0 0 auto; }
    .tkl-tip-row.bad { color: #fca5a5; }
</style>

{{-- ───────── ফিল্টার ───────── --}}
<x-filament::section>
    <div class="tkl-filters">
        <div>
            <label class="tkl-label">Date range</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="range">
                    @foreach (\App\Support\TrackingAnalytics::RANGES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @if ($range === 'custom')
            <div>
                <label class="tkl-label">From</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="from" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="tkl-label">Until</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="until" />
                </x-filament::input.wrapper>
            </div>
        @endif

        <div class="tkl-hint">{{ $rangeText }} · Asia/Dhaka</div>
    </div>
</x-filament::section>

{{-- ───────── সামারি কার্ড ───────── --}}
<div class="tkl-cards">
    <x-filament::section>
        <div class="tkl-k">Events</div>
        <div class="tkl-v">{{ number_format($t['events']) }}</div>
        <div class="tkl-sub {{ $evChange ? ($evChange['up'] ? 'up' : 'down') : '' }}">
            {{ $evChange ? $evChange['text'] . ' vs previous period' : 'Sent to Meta / GA4 by the Worker' }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="tkl-k">Purchases</div>
        <div class="tkl-v">{{ number_format($t['purchases']) }}</div>
        <div class="tkl-sub">{{ number_format($t['server_purchases']) }} from server</div>
    </x-filament::section>

    <x-filament::section>
        <div class="tkl-k">Revenue</div>
        <div class="tkl-v">{{ $money($t['revenue']) }}</div>
        <div class="tkl-sub {{ $revChange ? ($revChange['up'] ? 'up' : 'down') : '' }}">
            {{ $revChange ? $revChange['text'] . ' vs previous period' : 'Purchase value' }}
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="tkl-k">Conversion</div>
        <div class="tkl-v">{{ $t['conversion'] === null ? '—' : number_format($t['conversion'], 1) . '%' }}</div>
        <div class="tkl-sub">{{ number_format($t['purchases']) }} / {{ number_format($t['add_to_cart']) }} AddToCart</div>
    </x-filament::section>

    <x-filament::section>
        <div class="tkl-k">Success rate</div>
        <div class="tkl-v">{{ number_format($t['success_rate'], 1) }}%</div>
        <div class="tkl-sub {{ $t['failed'] > 0 ? 'down' : 'up' }}">{{ number_format($t['failed']) }} failed</div>
    </x-filament::section>
</div>

{{-- ───────── Event Analytics চার্ট ───────── --}}
<x-filament::section heading="Event Analytics">
    @if (! $hasData)
        <div class="tkl-empty">এই সময়ে কোনো ইভেন্ট নেই।</div>
    @else
        <div class="tkl-chartwrap"
             x-data="{ hover: null, mx: 0, my: 0, flip: false }"
             x-on:mousemove="const r = $el.getBoundingClientRect(); mx = $event.clientX - r.left; my = $event.clientY - r.top; flip = mx > r.width * 0.55"
             x-on:mouseleave="hover = null">
            <svg class="tkl-chart" viewBox="0 0 {{ $g['W'] }} {{ $g['H'] }}" role="img" aria-label="Event analytics chart">
                @foreach ($g['ticksL'] as $tick)
                    <line x1="{{ $g['L'] }}" x2="{{ $g['W'] - $g['R'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="currentColor" stroke-opacity=".12" />
                    <text x="{{ $g['L'] - 8 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end" font-size="11" fill="currentColor" fill-opacity=".6">{{ $tick['text'] }}</text>
                @endforeach
                @foreach ($g['ticksR'] as $tick)
                    <text x="{{ $g['W'] - $g['R'] + 8 }}" y="{{ $tick['y'] + 4 }}" text-anchor="start" font-size="11" fill="currentColor" fill-opacity=".6">{{ $tick['text'] }}</text>
                @endforeach

                {{-- হোভার করা দিনের হালকা ব্যান্ড --}}
                @foreach ($g['columns'] as $i => $col)
                    <rect x="{{ $col['x'] }}" y="{{ $col['y'] }}" width="{{ $col['w'] }}" height="{{ $col['h'] }}" fill="currentColor" fill-opacity=".07" x-show="hover === {{ $i }}" style="display: none" />
                @endforeach

                @foreach ($g['rects'] as $r)
                    <rect x="{{ $r['x'] }}" y="{{ $r['y'] }}" width="{{ $r['w'] }}" height="{{ $r['h'] }}" fill="{{ $r['color'] }}" rx="2" />
                @endforeach
                @foreach ($g['vrects'] as $r)
                    <rect x="{{ $r['x'] }}" y="{{ $r['y'] }}" width="{{ $r['w'] }}" height="{{ $r['h'] }}" fill="{{ \App\Support\TrackingAnalytics::VALUE_COLOR }}" rx="2" />
                @endforeach

                <polyline points="{{ $g['line'] }}" fill="none" stroke="{{ \App\Support\TrackingAnalytics::LINE_COLOR }}" stroke-width="2.5" stroke-linejoin="round" />
                @foreach ($g['dots'] as $d)
                    <circle cx="{{ $d['cx'] }}" cy="{{ $d['cy'] }}" r="3.5" fill="{{ \App\Support\TrackingAnalytics::LINE_COLOR }}" stroke="#fff" stroke-width="1.5" />
                @endforeach

                @foreach ($g['xlabels'] as $x)
                    <text x="{{ $x['x'] }}" y="{{ $g['H'] - 14 }}" text-anchor="middle" font-size="11" fill="currentColor" fill-opacity=".6">{{ $x['text'] }}</text>
                @endforeach
                <text x="{{ $g['W'] - $g['R'] + 40 }}" y="{{ $g['T'] + 40 }}" font-size="11" fill="currentColor" fill-opacity=".5" transform="rotate(90 {{ $g['W'] - $g['R'] + 40 }} {{ $g['T'] + 40 }})">Revenue (৳)</text>

                {{-- সবার উপরে অদৃশ্য হোভার কলাম --}}
                @foreach ($g['columns'] as $i => $col)
                    <rect x="{{ $col['x'] }}" y="{{ $col['y'] }}" width="{{ $col['w'] }}" height="{{ $col['h'] }}" fill="transparent" x-on:mouseenter="hover = {{ $i }}" />
                @endforeach
            </svg>

            {{-- একটাই টুলটিপ, ভেতরের কনটেন্ট দিন অনুযায়ী বদলায় --}}
            <div class="tkl-tip" style="display: none" x-show="hover !== null"
                 x-bind:style="{ left: mx + 'px', top: my + 'px', transform: flip ? 'translate(calc(-100% - 16px), 16px)' : 'translate(16px, 16px)' }">
                @foreach ($a['buckets'] as $i => $b)
                    <div x-show="hover === {{ $i }}" style="display: none">
                        <div class="tkl-tip-title">{{ $b['full'] }}</div>
                        <div class="tkl-tip-row"><i class="tkl-tip-sw" style="background: {{ \App\Support\TrackingAnalytics::LINE_COLOR }}"></i> Total events: {{ number_format($b['total']) }}</div>
                        @foreach ($a['stack_keys'] as $key)
                            <div class="tkl-tip-row"><i class="tkl-tip-sw" style="background: {{ \App\Support\TrackingAnalytics::COLORS[$key] ?? \App\Support\TrackingAnalytics::COLORS['Other'] }}"></i> {{ $key }}: {{ number_format($b['by'][$key] ?? 0) }}</div>
                        @endforeach
                        <div class="tkl-tip-row"><i class="tkl-tip-sw" style="background: {{ \App\Support\TrackingAnalytics::VALUE_COLOR }}"></i> Purchase value: {{ $money($b['value']) }}</div>
                        @if ($b['failed'] > 0)
                            <div class="tkl-tip-row bad"><i class="tkl-tip-sw" style="background: #ef4444"></i> Failed: {{ number_format($b['failed']) }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tkl-legend">
            <span><i class="tkl-dot" style="background: {{ \App\Support\TrackingAnalytics::LINE_COLOR }}"></i> Total events</span>
            @foreach ($a['stack_keys'] as $key)
                <span><i class="tkl-dot" style="background: {{ \App\Support\TrackingAnalytics::COLORS[$key] ?? \App\Support\TrackingAnalytics::COLORS['Other'] }}"></i> {{ $key }}</span>
            @endforeach
            <span><i class="tkl-dot" style="background: {{ \App\Support\TrackingAnalytics::VALUE_COLOR }}"></i> Purchase value</span>
        </div>
    @endif
</x-filament::section>

{{-- ───────── Funnel/Distribution + Top Events ───────── --}}
<div class="tkl-two">
    <x-filament::section heading="Event Distribution">
        <x-slot name="headerEnd">
            <div class="tkl-toggle">
                <button type="button" wire:click="$set('funnelView', 'funnel')" @class(['on' => $funnelView === 'funnel'])>Funnel</button>
                <button type="button" wire:click="$set('funnelView', 'distribution')" @class(['on' => $funnelView === 'distribution'])>Distribution</button>
            </div>
        </x-slot>

        @if (! $hasData)
            <div class="tkl-empty">এই সময়ে কোনো ইভেন্ট নেই।</div>
        @elseif ($funnelView === 'funnel')
            @foreach ($a['funnel'] as $stage)
                <div class="tkl-frow">
                    <div class="tkl-fbar" style="width: {{ max(18, min(100, $stage['of_first'])) }}%; background: {{ $stage['color'] }}">
                        {{ number_format($stage['of_first'], 1) }}%
                    </div>
                    <div>
                        <div class="tkl-fname">{{ $stage['event'] }}</div>
                        <div class="tkl-fsub">
                            {{ number_format($stage['count']) }} events
                            @if ($stage['of_previous'] !== null)
                                — {{ number_format($stage['of_previous'], 0) }}% of previous
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            @foreach ($a['distribution'] as $row)
                <div class="tkl-drow">
                    <div>{{ $row['event'] }}</div>
                    <div class="tkl-track"><div class="tkl-fill" style="width: {{ max(2, $row['count'] / $maxDist * 100) }}%; background: {{ $row['color'] }}"></div></div>
                    <div style="text-align: right">{{ number_format($row['count']) }} · {{ number_format($row['share'], 1) }}%</div>
                </div>
            @endforeach
        @endif
    </x-filament::section>

    <x-filament::section heading="Top Events">
        @if (! $hasData)
            <div class="tkl-empty">এই সময়ে কোনো ইভেন্ট নেই।</div>
        @else
            @foreach ($a['events'] as $name => $e)
                <div class="tkl-top"><span>{{ $name }}</span><span>{{ number_format($e['count']) }}</span></div>
            @endforeach
            <div class="tkl-top total"><span>Total Events</span><span>{{ number_format($t['events']) }}</span></div>
        @endif
    </x-filament::section>
</div>
