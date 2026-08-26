<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users who hold AT LEAST ONE of the listed roles.
 * A user can now hold multiple roles simultaneously (stored in user_roles).
 * Usage in routes: ->middleware('role:super_admin,planning_officer')
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Your account is inactive. Contact the system administrator.');
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'You are not authorised to access this section.');
        }

        return $next($request);
    }
}
