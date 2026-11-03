@php
    use Illuminate\Support\HtmlString;

    $record       = $this->record;
    $orderItems   = \App\Models\OrderItem::with(['order.user'])->where('product_id', $record->id)->latest()->limit(100)->get();
    $delivered    = $orderItems->filter(fn ($i) => $i->order?->status === 'delivered');
    $totalRevenue = $orderItems->sum('total_price');
    $profitPerUnit = $record->cost_price ? ($record->price - $record->cost_price) : null;
    $totalProfit  = $profitPerUnit !== null ? $profitPerUnit * $delivered->sum('quantity') : null;
    $marginPct    = ($record->cost_price && $record->price > 0)
        ? round((($record->price - $record->cost_price) / $record->price) * 100, 1)
        : null;
    $reviews      = \App\Models\Review::where('product_id', $record->id)->latest()->limit(5)->get();
    $variants     = $record->variants()->get();
    $images       = $record->images()->orderBy('sort_order')->get();
    $primaryImg   = $record->primaryImage ?? $images->first();
    $imgUrl       = $primaryImg
        ? (str_starts_with($primaryImg->image_path, 'http') ? $primaryImg->image_path : asset('storage/' . $primaryImg->image_path))
        : 'https://placehold.co/600x600/0f172a/334155?text=No+Image';

    $threshold  = $record->low_stock_threshold ?? 5;
    $sc         = $record->quantity === 0 ? '#ef4444' : ($record->quantity <= $threshold ? '#f59e0b' : '#10b981');
    $stockLabel = $record->quantity === 0 ? 'Out of Stock' : ($record->quantity <= $threshold ? 'Low Stock' : 'In Stock');

    $cancelled = $orderItems->filter(fn ($i) => in_array($i->order?->status, ['cancelled','returned']))->count();
    $pending   = $orderItems->filter(fn ($i) => $i->order?->status === 'pending')->count();
    $conv      = $record->total_views > 0 ? round(($record->total_sales / $record->total_views) * 100, 2) : 0;

    $stars = fn (float $r): string => collect(range(1,5))->map(
        fn ($i) => '<span style="color:' . ($i <= round($r) ? '#f59e0b' : '#1e293b') . ';"></span>'
    )->join('');
@endphp

