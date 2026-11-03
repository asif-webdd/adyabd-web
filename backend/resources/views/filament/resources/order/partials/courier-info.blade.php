@if (!$record)
    <p class="text-sm text-gray-400">No courier data.</p>
@elseif (!$record->courier_tracking_id)
    <div style="text-align:center;padding:16px 0;color:#9ca3af;font-size:13px">
        <p style="margin:0">No courier booked yet.</p>
    </div>
@else
    @php
        $courier  = $record->courierMerchant?->name ?? '—';
        $tracking = $record->courier_tracking_id;
        $bookedAt = $record->courier_booked_at
            ? $record->courier_booked_at->format('d M Y')
            : '—';
    @endphp

    {{-- Tracking badge --}}
    <div style="background:#f9fafb;border-radius:8px;padding:12px;margin-bottom:12px;display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;background:#e0f2fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                 stroke="#0284c7" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 3v4M8 3v4"/>
            </svg>
        </div>
        <div>
            <p style="margin:0;font-weight:600;font-size:13px;color:#111827">{{ $tracking }}</p>
            <p style="margin:2px 0 0;font-size:12px;color:#6b7280">{{ $courier }}</p>
        </div>
    </div>

    {{-- Details rows --}}
    <div style="font-size:13px">
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Booked on</span>
            <span>{{ $bookedAt }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0">
            <span style="color:#6b7280">Courier</span>
            <span>{{ $courier }}</span>
        </div>
    </div>
@endif
