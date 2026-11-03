<?php

namespace App\Http\Middleware;

use App\Models\{IpBlock, User};
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if (IpBlock::active()->where('ip_address', $ip)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchases from your IP address have been restricted. For assistance, call: 16488',
                'code'    => 'IP_BLOCKED',
            ], 403);
        }

        if ($request->user()) {
            $user = $request->user();
            if ($user->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Reason: ' . ($user->blocked_reason ?? 'Policy violation') . '. For assistance, call: 16488',
                    'code'    => 'USER_BLOCKED',
                    'reason'  => $user->blocked_reason,
                ], 403);
            }
            // Update IP tracking silently
            if ($user->last_login_ip !== $ip) {
                $user->updateQuietly([
                    'last_login_ip' => $ip
                ]);
            }
        }

        return $next($request);
    }
}
