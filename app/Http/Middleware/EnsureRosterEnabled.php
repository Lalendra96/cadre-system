<?php

namespace App\Http\Middleware;

use App\Services\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;

class EnsureRosterEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(FeatureToggleService::enabled('roster'), 404, 'Roster module is disabled.');
        return $next($request);
    }
}
