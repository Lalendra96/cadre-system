<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Handles the "Forgot Password" flow for an internal LAN system where
 * outbound email may not be configured. The user submits their email,
 * the system records the request timestamp, and the Super Admin sees a
 * notification badge on the Users screen to take action via the existing
 * temp-password reset flow.
 *
 * Security: the response is always the same success message regardless of
 * whether the email exists — this prevents account enumeration.
 */
class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function submit(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Record the request on the matching active account (if one exists).
        // We do this silently — the response is the same either way to prevent
        // account enumeration by an outside observer.
        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if ($user) {
            $user->update([
                'password_reset_requested_at' => now(),
            ]);
        }

        // Always show the same neutral success screen.
        return view('auth.forgot-password-sent', [
            'email' => $request->email,
        ]);
    }
}
