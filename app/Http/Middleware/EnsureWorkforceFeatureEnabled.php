<?php

namespace App\Http\Middleware;

use App\Services\WorkforceFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkforceFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(app(WorkforceFeatureService::class)->enabled($feature), 404, 'This workforce feature is disabled by the system administrator.');
        return $next($request);
    }
}
