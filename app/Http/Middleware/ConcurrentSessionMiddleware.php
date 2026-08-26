<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security 16 — Concurrent Session Prevention.
 * Each authenticated user has a single valid session token stored in the DB.
 * When they log in from a new browser/device, that token is updated,
 * invalidating all previous sessions on next request.
 */
class ConcurrentSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::getBool('concurrent_session_lock_enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $sessionToken = $request->session()->getId();

        // If the user has a stored token and it doesn't match, invalidate.
        if ($user->current_session_token && ! $user->isCurrentSession($sessionToken)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your session was ended because you signed in from another browser or device. Please sign in again.');
        }

        return $next($request);
    }
}
