<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSecuritySession;
use App\Services\SecurityLogService;
use App\Services\UserSessionSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecurityConsoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $now = now();
        $onlineCutoff = $now->copy()->subMinutes(UserSessionSecurityService::ONLINE_WINDOW_MINUTES);
        $recentCutoff = $now->copy()->subHours(24);

        $sessions = UserSecuritySession::with(['user.userRoles', 'revoker'])
            ->where(function ($q) use ($recentCutoff) {
                $q->whereNull('revoked_at')->orWhere('last_activity_at', '>=', $recentCutoff);
            })
            ->latest('last_activity_at')
            ->paginate(30, ['*'], 'sessions_page')
            ->withQueryString();

        $onlineCount = UserSecuritySession::whereNull('revoked_at')
            ->where('last_activity_at', '>=', $onlineCutoff)
            ->distinct('user_id')
            ->count('user_id');

        $activeSessionCount = UserSecuritySession::whereNull('revoked_at')->count();
        $failed24h = SecurityEvent::where('event', 'authentication.failed')
            ->where('occurred_at', '>=', $recentCutoff)->count();
        $remoteLogouts24h = SecurityEvent::whereIn('event', ['session.remote_logout.requested', 'session.remote_logout_all.requested'])
            ->where('occurred_at', '>=', $recentCutoff)->count();

        $events = SecurityEvent::with('user')
            ->latest('occurred_at')
            ->limit(100)
            ->get();

        $currentSessionHash = UserSessionSecurityService::hashSessionId($request->session()->getId());

        return view('security-console.index', compact(
            'sessions', 'events', 'onlineCount', 'activeSessionCount', 'failed24h',
            'remoteLogouts24h', 'onlineCutoff', 'currentSessionHash'
        ));
    }

    public function revokeSession(Request $request, UserSecuritySession $securitySession)
    {
        $actor = $this->authorizeSuperAdmin($request);
        $this->confirmAdminPassword($request, $actor);

        $currentHash = UserSessionSecurityService::hashSessionId($request->session()->getId());
        if ($securitySession->session_hash === $currentHash) {
            return back()->with('error', 'The current Super Admin session cannot be remotely terminated from this console. Use normal logout.');
        }

        if ($securitySession->revoked_at) {
            return back()->with('info', 'That session has already been revoked.');
        }

        $reason = trim((string) $request->input('reason')) ?: 'Remote logout by Super Admin';
        UserSessionSecurityService::revoke($securitySession, $actor, $reason);

        SecurityLogService::event('session.remote_logout.requested', $request, [
            'target_user_id' => $securitySession->user_id,
            'security_session_id' => $securitySession->id,
            'reason' => $reason,
        ]);

        return back()->with('success', 'The selected session has been revoked. The user will be logged out on the next authenticated request.');
    }

    public function revokeAll(Request $request, User $user)
    {
        $actor = $this->authorizeSuperAdmin($request);
        $this->confirmAdminPassword($request, $actor);

        $except = $actor->id === $user->id
            ? UserSessionSecurityService::hashSessionId($request->session()->getId())
            : null;

        $reason = trim((string) $request->input('reason')) ?: 'All sessions revoked by Super Admin';
        $count = UserSessionSecurityService::revokeAll($user, $actor, $reason, $except);

        SecurityLogService::event('session.remote_logout_all.requested', $request, [
            'target_user_id' => $user->id,
            'revoked_count' => $count,
            'preserved_current_admin_session' => (bool) $except,
            'reason' => $reason,
        ]);

        return back()->with('success', "Revoked {$count} active session(s) for {$user->name}.");
    }

    public function forcePasswordChange(Request $request, User $user)
    {
        $actor = $this->authorizeSuperAdmin($request);
        $this->confirmAdminPassword($request, $actor);

        if ($user->id === $actor->id) {
            return back()->with('error', 'Use your own Change Password page rather than forcing your current Super Admin account.');
        }

        $user->forceFill([
            'force_password_change' => true,
            'remember_token' => null,
            'current_session_token' => 'security-reset:'.Str::random(64),
        ])->save();

        UserSessionSecurityService::revokeAll($user, $actor, 'Password change required by Super Admin');

        SecurityLogService::event('account.force_password_change', $request, [
            'target_user_id' => $user->id,
        ]);

        return back()->with('success', "{$user->name} must change their password at the next sign-in. Existing sessions were revoked.");
    }

    public function resetMfa(Request $request, User $user)
    {
        $actor = $this->authorizeSuperAdmin($request);
        $this->confirmAdminPassword($request, $actor);

        if ($user->id === $actor->id) {
            return back()->with('error', 'For safety, the currently signed-in Super Admin cannot reset their own MFA from this console.');
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_verified_at' => null,
            'remember_token' => null,
            'current_session_token' => 'mfa-reset:'.Str::random(64),
        ])->save();

        UserSessionSecurityService::revokeAll($user, $actor, 'MFA reset by Super Admin');

        SecurityLogService::event('account.mfa_reset', $request, [
            'target_user_id' => $user->id,
        ]);

        return back()->with('success', "MFA was reset for {$user->name}. Existing sessions were revoked.");
    }

    private function authorizeSuperAdmin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403, 'Only Super Admin may access the Security Console.');

        return $user;
    }

    private function confirmAdminPassword(Request $request, User $actor): void
    {
        $request->validate([
            'admin_password' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (! Hash::check((string) $request->input('admin_password'), $actor->password)) {
            SecurityLogService::event('security_console.reauthentication_failed', $request);
            throw ValidationException::withMessages([
                'admin_password' => 'The Super Admin password is incorrect.',
            ]);
        }
    }
}
