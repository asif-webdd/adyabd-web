@if (!$record)
    {{-- nothing --}}
@else
    @php
        $histories = $record->statusHistories()->latest()->take(10)->get();
    @endphp

    @if ($histories->isEmpty())
        <p style="font-size:13px;color:#9ca3af;margin:0">No history yet.</p>
    @else
        <div style="max-height:300px;overflow-y:auto;padding-right:4px">
            @foreach ($histories as $i => $h)
                @php
                    $isLast    = $i === $histories->count() - 1;
                    $label     = ucwords(str_replace('_', ' ', $h->status));
                    $time      = $h->created_at->format('d M · g:i A');
                    $by        = $h->changed_by ? ' · ' . $h->changed_by : '';
                    $lineStyle = $isLast ? 'visibility:hidden' : '';
                @endphp
                <div style="display:flex;gap:10px">
                    {{-- Timeline dot + line --}}
                    <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0">
                        <div style="width:9px;height:9px;border-radius:50%;background:#1D9E75;margin-top:3px;flex-shrink:0"></div>
                        <div style="width:1px;flex:1;background:#e5e7eb;margin:4px 0;min-height:20px;{{ $lineStyle }}"></div>
                    </div>
                    {{-- Content --}}
                    <div style="padding-bottom:14px">
                        <p style="margin:0;font-size:13px;font-weight:600;color:#111827">{{ $label }}</p>
                        <span style="font-size:11px;color:#9ca3af">{{ $time }}{{ $by }}</span>
                        @if ($h->note)
                            <p style="margin:4px 0 0;font-size:12px;color:#6b7280;background:#f9fafb;padding:6px 8px;border-radius:6px">
                                {{ $h->note }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif
