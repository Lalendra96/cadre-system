<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSecuritySession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class UserSessionSecurityService
{
    public const ONLINE_WINDOW_MINUTES = 5;

    public static function hashSessionId(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }

    public static function register(User $user, Request $request): ?UserSecuritySession
    {
        if (! Schema::hasTable('user_security_sessions')) {
            return null;
        }

        $sessionId = $request->session()->getId();
        $hash = self::hashSessionId($sessionId);

        return UserSecuritySession::updateOrCreate(
            ['session_hash' => $hash],
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'device_label' => self::deviceLabel((string) $request->userAgent()),
                'authenticated_at' => now(),
                'last_activity_at' => now(),
                'revoked_at' => null,
                'revoked_by' => null,
                'revoke_reason' => null,
            ]
        );
    }

    public static function touch(User $user, Request $request): ?UserSecuritySession
    {
        if (! Schema::hasTable('user_security_sessions')) {
            return null;
        }

        $hash = self::hashSessionId($request->session()->getId());
        $record = UserSecuritySession::where('session_hash', $hash)->where('user_id', $user->id)->first();

        if (! $record) {
            return self::register($user, $request);
        }

        if (! $record->last_activity_at || $record->last_activity_at->lt(now()->subSeconds(60))) {
            $record->forceFill([
                'last_activity_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'device_label' => self::deviceLabel((string) $request->userAgent()),
            ])->save();
        }

        return $record;
    }

    public static function revoke(UserSecuritySession $session, User $actor, string $reason): void
    {
        $session->forceFill([
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revoke_reason' => mb_substr($reason, 0, 255),
        ])->save();

        // If this is the user's currently accepted session, rotate the authoritative
        // token so ConcurrentSessionMiddleware logs it out on its very next request.
        $sessionUser = $session->user;
        if ($sessionUser && $sessionUser->current_session_token
            && hash_equals(self::hashSessionId((string) $sessionUser->current_session_token), $session->session_hash)) {
            $sessionUser->forceFill(['current_session_token' => 'revoked:'.Str::random(64)])->save();
        }
    }

    public static function revokeAll(User $target, User $actor, string $reason, ?string $exceptSessionHash = null): int
    {
        $query = UserSecuritySession::where('user_id', $target->id)->whereNull('revoked_at');
        if ($exceptSessionHash) {
            $query->where('session_hash', '<>', $exceptSessionHash);
        }

        $count = $query->update([
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revoke_reason' => mb_substr($reason, 0, 255),
            'updated_at' => now(),
        ]);

        if (! $exceptSessionHash) {
            $target->forceFill([
                'current_session_token' => 'revoked:'.Str::random(64),
                'remember_token' => null,
            ])->save();
        }

        return $count;
    }

    public static function deviceLabel(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        $browser = str_contains($ua, 'edg/') ? 'Edge'
            : (str_contains($ua, 'chrome/') ? 'Chrome'
                : (str_contains($ua, 'firefox/') ? 'Firefox'
                    : (str_contains($ua, 'safari/') ? 'Safari' : 'Browser')));
        $os = str_contains($ua, 'windows') ? 'Windows'
            : (str_contains($ua, 'android') ? 'Android'
                : (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') ? 'iOS/iPadOS'
                    : (str_contains($ua, 'mac os') ? 'macOS'
                        : (str_contains($ua, 'linux') ? 'Linux' : 'Unknown OS'))));

        return $browser.' on '.$os;
    }
}
