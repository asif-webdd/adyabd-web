<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets only users with an ACTIVE seller profile through. Everyone else gets a
 * clear JSON error instead of a 500 from `$request->user()->seller->id`.
 */
class EnsureSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $seller = $request->user()?->seller;

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'You do not have a seller account.', 'code' => 'NOT_A_SELLER'], 403);
        }
        if ($seller->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => $seller->status === 'pending'
                    ? 'Your seller application is still under review.'
                    : 'Your seller account is not active.',
                'code'    => 'SELLER_' . strtoupper($seller->status),
            ], 403);
        }

        return $next($request);
    }
}
