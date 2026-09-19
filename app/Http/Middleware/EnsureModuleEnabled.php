<?php

namespace App\Http\Middleware;

use App\Services\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ): Response {
        abort_unless(
            FeatureToggleService::enabled($feature),
            404,
            'This feature is currently disabled by the system administrator.'
        );

        return $next($request);
    }
}
