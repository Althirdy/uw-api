<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role_id, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have the required role to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
