<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OfflineMfaService;
use App\Services\SecurityLogService;
use App\Services\UserSessionSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function challenge()
    {
        return view('auth.mfa-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = (int) $request->session()->get('mfa_pending_user');
        abort_if($userId <= 0, 419, 'Your MFA session has expired. Please sign in again.');

        $user = User::findOrFail($userId);
        $throttleKey = 'mfa|'.$user->id.'|'.$request->ip();
        $maxAttempts = max(1, (int) config('security.mfa_max_attempts', 5));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            SecurityLogService::event('mfa.challenge.locked', $request, [
                'account_reference' => hash('sha256', (string) $user->id),
            ]);

            throw ValidationException::withMessages([
                'code' => "Too many MFA attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! OfflineMfaService::verify($user, $data['code'])) {
            RateLimiter::hit($throttleKey, max(60, (int) config('security.mfa_lockout_seconds', 300)));
            SecurityLogService::event('mfa.challenge.failed', $request, [
                'account_reference' => hash('sha256', (string) $user->id),
            ]);

            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or expired.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login(
            $user,
            (bool) $request->session()->get('mfa_remember')
        );

        $request->session()->forget([
            'mfa_pending_user',
            'mfa_remember',
        ]);
        $request->session()->regenerate();

        if (! $user->force_password_change && ! $user->isPasswordExpired()) {
            $request->session()->put('administrative_notice_required', true);
        } else {
            $request->session()->forget('administrative_notice_required');
        }

        $user->updateSessionToken(
            $request->session()->getId()
        );
        UserSessionSecurityService::register($user, $request);

        SecurityLogService::event('mfa.challenge.succeeded', $request);

        if ($user->password_reset_requested_at) {
            $user->update([
                'password_reset_requested_at' => null,
            ]);
        }

        return redirect()->intended(
            route('dashboard')
        );
    }

    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->mfa_enabled) {
            return redirect()->route('dashboard')->with('info', 'Multi-factor authentication is already enabled.');
        }

        $secret = $user->mfa_secret ?: OfflineMfaService::generateSecret();

        if (! $user->mfa_secret) {
            $user->update([
                'mfa_secret' => $secret,
                'mfa_enabled' => false,
            ]);
        }

        return view(
            'auth.mfa-setup',
            compact('secret')
        );
    }

    public function enable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        abort_unless(
            hash_equals(
                (string) $user->mfa_secret,
                $data['secret']
            )
                && OfflineMfaService::code($data['secret']) === $data['code'],
            422,
            'Enter the current offline OTP to enable MFA.'
        );

        $user->update([
            'mfa_enabled' => true,
            'mfa_verified_at' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('administrative_notice_required', true);
        SecurityLogService::event('mfa.enrolled', $request);

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'Offline OTP MFA is enabled.'
            );
    }
}
