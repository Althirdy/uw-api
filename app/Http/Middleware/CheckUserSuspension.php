<?php

namespace App\Http\Middleware;

use App\Models\UserSuspension;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
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
    }
}
