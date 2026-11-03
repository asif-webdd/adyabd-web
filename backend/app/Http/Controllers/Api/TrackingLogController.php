<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrackingLog;
use Illuminate\Http\Request;

class TrackingLogController extends Controller
{
    // Cloudflare Worker এখানে প্রতিটা ইভেন্টের সারাংশ পাঠায়
    public function store(Request $request)
    {
        $key = (string) config('services.tracking.key');
        if ($key === '' || ! hash_equals($key, (string) $request->header('X-Server-Key'))) {
            abort(401);
        }

        $data = $request->validate([
            'event_name' => 'required|string|max:50',
            'event_id'   => 'required|string|max:191',
            'source'     => 'required|in:browser,server',
            'order_id'   => 'nullable|string|max:64',
            'value'      => 'nullable|numeric',
            'currency'   => 'nullable|string|max:8',
            'page_path'  => 'nullable|string|max:255',
            'has_error'  => 'required|boolean',
            'results'    => 'nullable|array',
        ]);

        //TrackingLog::create($data);
        TrackingLog::create($data + ['created_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