<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#f1f5f9;">

    {{-- ══════════════════════════════════════════════════════════════
         HERO: Gallery + Info
    ══════════════════════════════════════════════════════════════ --}}
    <div style="display:grid;grid-template-columns:360px 1fr;gap:32px;align-items:start;margin-bottom:28px;">

        {{-- Gallery --}}
        <div>
            <div style="border-radius:14px;overflow:hidden;background:#0f172a;aspect-ratio:1/1;border:1px solid rgba(255,255,255,0.07);">
                <img id="vp-hero" src="{{ $imgUrl }}" style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                @foreach ($images->take(6) as $img)
                    @php $u = str_starts_with($img->image_path,'http') ? $img->image_path : asset('storage/'.$img->image_path); @endphp
                    <button onclick="document.getElementById('vp-hero').src='{{ $u }}'"
                            style="width:60px;height:60px;border-radius:8px;overflow:hidden;border:2px solid rgba(255,255,255,0.1);cursor:pointer;background:none;padding:0;flex-shrink:0;transition:border-color .15s;"
                            onmouseover="this.style.borderColor='#6366f1'"
                            onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <img src="{{ $u }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Info --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Status flags --}}
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                @foreach ([
                    ['is_active',     'Active',        '#10b981'],
                    ['is_featured',   'Featured',      '#f59e0b'],
                    ['is_new',        'New',           '#3b82f6'],
                    ['is_trending',   'Trending',      '#a855f7'],
                    ['free_shipping', 'Free Shipping', '#06b6d4'],
                    ['has_variants',  'Has Variants',  '#64748b'],
                ] as [$field, $label, $color])
                    @php $on = (bool) $record->$field; @endphp
                    <span style="display:inline-flex;align-items:center;
                        background:{{ $on ? $color.'22' : 'rgba(255,255,255,0.04)' }};
                        color:{{ $on ? $color : '#ddd' }};
                        border:1px solid {{ $on ? $color.'55' : 'rgba(255,255,255,0.08)' }};
                        padding:4px 12px;border-radius:6px;font-size:11px;font-weight:700;letter-spacing:.03em;">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $on ? $color : '#ddd' }};{{ $on ? '' : 'opacity:.3;' }}display:inline-block;margin-right:5px;"></span>
                        {{ $label }}
                    </span>
                @endforeach
            </div>

            {{-- Name --}}
            <div>
                <h2 style="font-size:26px;font-weight:900;color:#f1f5f9;line-height:1.2;margin:0 0 4px;">{{ $record->name }}</h2>
                @if ($record->name_bn)
                    <div style="font-size:14px;color:#64748b;">{{ $record->name_bn }}</div>
                @endif
            </div>

            {{-- Rating + views --}}
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-size:15px;">{!! $stars($record->rating ?? 0) !!}</span>
                <span style="font-size:13px;font-weight:600;color:#cbd5e1;">{{ number_format($record->rating, 1) }}</span>
                <span style="color:#334155;">·</span>
                <span style="font-size:13px;color:#64748b;">{{ $record->total_reviews }} reviews</span>
                <span style="color:#334155;">·</span>
                <span style="font-size:13px;color:#64748b;">{{ number_format($record->total_views) }} views</span>
            </div>

            {{-- Price --}}
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:8px;">
                <span style="font-size:32px;font-weight:900;color:#10b981;letter-spacing:-1px;">BDT {{ number_format($record->price, 2) }}</span>
                @if ($record->compare_price)
                    <span style="font-size:16px;color:#ddd;text-decoration:line-through;">BDT {{ number_format($record->compare_price, 2) }}</span>
                @endif
                @if (($record->discount_percentage ?? 0) > 0)
                    <span style="background:#ef444420;color:#ef4444;border:1px solid #ef444430;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:800;">−{{ $record->discount_percentage }}%</span>
                @endif
            </div>

            {{-- Cost / Profit / Stock cards --}}
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
                @foreach ([
                    ['Cost Price',    $record->cost_price ? 'BDT '.number_format($record->cost_price,2) : '—', '#f59e0b', null],
                    ['Profit / Unit', $profitPerUnit !== null ? 'BDT '.number_format($profitPerUnit,2) : '—', '#3b82f6', null],
                    ['Stock',         $stockLabel, $sc, $record->quantity.' '.e($record->unit).' available'],
                ] as [$cl, $cv, $cc, $csub])
                    <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:14px 16px;">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#ddd;margin-bottom:6px;">{{ $cl }}</div>
                        <div style="font-size:18px;font-weight:800;color:{{ $cc }};">{{ $cv }}</div>
                        @if ($csub)
                            <div style="font-size:11px;color:#ddd;margin-top:2px;">{{ $csub }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Margin bar --}}
            @if ($marginPct !== null)
                @php $mc = $marginPct >= 30 ? '#10b981' : ($marginPct >= 10 ? '#f59e0b' : '#ef4444'); @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#ddd;margin-bottom:6px;">
                        <span>Profit Margin</span>
                        <span style="font-weight:800;color:{{ $mc }};">{{ $marginPct }}%</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,0.07);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:{{ $marginPct }}%;background:{{ $mc }};border-radius:99px;"></div>
                    </div>
                </div>
            @endif

            {{-- Stock bar --}}
            @php
                $stockMax = max($record->quantity + $record->total_sales, 1);
                $stockPct = min(100, round(($record->quantity / $stockMax) * 100));
            @endphp
            <div>
                <div style="display:flex;justify-content:space-between;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#ddd;margin-bottom:6px;">
                    <span style="color:{{ $sc }};font-weight:700;">{{ $stockLabel }}</span>
                    <span>{{ $record->quantity }} {{ $record->unit }} left · alert at {{ $threshold }}</span>
                </div>
                <div style="height:6px;background:rgba(255,255,255,0.07);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:{{ $stockPct }}%;background:{{ $sc }};border-radius:99px;"></div>
                </div>
            </div>

            {{-- Meta chips --}}
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach (array_filter([
                    $record->sku    ? ['SKU',      $record->sku]                     : null,
                    ['Category',     $record->category?->name ?? '—'],
                    $record->brand  ? ['Brand',    $record->brand->name]             : null,
                    $record->weight ? ['Weight',   $record->weight.' kg']            : null,
                    ['Unit',         $record->unit],
                    ['Sales',        number_format($record->total_sales)],
                ]) as $chip)
                    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.09);border-radius:8px;padding:8px 14px;">
                        <div style="font-size:10px;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px;">{{ $chip[0] }}</div>
                        <div style="font-size:13px;font-weight:700;color:#cbd5e1;">{{ $chip[1] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Tags --}}
            @php
                $rawTags = $record->tags;
                if (is_string($rawTags)) $rawTags = json_decode($rawTags, true) ?? array_map('trim', explode(',', $rawTags));
            @endphp
            @if (!empty($rawTags))
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach ((array)$rawTags as $tag)
                        @if (trim($tag))
                            <span style="background:rgba(99,102,241,0.12);color:#818cf8;border:1px solid rgba(99,102,241,0.2);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;">#{{ trim($tag) }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════════════════════════ --}}
    <div style="margin-bottom:28px;">
        <div style="font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px;">Performance Overview</div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            @foreach ([
                ['Units Sold',     number_format($record->total_sales),                                    '#6366f1', 'All time'],
                ['Total Revenue',  'BDT '.number_format($totalRevenue,0),                                     '#10b981', 'From orders'],
                ['Delivered Rev.', 'BDT '.number_format($delivered->sum('total_price'),0),                    '#059669', 'Confirmed'],
                ['Est. Profit',    $totalProfit !== null ? 'BDT '.number_format($totalProfit,0) : '—',        '#a855f7', 'Delivered × margin'],
                ['Avg Rating',     number_format($record->rating,1).' / 5',                                '#f59e0b', $record->total_reviews.' reviews'],
                ['Conversion',     $conv.'%',                                                               '#06b6d4', 'Views → sales'],
                ['Pending',        (string)$pending,                                                        '#f97316', 'Awaiting action'],
                ['Cancelled',      (string)$cancelled,                                                      '#ef4444', 'Lost orders'],
            ] as [$kt, $kv, $kc, $ks])
                <div style="background:rgba(255,255,255,0.025);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:20px 18px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#ddd;margin-bottom:10px;">{{ $ks }}</div>
                    <div style="font-size:24px;font-weight:900;color:{{ $kc }};letter-spacing:-.5px;margin-bottom:6px;">{{ $kv }}</div>
                    <div style="font-size:12px;color:#64748b;font-weight:500;">{{ $kt }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         SELLER
    ══════════════════════════════════════════════════════════════ --}}
    <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:24px;margin-bottom:28px;">
        <div style="font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:16px;">Seller</div>
        @if ($record->seller)
            @php
                $s  = $record->seller;
                $sc2 = match($s->status) { 'active' => '#10b981', 'suspended' => '#f59e0b', 'banned' => '#ef4444', default => '#64748b' };
                $initial = strtoupper(substr($s->shop_name, 0, 1));
            @endphp
            <div style="display:flex;gap:24px;align-items:flex-start;">
                <div style="width:72px;height:72px;border-radius:18px;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:900;color:#fff;">
                    {{ $initial }}
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                        <span style="font-size:20px;font-weight:800;color:#f1f5f9;">{{ $s->shop_name }}</span>
                        @if ($s->is_verified)
                            <span style="background:#10b98118;color:#10b981;border:1px solid #10b98140;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;"> Verified</span>
                        @else
                            <span style="background:#ef444418;color:#ef4444;border:1px solid #ef444440;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;"> Unverified</span>
                        @endif
                        <span style="background:{{ $sc2 }}18;color:{{ $sc2 }};border:1px solid {{ $sc2 }}40;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;">{{ ucfirst($s->status) }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
                        <span style="font-size:14px;">{!! $stars($s->rating ?? 0) !!}</span>
                        <span style="font-size:12px;color:#64748b;">{{ number_format($s->rating,1) }} · {{ $s->total_reviews }} reviews</span>
                        @foreach (array_filter([$s->phone ? ' '.$s->phone : null, $s->email ? 'Email: '.$s->email : null, $s->address ? ' '.$s->address : null]) as $ci)
                            <span style="color:#1e293b;margin:0 8px;">|</span>
                            <span style="font-size:12px;color:#94a3b8;">{{ $ci }}</span>
                        @endforeach
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;">
                        @foreach ([
                            ['Total Sales', number_format($s->total_sales),       '#3b82f6'],
                            ['Balance',     'BDT '.number_format($s->balance,0),     '#10b981'],
                            ['Commission',  $s->commission_rate.'%',              '#f59e0b'],
                            ['Reviews',     number_format($s->total_reviews),     '#a855f7'],
                            ['Rating',      number_format($s->rating,1).' / 5',  '#f59e0b'],
                        ] as [$sl, $sv, $scolor])
                            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:14px 16px;">
                                <div style="font-size:17px;font-weight:800;color:{{ $scolor }};">{{ $sv }}</div>
                                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#ddd;margin-top:4px;">{{ $sl }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <p style="color:#64748b;font-size:13px;">No seller assigned.</p>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         DESCRIPTION
    ══════════════════════════════════════════════════════════════ --}}
    <details style="margin-bottom:28px;">
        <summary style="cursor:pointer;font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;padding:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:10px;list-style:none;display:flex;justify-content:space-between;">
            <span>Description</span><span>▾</span>
        </summary>
        <div style="border:1px solid rgba(255,255,255,0.07);border-top:none;border-radius:0 0 10px 10px;padding:20px;">
            <div style="margin-bottom:16px;">
                <div style="font-size:11px;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">Summary</div>
                <div style="font-size:14px;color:#94a3b8;line-height:1.7;">{{ $record->short_description ?? 'No summary provided.' }}</div>
            </div>
            <div style="margin-bottom:16px;">
                <div style="font-size:11px;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;">Full Description</div>
                <div style="font-size:14px;color:#94a3b8;line-height:1.8;">{!! $record->description ?? 'No description provided.' !!}</div>
            </div>
            @if ($record->tags)
                <div>
                    <div style="font-size:11px;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">Tags</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach ((array)(is_string($record->tags) ? (json_decode($record->tags,true) ?? array_map('trim',explode(',',$record->tags))) : $record->tags) as $tag)
                            @if(trim($tag))
                                <span style="background:rgba(99,102,241,0.12);color:#818cf8;border:1px solid rgba(99,102,241,0.2);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;">#{{ trim($tag) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </details>

    {{-- ══════════════════════════════════════════════════════════════
         VARIANTS
    ══════════════════════════════════════════════════════════════ --}}
    @if ($record->has_variants)
        <details style="margin-bottom:28px;">
            <summary style="cursor:pointer;font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;padding:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:10px;list-style:none;display:flex;justify-content:space-between;">
                <span>Variants ({{ $variants->count() }})</span><span>▾</span>
            </summary>
            <div style="border:1px solid rgba(255,255,255,0.07);border-top:none;border-radius:0 0 10px 10px;padding:20px;">
                @if ($variants->isEmpty())
                    <p style="color:#64748b;font-size:13px;">No variants defined.</p>
                @else
                    <div style="overflow-x:auto;border-radius:10px;border:1px solid rgba(255,255,255,0.07);">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                            <tr style="background:rgba(255,255,255,0.04);border-bottom:1px solid rgba(255,255,255,0.08);">
                                @foreach (['SKU','Attributes','Price','Compare','Stock','Status'] as $h)
                                    <th style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#ddd;font-weight:700;">{{ $h }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($variants as $v)
                                @php
                                    $raw = $v->attribute_values;
                                    if (is_string($raw)) $raw = json_decode($raw,true) ?? [];
                                    if (! is_array($raw)) $raw = [];
                                    $vsc = $v->quantity === 0 ? '#ef4444' : ($v->quantity <= 5 ? '#f59e0b' : '#10b981');
                                @endphp
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                    <td style="padding:12px 14px;font-size:11px;color:#64748b;font-family:monospace;">{{ $v->sku }}</td>
                                    <td style="padding:12px 14px;">
                                        <div style="display:flex;gap:5px;flex-wrap:wrap;">
                                            @foreach ($raw as $a)
                                                @php
                                                    $label = is_array($a) && isset($a['attribute'],$a['value'])
                                                        ? $a['attribute'].': '.$a['value']
                                                        : (is_array($a) ? implode(': ',$a) : $a);
                                                @endphp
                                                <span style="background:rgba(99,102,241,0.14);color:#818cf8;border:1px solid rgba(99,102,241,0.2);border-radius:5px;padding:2px 9px;font-size:11px;font-weight:600;">{{ $label }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td style="padding:12px 14px;font-weight:800;color:#10b981;">BDT {{ number_format($v->price,2) }}</td>
                                    <td style="padding:12px 14px;">
                                        @if ($v->compare_price)
                                            <span style="color:#ddd;text-decoration:line-through;font-size:12px;">BDT {{ number_format($v->compare_price,2) }}</span>
                                        @else
                                            <span style="color:#334155;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 14px;font-weight:700;color:{{ $vsc }};">{{ $v->quantity }}</td>
                                    <td style="padding:12px 14px;">
                                        @if ($v->is_active)
                                            <span style="background:#10b98118;color:#10b981;border:1px solid #10b98130;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Active</span>
                                        @else
                                            <span style="background:#ef444418;color:#ef4444;border:1px solid #ef444430;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </details>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         ORDER HISTORY
    ══════════════════════════════════════════════════════════════ --}}
    <div style="margin-bottom:28px;">
        <div style="font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px;">Order History (last 100)</div>
        @if ($orderItems->isEmpty())
            <div style="text-align:center;padding:48px 24px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;">
                <div style="font-size:48px;opacity:.3;margin-bottom:12px;"></div>
                <div style="font-size:15px;font-weight:700;color:#ddd;">No orders yet</div>
            </div>
        @else
            @php
                $statusMeta = [
                    'pending'          => ['#f59e0b','Pending'],
                    'confirmed'        => ['#3b82f6','Confirmed'],
                    'processing'       => ['#8b5cf6','Processing'],
                    'shipped'          => ['#06b6d4','Shipped'],
                    'out_for_delivery' => ['#f97316','Out for Delivery'],
                    'delivered'        => ['#10b981','Delivered'],
                    'cancelled'        => ['#ef4444','Cancelled'],
                    'returned'         => ['#6b7280','Returned'],
                    'refunded'         => ['#ec4899','Refunded'],
                ];
            @endphp
            <div style="overflow-x:auto;border-radius:10px;border:1px solid rgba(255,255,255,0.07);">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                    <tr style="background:rgba(255,255,255,0.04);border-bottom:2px solid rgba(255,255,255,0.08);">
                        @foreach (['Order #','Customer','Qty','Unit Price','Total','Status','Payment','Method'] as $h)
                            <th style="padding:10px 14px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#ddd;font-weight:700;white-space:nowrap;">{{ $h }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orderItems as $item)
                        @php
                            $order = $item->order;
                            if (! $order) continue;
                            [$osc, $osl] = $statusMeta[$order->status] ?? ['#64748b', ucfirst($order->status)];
                            $isPaid = $order->payment_status === 'paid';
                            $pc = $isPaid ? '#10b981' : '#f59e0b';
                        @endphp
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                            <td style="padding:12px 14px;">
                                <span style="font-weight:800;color:#6366f1;font-size:13px;font-family:monospace;">#{{ $order->order_number }}</span>
                                <div style="font-size:11px;color:#ddd;margin-top:2px;">{{ $order->created_at?->format('d M Y') }}</div>
                            </td>
                            <td style="padding:12px 14px;">
                                <div style="font-size:13px;font-weight:600;color:#e2e8f0;">{{ $order->shipping_name ?? $order->user?->name ?? 'Guest' }}</div>
                                <div style="font-size:11px;color:#ddd;margin-top:2px;">{{ $order->shipping_phone ?? '—' }}</div>
                            </td>
                            <td style="padding:12px 14px;font-weight:700;color:#cbd5e1;">{{ $item->quantity }} <span style="font-size:11px;color:#ddd;">{{ $record->unit }}</span></td>
                            <td style="padding:12px 14px;font-size:13px;color:#64748b;">BDT {{ number_format($item->unit_price,2) }}</td>
                            <td style="padding:12px 14px;font-weight:900;color:#10b981;">BDT {{ number_format($item->total_price,2) }}</td>
                            <td style="padding:12px 14px;">
                                <span style="background:{{ $osc }}18;color:{{ $osc }};border:1px solid {{ $osc }}30;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;">{{ $osl }}</span>
                            </td>
                            <td style="padding:12px 14px;">
                                <span style="background:{{ $pc }}18;color:{{ $pc }};border:1px solid {{ $pc }}30;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">{{ ucfirst($order->payment_status ?? 'N/A') }}</span>
                            </td>
                            <td style="padding:12px 14px;font-size:12px;color:#ddd;">{{ ucfirst($order->payment_method ?? '—') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr style="background:rgba(99,102,241,0.06);border-top:1px solid rgba(99,102,241,0.15);">
                        <td colspan="2" style="padding:13px 14px;font-size:12px;color:#64748b;font-weight:600;">{{ $orderItems->count() }} order lines</td>
                        <td style="padding:13px 14px;font-weight:800;color:#e2e8f0;">{{ $orderItems->sum('quantity') }}</td>
                        <td></td>
                        <td style="padding:13px 14px;font-weight:900;color:#10b981;font-size:15px;">BDT {{ number_format($orderItems->sum('total_price'),2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         REVIEWS
    ══════════════════════════════════════════════════════════════ --}}
    <details style="margin-bottom:28px;">
        <summary style="cursor:pointer;font-size:13px;font-weight:700;color:#ddd;text-transform:uppercase;letter-spacing:.07em;padding:16px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:10px;list-style:none;display:flex;justify-content:space-between;">
            <span>Recent Reviews ({{ $reviews->count() }})</span><span>▾</span>
        </summary>
        <div style="border:1px solid rgba(255,255,255,0.07);border-top:none;border-radius:0 0 10px 10px;padding:20px;">
            @if ($reviews->isEmpty())
                <p style="color:#64748b;font-size:13px;">No reviews yet.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach ($reviews as $rev)
                        @php $ri = strtoupper(substr($rev->user?->name ?? 'A', 0, 1)); @endphp
                        <div style="background:rgba(255,255,255,0.025);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:18px 20px;">
                            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;">
                                <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;">{{ $ri }}</div>
                                <div>
                                    <div style="font-weight:700;color:#e2e8f0;font-size:13px;margin-bottom:4px;">{{ $rev->user?->name ?? 'Anonymous' }}</div>
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <span style="font-size:13px;">{!! $stars($rev->rating) !!}</span>
                                        <span style="font-size:11px;color:#ddd;">{{ $rev->created_at?->format('d M Y') }}</span>
                                        @if ($rev->is_verified_purchase)
                                            <span style="background:#10b98118;color:#10b981;border:1px solid #10b98130;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;"> Verified</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if ($rev->title)
                                <div style="font-weight:700;color:#cbd5e1;font-size:13px;margin-bottom:6px;">{{ $rev->title }}</div>
                            @endif
                            <div style="font-size:13px;color:#94a3b8;line-height:1.7;">{{ $rev->comment ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </details>

    {{-- ══════════════════════════════════════════════════════════════
         TIMESTAMPS
    ══════════════════════════════════════════════════════════════ --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        @foreach ([
            ['Created At',    $record->created_at?->format('d M Y, h:i A') ?? '—'],
            ['Last Updated',  $record->updated_at?->format('d M Y, h:i A') ?? '—'],
            ['Deleted At',    $record->deleted_at?->format('d M Y, h:i A') ?? 'Not deleted'],
        ] as [$tl, $tv])
            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:14px 16px;">
                <div style="font-size:10px;color:#ddd;text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">{{ $tl }}</div>
                <div style="font-size:13px;font-weight:600;color:#cbd5e1;">{{ $tv }}</div>
            </div>
        @endforeach
    </div>

</div>
