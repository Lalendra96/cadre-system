<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepts every authenticated request and redirects to /change-password
 * when the user's force_password_change flag is true. This is set by a
 * Super Admin when assigning a temporary password, and cleared by the user
 * once they choose a permanent password of their own.
 *
 * Applied to the protected routes group in routes/web.php.
 * The /change-password and /logout routes are outside this group and are
 * always accessible to authenticated users regardless of this flag.
 */
class EnsurePasswordNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_change) {
            return redirect()->route('change-password')
                ->with('info', 'A temporary password was set for your account. Please choose a new password to continue.');
        }

        return $next($request);
    }
}
