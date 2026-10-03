<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces organisation-required MFA after any mandatory password change
 * and before access to protected application functions/acknowledgements.
 */
class EnsureRequiredMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiresMfa() && ! $user->mfa_enabled) {
            $request->session()->forget('administrative_notice_required');

            return redirect()->route('mfa.setup')->with(
                'warning',
                'Multi-factor authentication is required for your account. Complete MFA setup to continue.'
            );
        }

        return $next($request);
    }
}
