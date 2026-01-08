<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure user is authenticated
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token may be expired or invalid.',
            ], 401);
        }

        // Check if user's token has the access-api ability
        if (! $request->user()->tokenCan('access-api')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token type. This endpoint requires an access token.',
            ], 403);
        }

        return $next($request);
    }
}
