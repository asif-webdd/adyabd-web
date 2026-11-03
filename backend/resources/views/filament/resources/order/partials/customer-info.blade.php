@if (!$record?->user)
    <p class="text-sm text-gray-400">No customer linked.</p>
@else
    @php
        $u          = $record->user;
        $initials   = collect(explode(' ', $u->name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
        $totalOrders = $u->orders()->count();
        $totalSpent  = number_format($u->orders()->sum('total_amount'), 0);
        $memberSince = $u->created_at->format('M Y');
        $phone       = $u->phone ?? $record->shipping_phone ?? '—';
    @endphp

    {{-- Avatar + name --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #f3f4f6">
        <div style="width:42px;height:42px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1d4ed8;font-size:14px;flex-shrink:0">
            {{ $initials }}
        </div>
        <div>
            <p style="margin:0;font-weight:600;font-size:14px;color:#111827">{{ $u->name }}</p>
            <p style="margin:2px 0 0;font-size:12px;color:#6b7280">Member since {{ $memberSince }}</p>
        </div>
    </div>

    {{-- Stats rows --}}
    <div style="font-size:13px">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Total orders</span>
            <strong>{{ $totalOrders }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Total spent</span>
            <strong>BDT  {{ $totalSpent }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Account status</span>
            @if ($u->is_blocked)
                <span style="color:#dc2626;font-weight:600">Blocked</span>
            @else
                <span style="color:#16a34a;font-weight:600">Active</span>
            @endif
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6">
            <span style="color:#6b7280">Email</span>
            <span style="color:#374151">{{ $u->email }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0">
            <span style="color:#6b7280">Phone</span>
            <span style="color:#374151">{{ $phone }}</span>
        </div>
    </div>
@endif
