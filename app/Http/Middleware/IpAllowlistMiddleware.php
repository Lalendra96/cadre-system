<?php

namespace App\Http\Middleware;

use App\Models\IpAllowlist;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security 17 — IP Allowlist Enforcement.
 * Only applied when ip_allowlist_enabled = true in system settings.
 * Super Admin bypass is intentionally NOT provided — if the admin's IP
 * is not in the allowlist they must add it first from the local server.
 */
class IpAllowlistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::getBool('ip_allowlist_enabled', false)) {
            return $next($request);
        }

        $ip = $request->ip();

        // Always allow loopback for server-level access
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        if (! IpAllowlist::allows($ip)) {
            abort(403, "Access denied: your IP address ({$ip}) is not on the allowlist. " .
                'Contact the system administrator.');
        }

        return $next($request);
    }
}
