<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SecurityLogService
{
    /**
     * Write a security event without recording credentials or other secrets.
     */
    public static function event(string $event, Request $request, array $context = []): void
    {
        $user = $request->user();

        $payload = array_merge([
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'route' => optional($request->route())->getName(),
        ], $context);

        Log::channel('security')->info($event, $payload);

        // Persist a queryable security trail for the Super Admin console.
        // Logging must never break the user's request during deployment before
        // the migration has run, therefore the database write is best-effort.
        try {
            if (Schema::hasTable('security_events')) {
                SecurityEvent::create([
                    'user_id' => $user?->id,
                    'event' => $event,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                    'route_name' => optional($request->route())->getName(),
                    'context' => $context ?: null,
                    'occurred_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('security_event_database_write_failed', ['error' => $e->getMessage()]);
        }
    }
}
