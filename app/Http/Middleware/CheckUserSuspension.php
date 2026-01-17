<?php

namespace App\Http\Middleware;

use App\Models\UserSuspension;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DEPRECATED: This middleware is no longer used.
 *
 * Suspended users are allowed to login and view content (announcements, accidents, etc.)
 * but are blocked from write operations (submitting/deleting concerns) via controller-level checks.
 *
 * This approach allows suspended citizens to stay informed while preventing misuse.
 */
class CheckUserSuspension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // MIDDLEWARE DISABLED
        // Suspension is now checked at controller level for write operations only
        // Suspended users can login and view content

        return $next($request);

        /* ORIGINAL LOGIC - NOW DISABLED
        if (auth()->check()) {
            $userId = auth()->id();

            // Check if user is suspended
            if (UserSuspension::isUserSuspended($userId)) {
                $activeSuspension = UserSuspension::getActiveSuspension($userId);

                // Log out the user
                auth()->logout();

                // Invalidate session
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Prepare suspension message
                $message = 'Your account has been suspended.';
                if ($activeSuspension) {
                    if ($activeSuspension->punishment_type === 'suspension') {
                        $message = 'Your account has been permanently suspended due to violations of our terms of service.';
                    } else {
                        $expiresAt = $activeSuspension->expires_at->format('F j, Y \a\t g:i A');
                        $message = "Your account has been temporarily suspended until {$expiresAt}.";
                    }

                    if ($activeSuspension->reason) {
                        $message .= " Reason: {$activeSuspension->reason}";
                    }
                }

                // Redirect to login with error message
                return redirect()->route('login')
                    ->with('error', $message);
            }
        }

        return $next($request);
        */
    }
}
